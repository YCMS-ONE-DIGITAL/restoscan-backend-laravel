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
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ForgetPasswordController;
use App\Http\Controllers\Api\PublicOtpController;
use App\Http\Controllers\Api\StaffController;

// Public Routes
Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/user/login', [LoginController::class, 'login']);
Route::get('/user/logout', [LoginController::class, 'logout']);
       
    // STAFF LOGIN + LOGOUT
    Route::post('/restaurant/staff/login', [StaffController::class, 'login']);  
    Route::post('/restaurant/staff/logout', [StaffController::class, 'logout']);


            // forget password
Route::post('/forgot-password/send-otp', [ForgetPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ForgetPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [ForgetPasswordController::class, 'resetPassword']);


Route::post('/staff/login', [StaffController::class, 'login']);

Route::middleware('staff.auth')->group(function () {
    Route::post('/staff/logout', [StaffController::class, 'logout']);
    // TABLE LIST
    Route::get('/tables', [StaffController::class, 'tablelist']);

    // UPDATE TABLE STATUS
    Route::post(
        '/tables/{id}/status',
        [StaffController::class, 'updateTableStatus']
    );
    // Route::get('/staff/list', [StaffController::class, 'Staff_List']);
        Route::get('/staff/categories', [StaffController::class, 'categories']);
        Route::get('/staff/menuitemlist', [StaffController::class, 'fetch_menu_items_list']);
        Route::post('/staff/place-order', [StaffController::class, 'placeOrder']);
        Route::get('/staff/fetchallorder', [StaffController::class, 'fetch_all_orders']);

});


// Protected Routes
Route::middleware('auth.token')->group(function () {
       
    Route::post('/restaurant/staff/add', [StaffController::class, 'store']);
    Route::get('/restaurant/staff/all', [StaffController::class, 'Staff_List']);
    Route::post('/restaurant/staff/update/{id}', [StaffController::class, 'update']);
    Route::get('/restaurant/staff/fetchone/{id}', [StaffController::class, 'show']);
    Route::delete('/restaurant/staff/delete/{id}', [StaffController::class, 'destroy']);

    // dashboard 
        Route::post('/user/change-password', [LoginController::class, 'changePassword']);
        Route::post('/user/update', [LoginController::class, 'update']);

    // Restaurant
    Route::get('/restaurant/check',[RestaurantController::class,'check']);
    Route::post('/restaurant/store', [RestaurantController::class, 'store']);
    Route::get('/restaurant/show', [RestaurantController::class, 'show']);
    Route::post('/restaurant/update', [RestaurantController::class, 'update']);

    // paymenthistory
    Route::get('/restaurant/paymenthistory', [OrdersController::class, 'paymenthistory']);

    // Menus
    Route::get('/restaurant/menus', [MenuController::class, 'index']);
    Route::post('/restaurant/menus/add', [MenuController::class, 'store']);
    Route::get('/restaurant/menus/{id}', [MenuController::class, 'show']);
    Route::post('/restaurant/menus/update/{id}', [MenuController::class, 'update']);
    Route::delete('/restaurant/menus/delete/{id}', [MenuController::class, 'destroy']);

    // Categories
    Route::post('/restaurant/category/add', [CategoryController::class, 'store']);
    Route::get('/restaurant/categories', [CategoryController::class, 'index']);
    Route::post('/restaurant/category/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/restaurant/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/restaurant/category/upload-image', [CategoryController::class, 'uploadImage']);


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
     Route::get('restaurant/orders/{order}/bill', [OrdersController::class, 'generateBill']);
     Route::get('/restaurant/orders/filter', [OrdersController::class, 'filterOrders']);



    //dashboard
    Route::get('/restaurant/dashboard/stats', [OrdersController::class, 'dashboardStats']);

    // AUTH CHECK (VERY IMPORTANT)
    Route::get('/user/me', function (Request $request) {
        return response()->json([
            'status' => 'success',
        'user' => $request->attributes->get('auth_user'), // ✔ Correct
        ]);
    });


    // customers
    Route::get('/restaurant/customers', [CustomerController::class, 'customers']);
    Route::get('/restaurant/customer/{id}/orders', [CustomerController::class, 'orderHistory']);



    // staffs



});



Route::get('/public/menu/items', [MenuItemController::class, 'public_menu_items']);

// -----------------------------
// ⭐ OTP APIS
// -----------------------------

// Send OTP to user's mobile
Route::post('/public/send-otp', [PublicOtpController::class, 'sendOtp']);
// Verify OTP entered by user
Route::post('/public/verify-otp', [PublicOtpController::class, 'verifyOtp']);


// -----------------------------
// ⭐ PUBLIC ORDER APIS
// -----------------------------

// Create order (OTP verified required)
Route::post('/public/order', [OrdersPublicController::class, 'create']);

// Get customer order history
Route::post('/public/order-history', [OrdersPublicController::class, 'publicOrderHistory']);


// PUBLIC CATEGORY API (customer website)
Route::get('/public/categories', function (Request $request) {

    $restaurantId = $request->query('restaurant_id');

    if (!$restaurantId) {
        return response()->json(['message' => 'restaurant_id required'], 422);
    }

    return \App\Models\Category::where('restaurant_id', $restaurantId)->get();
});


