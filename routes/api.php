<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\MenuController;

Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/user/login', [LoginController::class, 'login']);
Route::middleware('auth.token')->group(function () {
    Route::post('/restaurant/add', [RestaurantController::class, 'store']);
    Route::get('/restaurant', [RestaurantController::class, 'show']);
    Route::post('/restaurant/update', [RestaurantController::class, 'update']);
    Route::post('/restaurant/menu/add', [MenuController::class, 'store']);
    Route::get('/restaurant/menu', [MenuController::class,'index']);
    Route::post('/restaurant/menu/update', [MenuController::class, 'update']); // Update menu (using ?menu_id)
    Route::get('/restaurant/menu', [MenuController::class,'show']);
});
