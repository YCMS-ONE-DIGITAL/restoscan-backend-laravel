<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RestaurantTablesController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrdersController;



Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/user/login', [LoginController::class, 'login']);
Route::middleware('auth.token')->group(function () {
    // restaurant crud api

    Route::post('/restaurant/add', [RestaurantController::class, 'store']);
    Route::get('/restaurant', [RestaurantController::class, 'show']);
    Route::post('/restaurant/update', [RestaurantController::class, 'update']);
        // restaurant menu api
 // Menus CRUD
Route::get('/restaurant/menus', [MenuController::class, 'index']);
Route::post('/restaurant/menus', [MenuController::class, 'store']);
Route::get('/restaurant/menus/{id}', [MenuController::class, 'show']);
Route::post('/restaurant/menus/update/{id}', [MenuController::class, 'update']);
Route::delete('/restaurant/menus/{id}', [MenuController::class, 'destroy']);


    // restaurant categories api 

 Route::post('/restaurant/category/add', [CategoryController::class, 'store']);
    Route::get('/restaurant/categories', [CategoryController::class, 'index']);
    Route::post('/restaurant/category/update', [CategoryController::class, 'update']);
    // restaurant table api 
    Route::post('/restaurant/seating/add', [RestaurantTablesController::class, 'store']);
Route::get('/restaurant/seating', [RestaurantTablesController::class, 'index']);
Route::get('/restaurant/seating', [RestaurantTablesController::class, 'show']);
Route::post('/restaurant/seating/update', [RestaurantTablesController::class, 'update']);

// restaurant menuitems api
    Route::post('/restaurant/menu/item/add', [MenuItemController::class, 'store']);
    Route::get('/restaurant/menu/item/fetchall', [MenuItemController::class, 'fetch_menu_items_list']);
    Route::get('/restaurant/menu/item', [MenuItemController::class, 'fetch_menu_item']);
    Route::get('/restaurant/menu/item/update', [MenuItemController::class, 'update_menu_item']);
Route::post('/order/create', [OrdersController::class, 'createOrderWithItems']);

Route::get('/order/fetch', [OrdersController::class, 'fetchOrder']);
Route::get('/order/fetchall', [OrdersController::class, 'fetch_all_orders']);

Route::post('/order/update', [OrdersController::class, 'update_order']);


  
});