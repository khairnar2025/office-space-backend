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

        // Route::delete('blogs/{blog}/sections/{section}', [BlogSectionController::class, 'destroy']);
        // Route::delete('blogs/{blog}/sections/{section}/attachment', [BlogSectionController::class, 'deleteAttachment']);
    });
    Route::get('user', [AuthController::class, 'user']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])
        ->name('update-profile');
});
