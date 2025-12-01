<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    private function getUser(Request $request)
    {
        return $request->get('auth_user');
    }

    public function check(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json([
                'has_restaurant' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $exists = Restaurant::where('user_id', $user->id)->exists();

        return response()->json([
            'has_restaurant' => $exists
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        if (Restaurant::where('user_id', $user->id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Restaurant already exists'], 400);
        }

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'contact_number' => 'nullable|string|max:15',
            'logo_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $logoPath = null;

        if ($request->hasFile('logo_url')) {
            $file = $request->file('logo_url');
            $filename = time().'_'.$file->getClientOriginalName();
            $logoPath = $file->storeAs('logos', $filename, 'public');
        }

        $restaurant = Restaurant::create([
            'user_id' => $user->id,
            'restaurant_name' => $validated['restaurant_name'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'logo_url' => $logoPath
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant added successfully!',
            'restaurant' => $restaurant,
            'has_restaurant' => true
        ]);
    }

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
            'logo_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // HANDLE LOGO UPLOAD
        if ($request->hasFile('logo_url')) {

    // Delete old logo
    if ($restaurant->logo_url && \Storage::disk('public')->exists($restaurant->logo_url)) {
        \Storage::disk('public')->delete($restaurant->logo_url);
    }

    // Clean name
    $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '-', strtolower($validated['restaurant_name'] ?? $restaurant->restaurant_name));

    $file = $request->file('logo_url');
    $extension = $file->getClientOriginalExtension();

    $filename = $cleanName . '-' . $user->id . '-' . time() . '.' . $extension;

    $path = $file->storeAs('logos', $filename, 'public');

    $restaurant->logo_url = $path;
}


        // update other fields
        $restaurant->update(array_filter($validated, fn($v) => !is_null($v)));

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant updated successfully!',
            'restaurant' => $restaurant
        ]);
    }
}
