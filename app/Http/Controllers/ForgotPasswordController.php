<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);
            if (!$user) {
                Log::error('Email not found.', ['id' => $request->email]);
                throw new BookStoreException("can not find a user with this email address", 400);
            }
            $token = Auth::fromUser($user);
            if ($user) {
                $delay = now()->addSecond(20);
                $user->notify((new PasswordResetRequest($user->email, $token))->delay($delay));
            }
            Log::info('Forgot PassWord Link : ' . 'Email Id :' . $request->email);
            return response()->json([
                'status' => 200,
                'message' => 'Password reset link has send to your Email id kindly check it'
            ], 200);
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'min:6|required|',
            'confirm_password' => 'required|same:new_password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => "Password doesn't match"
            ], 400);
        }
        try {
            $currentUser = Auth::user();
            $userObject = new User();
            $user = $userObject->userEmailValidation($currentUser->email);
            if (!$user) {
                Log::error('Email not Found,', ['id' => $request->email]);
                throw new BookStoreException("can not find a user with this email address", 400);
            } else {
                $user->password = bcrypt($request->new_password);
                $user->save();
                Log::info('Reset Successful : ' . 'Email Id :' . $request->email);
                return response()->json([
                    'status' => 201,
                    'message' => 'Password reset successfull!'
                ], 201);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }
}
