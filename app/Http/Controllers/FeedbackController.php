<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
use App\Models\Book;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class FeedbackController extends Controller
{

     /**
     * @OA\Post(
     *   path="/api/feedback",
     *   summary="Feedback of User",
     *   description=" Feedback ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"User_feedback"},
     *               @OA\Property(property="User_feedback", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Thank you for your Feedback Your feedback is very important to us...."),
     *   @OA\Response(response=404, description="Your are not an User"),
     *   @OA\Response(response=401, description="Invalid Authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    /**
     * Function takes users feedback and a valid Authenticate token as an input
     * and fetch the feedback in the bookstore database and take user feedback 
     * and only user can give feedback.
     */
    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'User_feedback' => 'required|string|between:3,1000',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'Your are not an User'], 404);
            }
            $feedback = new Feedback();
            $feedback->saveFeedback($request,$currentUser)->save();

            if (!$feedback){
                throw new BookStoreException("Invalid Authorization token ", 401);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
        Log::info('feedback created', ['user_id' => $feedback->user_id]);
        return response()->json([
            'status' => 200,
            'message' => 'Thank you for your Feedback Your feedback is very important to us....'
        ]);
        
    }

    /**
     * @OA\Post(
     *   path="/api/bookRating",
     *   summary="Rating of Book",
     *   description=" Rating of Book ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"book_id", "rating"},
     *               @OA\Property(property="book_id", type="integer"),
     *               @OA\Property(property="rating", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="rating successfully added to book"),
     *   @OA\Response(response=404, description="Your are not an User"),
     *   @OA\Response(response=401, description="Invalid Authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    /**
     * Function takes rating for book and a valid Authenticate token as an input
     * and fetch the rating in the bookstore database and take user rating of book 
     * and only user can give rating.
     */
    public function bookRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer',
            'rating' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $user = new User();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'Your are not an User'], 404);
            }
            $rating = new feedback();
            if ($request->input('rating')>5)
            {
                return response()->json(['message' => 'Invalid rating give between 1 To 5']);
            }
            $rating->saveRating($request, $currentUser)->save();

            if (!$rating){
                throw new BookStoreException("Invalid Authorization token ", 401);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
        return response()->json([
            'status' => 200,
            'message' => 'rating successfully added to book'
        ]);
    }


    /**
     * @OA\Post(
     *   path="/api/displayAvgRatingOfBook",
     *   summary="Display Average rating",
     *   description="Display Average rating",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"book_id",},
     *               @OA\Property(property="book_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Average rating of Book :"),
     *   @OA\Response(response=404, description="Your are not an User"),
     *   @OA\Response(response=401, description="Invalid Authorization token"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    /**
     * Function takes book_id and a valid Authenticate token as an input
     * and display average ratings of book and only user can view average
     * rating of book 
     */
    public function displayAvgRatingOfBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            $user = new User();
            $book = new Book();
            $userId = $user->userVerification($currentUser->id);
            if (count($userId) == 0) {
                return response()->json(['message' => 'Your are not an User'], 404);
            }
            if($currentUser) {
                $book_id = $request->input('book_id');
                $book_existance = $book->findBook($book_id);
                if (!$book_existance) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Book Not Found'
                    ], 404);
                }
                $rating = new feedback();
                return response()->json([
                    'message' => 'Average rating of Book '.$book_id.  ':',
                    'Average Rating' => $rating->avgRating($book_id)
                ], 201); 
            }
            else {
                Log::error('Invalid User');
                throw new BookStoreException("Invalid authorization token", 404);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }
}
