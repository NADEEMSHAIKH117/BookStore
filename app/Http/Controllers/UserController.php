<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\BookStoreException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    /**
     * @OA\Post(
     *   path="/api/register",
     *   summary="register",
     *   description="register the user for login",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"role","firstname","lastname","phone_no","email", "password", "confirm_password"},
     *               @OA\Property(property="role", type="string"),
     *               @OA\Property(property="firstname", type="string"),
     *               @OA\Property(property="lastname", type="string"),
     *               @OA\Property(property="phone_no", type="string"),
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="password"),
     *               @OA\Property(property="confirm_password", type="password")
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     * )
     * It takes a POST request and requires fields for the user to register,
     * and validates them if it is validated,creates those values in DB
     * and returns success response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|string|between:2,10',
                'firstname' => 'required|string|between:2,50',
                'lastname' => 'required|string|between:2,50',
                'phone_no' => 'required|string|min:10',
                'email' => 'required|string|email|max:100',
                'password' => 'required|string|min:6',
                'confirm_password' => 'required|same:password',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $userObject = new User();

            $user = $userObject->userEmailValidation($request->email);
            if ($user) {
                throw new BookStoreException("The email has already been taken", 401);
            }

            $userObject->saveUserDetails($request);
            Log::info('Registered user Email : ' . 'Email Id :' . $request->email);
            Cache::remember('users', 3600, function () {
                return DB::table('users')->get();
            });

            return response()->json([
                'status' => 201,
                'message' => 'User Successfully Registerd',
            ], 201);
        } catch (BookStoreException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }


    /**
     * @OA\Post(
     *   path="/api/login",
     *   summary="login",
     *   description=" login ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "password"},
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="password", type="password"),
     *            ),
     *        ),
     *    ),
     * @OA\Response(response=200, description="Login successfull"),
     * @OA\Response(response=401, description="email not found register first"),
     * 
     * )
     * Takes the POST request and user credentials checks if it correct,
     * if so, returns JWT access token.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            Cache::remember('users', 3600, function () {
                return User::all();
            });

            $userObject = new User();
            $user = $userObject->userEmailValidation($request->email);
            if (!$user) {
                Log::error('user faild to login', ['id' => $request->email]);
                throw new BookStoreException("email not found register first", 401);
            }

            if (!$token = auth()->attempt($validator->validated())) {
                throw new BookStoreException("Invalid Credentials", 401);
            }

            Log::info('Login Success : ' . 'Email Id :' . $request->email);
            return response()->json([
                'status' => 200,
                'access_token' => $token,
                'message' => 'Login successfull'
            ], 200);
        } catch (BookStoreException $exception) {
            Log::error('Invalid User');
            return $exception->message();
        }
    }

    /**
     * Takes the POST request and JWT access token to logout the user profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *   path="/api/logout",
     *   summary="logout",
     *   description=" logout ",
     *  @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"token",},
     *               @OA\Property(property="token", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="User successfully signed out"),
     * )
     */
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'status' => 200,
            'message' => 'User successfully signed out'
        ], 200);
    }
}
