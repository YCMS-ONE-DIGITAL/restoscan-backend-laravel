<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Controllers
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RestaurantTablesController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\OrdersPublicController;

// Public Routes
Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/user/login', [LoginController::class, 'login']);

// Protected Routes
Route::middleware('auth.token')->group(function () {

    // Restaurant
    Route::post('/restaurant/add', [RestaurantController::class, 'store']);
    Route::get('/restaurant', [RestaurantController::class, 'show']);
    Route::post('/restaurant/update', [RestaurantController::class, 'update']);

    // Menus
    Route::get('/restaurant/menus', [MenuController::class, 'index']);
    Route::post('/restaurant/menus', [MenuController::class, 'store']);
    Route::get('/restaurant/menus/{id}', [MenuController::class, 'show']);
    Route::post('/restaurant/menus/update/{id}', [MenuController::class, 'update']);
    Route::delete('/restaurant/menus/{id}', [MenuController::class, 'destroy']);

    // Categories
    Route::post('/restaurant/category/add', [CategoryController::class, 'store']);
    Route::get('/restaurant/categories', [CategoryController::class, 'index']);
    Route::post('/restaurant/category/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/restaurant/categories/{id}', [CategoryController::class, 'destroy']);

    // Tables
    Route::post('/restaurant/table/add', [RestaurantTablesController::class, 'store']);
    Route::get('/restaurant/table/list', [RestaurantTablesController::class, 'index']);
    Route::get('/restaurant/table/details', [RestaurantTablesController::class, 'show']);
    Route::post('/restaurant/table/update', [RestaurantTablesController::class, 'update']);
    Route::delete('/restaurant/table/delete/{id}', [RestaurantTablesController::class, 'destroy']);

    // Menu Items
    Route::post('/restaurant/menu/item/add', [MenuItemController::class, 'store']);
    Route::get('/restaurant/menu/item/list', [MenuItemController::class, 'fetch_menu_items_list']); 
    Route::get('/restaurant/menu/item/{id}', [MenuItemController::class, 'fetch_menu_item']);
    Route::post('/restaurant/menu/item/update/{id}', [MenuItemController::class, 'update_menu_item']);
    Route::delete('/restaurant/menu/item/{id}', [MenuItemController::class, 'destroy']);
    Route::get('/restaurant/menu/item/list/all', [MenuItemController::class, 'fetch_all_items']);
    Route::post('/restaurant/menu/item/upload-image', [MenuItemController::class, 'uploadImage']);

    // Orders
    Route::post('/restaurant/orders/create', [OrdersController::class, 'createOrderWithItems']);
    Route::get('/restaurant/orders/fetch', [OrdersController::class, 'fetchOrder']);
    Route::get('/restaurant/orders/fetchall', [OrdersController::class, 'fetch_all_orders']);
    Route::post('/restaurant/orders/update', [OrdersController::class, 'update_order']);

    // AUTH CHECK (VERY IMPORTANT)
    Route::get('/user/me', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'user' => $request->auth_user,
        ]);
    });
});


Route::get('/public/menu/items', [MenuItemController::class, 'public_menu_items']);

// PUBLIC Order Create API (NO AUTH REQUIRED)
Route::post('/public/order/create', [OrdersPublicController::class, 'create']);


// PUBLIC CATEGORY API (customer website)
Route::get('/public/categories', function (Request $request) {

    $restaurantId = $request->query('restaurant_id');

    if (!$restaurantId) {
        return response()->json(['message' => 'restaurant_id required'], 422);
    }

    return \App\Models\Category::where('restaurant_id', $restaurantId)->get();
});
