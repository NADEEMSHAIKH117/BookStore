<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
use App\Models\Book;
use App\Models\Cart;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{


    /**
     * @OA\Post(
     *   path="/api/addtocart",
     *   summary="Add Book to cart",
     *   description="User Can Add Book to cart ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"book_id"},
     *               @OA\Property(property="book_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Book added to Cart Sucessfully"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    public function addBookToCartByBookId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $cart = new Cart();
            $book = new Book();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'Your are not an User'], 404);
            }
            if ($currentUser) {
                $book_id = $request->input('book_id');
                $book_existance = $book->findBook($book_id);

                if (!$book_existance) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Book not Found'
                    ], 404);
                }
                $books = $book->findBook($book_id);
                if ($books->quantity == 0) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'OUT OF STOCK'
                    ], 404);
                }
                $book_cart = $cart->bookCart($book_id, $currentUser->id);
                if ($book_cart) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Book already added in cart'
                    ], 404);
                }
                $cart->book_id = $request->get('book_id');

                if ($currentUser->carts()->save($cart)) {
                    Cache::remember('carts', 3600, function () {
                        return DB::table('carts')->get();
                    });
                    return response()->json([
                        'message' => 'Book added to Cart Sucessfully'
                    ], 201);
                }
                return response()->json(['message' => 'Book cannot be added to Cart'], 405);
            } else {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/deleteBookByCartId",
     *   summary="Delete the book from cart",
     *   description=" Delete cart ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Book deleted Sucessfully from cart"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    public function deleteBookByCartId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try {
            $id = $request->input('id');
            $currentUser = JWTAuth::parseToken()->authenticate();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json([
                    'status' => 404,
                    'message' => 'You are not an User'
                ], 404);
            }
            if (!$currentUser) {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
            $book = $currentUser->carts()->find($id);
            if (!$book) {
                Log::error('Book Not Found', ['id' => $request->id]);
                return response()->json(['message' => 'Book not Found in cart'], 404);
            }

            if ($book->delete()) {
                Log::info('book deleted', ['user_id' => $currentUser, 'book_id' => $request->id]);
                Cache::forget('carts');
                return response()->json(['message' => 'Book deleted Sucessfully from cart'], 201);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }


    /**
     * @OA\Get(
     *   path="/api/getAllBooksByUserId",
     *   summary="Get All Books Present in Cart",
     *   description=" Get All Books Present in Cart ",
     *   @OA\RequestBody(
     *
     *    ),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    public function getAllBooksByUserId()
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'Your are not an User'], 404);
            }
            if ($currentUser) {
                $books = new Cart();
                Log::info('All book Presnet in cart are fetched');
                return response()->json([
                    'message' => 'Books Present in Cart :',
                    'Cart' => $books->getBooks($currentUser)

                ], 201);
                if ($books == '[]') {
                    Log::error('Book Not Found');
                    return response()->json(['message' => 'Books not found'], 404);
                }
            } else {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/increamentBookQuantityInCart",
     *   summary="Add Quantity to Existing Book in cart",
     *   description=" Add Book Quantity  in cart",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Book Quantity increament Successfully"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function increamentBookQuantityInCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 404);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $cart = new Cart();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'You are not an User'], 404);
            }
            if (!$currentUser) {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
            $cart = Cart::find($request->id);

            if (!$cart) {
                return response()->json([
                    'message' => 'Item Not found with this id'
                ], 404);
            }
            $cart->book_quantity += 1;
            $cart->save();
            Log::info('Book Quantity increament Successfully to the bookstore cart');
            return response()->json([
                'message' => 'Book Quantity increament Successfully'
            ], 201);
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/decreamentBookQuantityInCart",
     *   summary="Decrese the Quantity of Existing Book in cart",
     *   description=" Decrese Book Quantity  in cart",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Book Quantity decreament Successfully"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function decreamentBookQuantityInCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $cart = new Cart();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (!$userId) {
                return response()->json(['message' => 'You are not an User'], 404);
            }
            if (!$currentUser) {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
            $cart = Cart::find($request->id);

            if (!$cart) {
                return response()->json([
                    'message' => 'Item Not found with this id'
                ], 404);
            }
            $cart->book_quantity -= 1;
            $cart->save();
            if ($cart->book_quantity == 0) {
                $cart->delete();
                return response()->json([
                    'message' => 'Book Successfully remove from cart'
                ], 201);
            }
            Log::info('Book Quantity decreament Successfully to the bookstore cart');
            return response()->json([
                'message' => 'Book Quantity decreament Successfully'
            ], 201);
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }


    /**
     * @OA\Post(
     *   path="/api/addBookToCartBywishlist",
     *   summary="Add the book to Cart from wishlist",
     *   description=" Add to cart ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"wishlist_id"},
     *               @OA\Property(property="wishlist_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Book added to Cart Sucessfully"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function addBookToCartBywishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wishlist_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $cart = new Cart();
            $book = new Book();
            $user = new User();

            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'You are not an User'], 404);
            }
            if ($currentUser) {
                $wishlist = Wishlist::where('id', $request->wishlist_id)->first();
                $book_id = $wishlist['book_id'];
                $book_existance = $book->findBook($book_id);

                if (!$book_existance) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Book Not Found'
                    ], 404);
                }
                $books = Book::find($book_id);
                if ($books->quantity == 0) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'OUT OF STOCK'
                    ], 404);
                }
                $book_cart = $cart->bookCart($book_id, $currentUser->id);

                if ($book_cart) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Book already added in cart'
                    ], 404);
                }

                $cart->book_id = $wishlist['book_id'];

                if ($currentUser->carts()->save($cart)) {
                    $wishlist->delete();
                    Cache::remember('carts', 3600, function () {
                        return DB::table('carts')->get();
                    });
                    return response()->json([
                        'message' => 'Book added to Cart Sucessfully'
                    ], 201);
                }

                return response()->json(['message' => 'Book cannot be added to Cart'], 405);
            } else {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }
}
