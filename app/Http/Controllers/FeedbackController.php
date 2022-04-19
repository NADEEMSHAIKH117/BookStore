<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
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
            $feedback->User_feedback = $request->input('User_feedback');
            $feedback->user_id = Auth::user()->id;
            $feedback->save();

            if (!$feedback){
                throw new BookStoreException("Invalid Authorization token ", 401);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
        Log::info('feedback created', ['user_id' => $feedback->user_id]);
        return response()->json([
            'status' => 201,
            'message' => 'Thank you for your Feedback Your feedback is very important to us....'
        ]);
    }
}
