<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\File;


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
    try {

        // Auth check
        $user = $this->getUser($request);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Check if restaurant already exists
        if (Restaurant::where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'A restaurant is already linked to this user.'
            ], 400);
        }

        // Validation
        $validator = \Validator::make($request->all(), [
            'restaurant_name'  => 'required|string|max:255',
            'address'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'state'            => 'nullable|string|max:100',
            'pincode'          => 'nullable|string|max:10',
            'contact_number'   => 'nullable|string|max:15',
            'logo_url'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Process Logo Upload
        $logoPath = null;

        if ($request->hasFile('logo_url')) {
            $file = $request->file('logo_url');

            if (!$file->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid image file. Please upload a valid image.'
                ], 400);
            }

            $filename = time().'_'.$file->getClientOriginalName();
            $logoPath = $file->storeAs('logos', $filename, 'public');
        }

        // Create Restaurant
        $restaurant = Restaurant::create([
            'user_id'          => $user->id,
            'restaurant_name'  => $request->restaurant_name,
            'address'          => $request->address,
            'city'             => $request->city,
            'state'            => $request->state,
            'pincode'          => $request->pincode,
            'contact_number'   => $request->contact_number,
            'logo_url'         => $logoPath
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant created successfully!',
            'restaurant' => $restaurant,
            'has_restaurant' => true
        ], 201);

    } catch (\Exception $e) {

        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong while saving restaurant.',
            'error_details' => $e->getMessage() // Remove in production
        ], 500);

    }
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
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    $restaurant = Restaurant::where('user_id', $user->id)->first();

    if (!$restaurant) {
        return response()->json([
            'status' => 'error',
            'message' => 'No restaurant found'
        ], 404);
    }

    // ✅ VALIDATION
    $validated = $request->validate([
        'restaurant_name' => 'nullable|string|max:255',
        'address'         => 'nullable|string|max:255',
        'city'            => 'nullable|string|max:100',
        'state'           => 'nullable|string|max:100',
        'pincode'         => 'nullable|string|max:10',
        'contact_number'  => 'nullable|string|max:15',
        'logo_url'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    /* ===============================
       HANDLE LOGO UPLOAD
       Path: public/upload/restaurant
    ================================ */
    if ($request->hasFile('logo_url')) {

        $uploadPath = public_path('upload/restaurant/logos');

        // Create folder if not exists
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        // Delete old logo safely
        if (
            $restaurant->logo_url &&
            File::exists(public_path($restaurant->logo_url))
        ) {
            File::delete(public_path($restaurant->logo_url));
        }

        // Clean restaurant name for filename
        $cleanName = preg_replace(
            '/[^A-Za-z0-9\-]/',
            '-',
            strtolower($validated['restaurant_name'] ?? $restaurant->restaurant_name)
        );

        $file = $request->file('logo_url');
        $extension = $file->getClientOriginalExtension();

        $filename = $cleanName . '-' . $user->id . '-' . time() . '.' . $extension;

        // Move image to public folder
        $file->move($uploadPath, $filename);

        // Save RELATIVE path in DB
        $restaurant->logo_url = 'upload/restaurant/logos/' . $filename;
    }

    // ✅ UPDATE OTHER FIELDS
    $restaurant->update(
        array_filter($validated, fn ($v) => !is_null($v))
    );

    return response()->json([
        'status'     => 'success',
        'message'    => 'Restaurant updated successfully!',
        'restaurant' => $restaurant
    ]);
}
}
