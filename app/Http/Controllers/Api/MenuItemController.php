<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');

        if (!$user || !$user->restaurant) {
            return null;
        }

        return $user->restaurant->id;
    }

    /**
     * Add new menu item (Veg, Nonveg, Egg etc)
     */
  public function store(Request $request)
{
    try {

        // Get restaurant ID (your custom function)
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found for this user.'
            ], 404);
        }

        // Validation
        $validator = \Validator::make($request->all(), [
            'menu_id'       => 'required|exists:menus,id',
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0.01',
            'type'          => 'required|in:veg,non_veg,egg',
            'image'         => 'nullable|string',
            'is_available'  => 'sometimes|boolean',
        ]);

        // If validation fails → return proper JSON
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Add restaurant ID
        $validated['restaurant_id'] = $restaurantId;

        // Default image to null if not provided
        $validated['image'] = $validated['image'] ?? null;

        // Create Menu Item
        $item = MenuItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully.',
            'item'     => $item
        ], 201);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while saving the item.',
            'error_details' => $e->getMessage()  // hide in production
        ], 500);
    }
}



public function fetch_all_items(Request $request)
{
    $restaurantId = $this->getRestaurantId($request);

    if (!$restaurantId) {
        return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
    }

    $items = MenuItem::where('restaurant_id', $restaurantId)->get();

    return response()->json([
        'success' => true,
        'data' => $items
    ]);
}

public function uploadImage(Request $request)
{
    $request->validate([
        'file' => 'required|file|image|mimes:jpg,jpeg,png,webp,gif|max:5048',
        'item_name' => 'required|string'
    ]);

    $restaurantId = $this->getRestaurantId($request);
    if (!$restaurantId) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Restaurant Name
    $restaurant = $request->get('auth_user')->restaurant;
    $restaurantName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '-', $restaurant->restaurant_name));

    // Item Name
    $itemName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '-', $request->item_name));

    // ⭐ Storage folder (same as logo)
    $folder = "menu_items/{$restaurantId}";

    // File Info
    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();

    // ⭐ Custom filename
    $filename = "{$itemName}-{$restaurantName}-{$restaurantId}-" . time() . ".{$extension}";

    // ⭐ Store file in storage/app/public/menu_items/{id}
    $path = $file->storeAs($folder, $filename, 'public');

    // Return the path for DB
    return response()->json([
        'success' => true,
        'filename' => $path  // ex: menu_items/5/pavbhaji-5-123.png
    ]);
}




    /**
     * List items by category
     */
    public function fetch_menu_items_list(Request $request)
{
    $restaurantId = $this->getRestaurantId($request);

    if (!$restaurantId) {
        return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
    }

    $items = MenuItem::where('restaurant_id', $restaurantId);

    if ($request->category_id) {
        $items->where('category_id', $request->category_id);
    }

    return response()->json([
        'success' => true,
        'data' => $items->get()
    ]);
}


    /**
     * Get one menu item
     */
    public function fetch_menu_item(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $item = MenuItem::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

        if (!$item) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        return response()->json($item);
    }

    /**
     * Update Menu Item
     */
    public function update_menu_item(Request $request, $id)
{
    $restaurantId = $this->getRestaurantId($request);

    $item = MenuItem::where('id', $id)
                    ->where('restaurant_id', $restaurantId)
                    ->first();

    $validated = $request->validate([
        'menu_id'      => 'sometimes|exists:menus,id',
        'category_id'  => 'sometimes|exists:categories,id',
        'name'         => 'sometimes|string|max:255',
        'description'  => 'sometimes|string|nullable',
        'price'        => 'sometimes|numeric|min:1',
        'type'         => 'sometimes|in:veg,non_veg,egg',
        'image'        => 'sometimes|string|nullable',
        'is_available' => 'sometimes|boolean',
    ]);

    $item->update($validated);

    return response()->json([
        'message' => 'Menu item updated successfully',
        'item' => $item
    ]);
}


    /**
     * Delete a menu item
     */
    public function destroy(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $item = MenuItem::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

        if (!$item) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }


    public function public_menu_items(Request $request)
{
    if (!$request->restaurant_id) {
        return response()->json(['message' => 'restaurant_id required'], 422);
    }

    $items = MenuItem::where('restaurant_id', $request->restaurant_id)->get();

    return response()->json([
        'status' => true,
        'data' => $items
    ]);
}

}
