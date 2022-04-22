<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api'], function () {
    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('logout', [UserController::class, 'logout']);

    Route::post('forgotpassword', [ForgotPasswordController::class, 'forgotPassword']);
    Route::post('resetPassword', [ForgotPasswordController::class, 'resetPassword']);

    Route::post('addBook', [BookController::class, 'addBook']);
    Route::post('updateBookByBookId', [BookController::class, 'updateBookByBookId']);
    Route::post('addQuantityToExistingBook', [BookController::class, 'addQuantityToExistingBook']);
    Route::post('deleteBookByBookId', [BookController::class, 'deleteBookByBookId']);
    Route::get('getAllBooks', [BookController::class, 'getAllBooks']);
    Route::post('searchByEnteredKeyWord', [BookController::class, 'searchByEnteredKeyWord']);
    Route::get('sortOnPriceLowToHigh', [BookController::class, 'sortOnPriceLowToHigh']);
    Route::get('sortOnPriceHighToLow', [BookController::class, 'sortOnPriceHighToLow']);

    Route::post('addtocart', [CartController::class, 'addBookToCartByBookId']);
    Route::post('deleteBookByCartId', [CartController::class, 'deleteBookByCartId']);
    Route::get('getAllBooksByUserId', [CartController::class, 'getAllBooksByUserId']);
    Route::post('increamentBookQuantityInCart', [CartController::class, 'increamentBookQuantityInCart']);
    Route::post('decreamentBookQuantityInCart', [CartController::class, 'decreamentBookQuantityInCart']);
    Route::post('addBookToCartBywishlist', [CartController::class, 'addBookToCartBywishlist']);


    Route::post('addBookToWishlistBybookId', [WishlistController::class, 'addBookToWishlistBybookId']);
    Route::post('deleteBookByWishlistId', [WishlistController::class, 'deleteBookByWishlistId']);
    Route::get('getAllBooksInWishlist', [WishlistController::class, 'getAllBooksInWishlist']);

    Route::post('addAddress',[AddressController::class, 'addAddress']);
    Route::post('updateAddress',[AddressController::class, 'updateAddress']);
    Route::post('deleteAddress',[AddressController::class, 'deleteAddress']);
    Route::get('getAddress',[AddressController::class, 'getAddress']);

    Route::post('placeOrder',[OrderController::class, 'placeOrder']);

    Route::post('feedback',[FeedbackController::class, 'feedback']);
    Route::post('bookRating',[FeedbackController::class, 'bookRating']);
    Route::post('displayAvgRatingOfBook',[FeedbackController::class, 'displayAvgRatingOfBook']);

});
