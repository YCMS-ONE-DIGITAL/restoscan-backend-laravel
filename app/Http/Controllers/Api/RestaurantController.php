<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\User;

class RestaurantController extends Controller
{
    // 🔹 Add Restaurant Details
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'contact_number' => 'nullable|string|max:15',
        ]);

        // ✅ Get logged-in user via token
        $token = $request->bearerToken();
        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized — Invalid or missing token',
            ], 401);
        }

        // ✅ Check if this user already has a restaurant
        if (Restaurant::where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant already added for this user',
            ], 400);
        }

        // ✅ Create restaurant record
        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => $request->restaurant_name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'contact_number' => $request->contact_number,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant details saved successfully!',
            'restaurant' => $restaurant,
        ]);
    }

    // 🔹 View Restaurant Details
    public function show(Request $request)
    {
        $token = $request->bearerToken();
        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['status' => 'error', 'message' => 'No restaurant found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'restaurant' => $restaurant,
        ]);
    }



    // 🔹 update Restaurant Details
   public function update(Request $request)
    {
    $token = $request->bearerToken();
    $user = User::where('remember_token', $token)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized — Invalid or missing token',
        ], 401);
    }

    $restaurant = Restaurant::where('user_id', $user->id)->first();

    if (!$restaurant) {
        return response()->json([
            'status' => 'error',
            'message' => 'No restaurant found for this user',
        ], 404);
    }

    $request->validate([
        'restaurant_name' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'pincode' => 'nullable|string|max:10',
        'contact_number' => 'nullable|string|max:15',
    ]);

    // ✅ safer update
    $restaurant->fill($request->all());
    $restaurant->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Restaurant details updated successfully!',
        'restaurant' => $restaurant
    ]);
}


}
