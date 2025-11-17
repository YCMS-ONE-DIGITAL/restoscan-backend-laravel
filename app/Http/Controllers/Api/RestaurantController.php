<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    // Get logged user from auth_user middleware
    private function getUser(Request $request)
    {
        return $request->get('auth_user');
    }

    /**
     * Add restaurant for logged-in user
     */
    public function store(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Check if user already has restaurant
        if (Restaurant::where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant already added for this user'
            ], 400);
        }

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'contact_number' => 'nullable|string|max:15',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => $validated['restaurant_name'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant details saved successfully!',
            'restaurant' => $restaurant
        ]);
    }

    /**
     * Show restaurant details for logged-in user
     */
    public function show(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['status' => 'error', 'message' => 'No restaurant found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'restaurant' => $restaurant
        ]);
    }

    /**
     * Update restaurant details
     */
    public function update(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json(['status' => 'error', 'message' => 'No restaurant found'], 404);
        }

        $validated = $request->validate([
            'restaurant_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'contact_number' => 'nullable|string|max:15',
        ]);

        $restaurant->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant details updated successfully!',
            'restaurant' => $restaurant
        ]);
    }
}
