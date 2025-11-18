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
    Route::post('/restaurant/category/update/{id}', [CategoryController::class, 'update']);
    // restaurant table api 
   // Add new seating table
Route::post('/restaurant/table/add', [RestaurantTablesController::class, 'store']);

// Fetch all tables (list)
Route::get('/restaurant/table/list', [RestaurantTablesController::class, 'index']);

// Fetch one table
Route::get('/restaurant/table/details', [RestaurantTablesController::class, 'show']);

// Update table
Route::post('/restaurant/table/update', [RestaurantTablesController::class, 'update']);
Route::delete('/restaurant/table/delete/{id}', [RestaurantTablesController::class, 'destroy']);


// restaurant menuitems api
     // Menu Items CRUD
    Route::post('/restaurant/menu/item/add', [MenuItemController::class, 'store']);
    Route::get('/restaurant/menu/item/list', [MenuItemController::class, 'fetch_menu_items_list']);
    Route::get('/restaurant/menu/item/{id}', [MenuItemController::class, 'fetch_menu_item']);
    Route::post('/restaurant/menu/item/update/{id}', [MenuItemController::class, 'update_menu_item']);
    Route::delete('/restaurant/menu/item/{id}', [MenuItemController::class, 'destroy']);
    Route::get('/restaurant/menu/item/list/all', [MenuItemController::class, 'fetch_all_items']);

    
Route::post('/order/create', [OrdersController::class, 'createOrderWithItems']);
Route::get('/order/fetch', [OrdersController::class, 'fetchOrder']);
Route::get('/order/fetchall', [OrdersController::class, 'fetch_all_orders']);
Route::post('/order/update', [OrdersController::class, 'update_order']);



  
});