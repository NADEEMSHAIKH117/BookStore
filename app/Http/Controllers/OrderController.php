<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
use App\Models\Address;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Notifications\sendOrderDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderController extends Controller
{


    /**
     * @OA\Post(
     *   path="/api/placeOrder",
     *   summary="Place  Order",
     *   description=" Place a order ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"address_id","name", "quantity"},
     *               @OA\Property(property="address_id", type="integer"),
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="quantity", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Order Successfully Placed..."),
     *   @OA\Response(response=401, description="We Do not have this book in the store..."),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required',
            'name' => 'required',
            'quantity' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser) {
                $book = new Book();
                $address = new Address();
                $bookDetails = $book->getBookDetails($request->input('name'));
                if ($bookDetails == '') {
                    Log::error('Book is not available');
                    throw new BookStoreException("We Do not have this book in the store...", 401);
                }

                if ($bookDetails['quantity'] < $request->input('quantity')) {
                    Log::error('Book stock is not available');
                    throw new BookStoreException("This much stock is unavailable for the book", 401);
                }

                //getting addressID
                $getAddress = $address->addressExist($request->input('address_id'));
                if (!$getAddress) {
                    throw new BookStoreException("This address id not available", 401);
                }

                //calculate total price
                $total_price = $request->input('quantity') * $bookDetails['Price'];
                $order = new Order();
                $order = Order::create([
                    'user_id' => $currentUser->id,
                    'book_id' => $bookDetails['id'],
                    'address_id' => $getAddress['id'],
                    'order_id' => $this->unique_code(9),
                ]);
                $order->order($currentUser, $bookDetails, $getAddress);
                $userId = User::where('id', $currentUser->id)->first();

                $delay = now()->addSeconds(5);
                $userId->notify((new sendOrderDetails($order->order_id, $bookDetails['name'], $bookDetails['author'], $request->input('quantity'), $total_price))->delay($delay));

                $bookDetails['quantity'] -= $request->quantity;
                $bookDetails->save();
                return response()->json([
                    'message' => 'Order Successfully Placed...',
                    'OrderId' => $order->order_id,
                    'Quantity' => $request->input('quantity'),
                    'Total_Price' => $total_price,
                    'message' => 'Mail sent to the user with all details',
                ], 201);
                Cache::remember('orders', 3600, function () {
                    return DB::table('orders')->get();
                });
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }
    /**
     * base_convert – Convert a number between arbitrary bases
     * sha1 – Calculate the sha1 hash of a string.
     * uniqid – Generate a unique ID.
     * mt_rand – Generate a random value via the Mersenne Twister Random Number Generator.
     */
    function unique_code($limit)
        {
            return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
        }
}
