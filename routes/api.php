<?php

use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BlogSectionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DeliveryPincodeController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\RazorpayController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::get('testimonials', [TestimonialController::class, 'publicIndex']);
Route::get('blogs', [BlogController::class, 'publicIndex']);
Route::get('blogs/{id}', [BlogController::class, 'publicShow']);
Route::get('categories', [CategoryController::class, 'publicIndex']);
Route::get('colors', [ColorController::class, 'publicIndex']);
Route::get('clients', [ClientController::class, 'publicIndex']);
Route::get('products/{product}/check-pincode/{pincode}', [ProductController::class, 'checkPincode']);
Route::get('products/{id}', [ProductController::class, 'publicShow']);
Route::get('products', [ProductController::class, 'publicIndex']);
Route::get('delivery-pincodes', [DeliveryPincodeController::class, 'publicIndex']);
Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::controller(CartController::class)->group(function () {
    Route::get('cart', 'index');
    Route::post('cart', 'store');
    Route::delete('cart/clear', 'clear');
    Route::put('cart/items/{item}', 'update');
    Route::delete('cart/items/{item}', 'destroy');
});
Route::middleware('check.status')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});
Route::middleware(['auth:sanctum', 'check.status'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('testimonials', TestimonialController::class);
        Route::apiResource('blogs', BlogController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('colors', ColorController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('delivery-pincodes', DeliveryPincodeController::class);
    });
    Route::get('user', [AuthController::class, 'user']);
    Route::post('user/update-profile', [AuthController::class, 'updateProfile'])
        ->name('update-profile');
    Route::get('user/addresses', [AuthController::class, 'addresses']);
    Route::put('user/addresses', [AuthController::class, 'updateAddresses']);


    Route::prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('{order}', 'show');
        Route::get('cancel/{order}', 'cancel');
    });
    Route::post('razorpay/create-order', [RazorpayController::class, 'createOrder']);
    Route::post('razorpay/verify-payment', [RazorpayController::class, 'verifyPayment']);
});
