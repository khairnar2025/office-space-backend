<?php

use App\Http\Controllers\Api\Admin\TestimonialController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::middleware('check.status')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('testimonials', [TestimonialController::class, 'publicIndex']);
});
Route::middleware(['auth:sanctum', 'check.status'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('testimonials', TestimonialController::class);
    });
    Route::get('user', [AuthController::class, 'user']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])
        ->name('update-profile');
});
