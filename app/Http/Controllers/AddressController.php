<?php

namespace App\Http\Controllers;

use App\Exceptions\BookStoreException;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AddressController extends Controller
{

    /**
     * @OA\Post(
     *   path="/api/addAddress",
     *   summary="Add Address",
     *   description="User Can Add Address ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"address","city","state","landmark", "pincode", "address_type"},
     *               @OA\Property(property="address", type="string"),
     *               @OA\Property(property="city", type="string"),
     *               @OA\Property(property="state", type="string"),
     *               @OA\Property(property="landmark", type="string"),
     *               @OA\Property(property="pincode", type="integer"),
     *               @OA\Property(property="address_type", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Address Added Successfully"),
     *   @OA\Response(response=401, description="Address alredy present for the user"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    /**
     * This AddAddress function is use to add Address of the User
     */
    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|between:2,600',
            'city' => 'required|string|between:2,100',
            'state' => 'required|string|between:2,100',
            'landmark' => 'required|string|between:2,100',
            'pincode' => 'required|integer',
            'address_type' => 'required|string|between:2,100',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser) {
                $address = new Address();
                $address->addressDetails($request, $currentUser)->save();
                Log::info('Address Added To Respective User', ['user_id', '=', $currentUser->id]);
                return response()->json([
                    'message' => 'Address Added Successfully'
                ], 201);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }


    /**
     * @OA\Post(
     *   path="/api/updateAddress",
     *   summary="Update Address",
     *   description="User Can Update Address ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","address","city","state","landmark", "pincode", "address_type"},
     *               @OA\Property(property="id", type="integer"),
     *               @OA\Property(property="address", type="string"),
     *               @OA\Property(property="city", type="string"),
     *               @OA\Property(property="state", type="string"),
     *               @OA\Property(property="landmark", type="string"),
     *               @OA\Property(property="pincode", type="integer"),
     *               @OA\Property(property="address_type", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Address Updated Successfully"),
     *   @OA\Response(response=401, description="Address not present add address first"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     * */
    public function updateAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'address' => 'required|string|between:2,600',
            'city' => 'required|string|between:2,100',
            'state' => 'required|string|between:2,100',
            'landmark' => 'required|string|between:2,100',
            'pincode' => 'required|integer',
            'address_type' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser) {
                $address = new Address();
                $address_exist = $address->addressExist($request->id);

                if (!$address_exist) {
                    Log::error('Address is empty');
                    throw new BookStoreException("Address not present add address first", 401);
                }

                $address_exist->fill($request->all());
                if ($address_exist->save()) {
                    Log::info('Address Updated For Respective User', ['user_id', '=', $currentUser->id]);
                    return response()->json([
                        'message' => ' Address Updated Successfully'
                    ], 201);
                }
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }


    /**
     * @OA\Post(
     *   path="/api/deleteAddress",
     *   summary="Delete Address",
     *   description=" Delete Address ",
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
     */
    public function deleteAddress(Request $request)
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
            $address = new Address();
            $address_exist = $address->addressExist($id);

            if (!$address_exist) {
                throw new BookStoreException('User not Found', 404);
            }

            if ($address_exist->delete()) {
                Log::info('Address Deleted For Respective User', ['user_id', '=', $currentUser->id]);
                return response()->json(['message' => 'Address deleted Sucessfully'], 201);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }


    /**
     * @OA\Get(
     *   path="/api/getAddress",
     *   summary="Get address ",
     *   description=" Get Address ",
     *   @OA\RequestBody(
     *
     *    ),
     *   @OA\Response(response=404, description="Address not found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function getAddress()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        try {
            if ($currentUser) {
                $address = new Address();
                $user = $address->userAddress($currentUser->id);

                if ($user == []) {
                    throw new BookStoreException("Address not found", 404);
                }
                Log::info('Address fetched For Respective User', ['user_id', '=', $currentUser->id]);
                return response()->json([
                    'address' => $user,
                    'message' => 'Fetched Address Successfully'
                ], 201);
            }
        } catch (BookStoreException $exception) {
            return $exception->message();
        }
    }
}
