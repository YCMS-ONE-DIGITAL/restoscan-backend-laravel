<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\File;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class MenuItemController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');
        return ($user && $user->restaurant) ? $user->restaurant->id : null;
    }

    /**
     * Add new menu item
     */
    public function store(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'menu_id'       => 'required|exists:menus,id',
                'category_id'   => 'required|exists:categories,id',
                'name'          => 'required|string|max:255',
                'description'   => 'nullable|string',
                'price'         => 'required|numeric|min:0.01',
                'type'          => 'required|in:veg,non_veg,egg',
                'image'         => 'nullable|string',
                'is_available'  => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['restaurant_id'] = $restaurantId;
            $validated['image'] = $validated['image'] ?? null;

            $item = MenuItem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'item'    => $item
            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error while creating item.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload Menu Item Image
     */

public function uploadImage(Request $request)
{
    try {
        // ✅ VALIDATION
        $request->validate([
            'file'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
            'item_name' => 'required|string'
        ]);

        $restaurantId = $this->getRestaurantId($request);
        if (!$restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $restaurant = $request->get('auth_user')->restaurant;

        // ✅ SANITIZE NAMES
        $restaurantName = strtolower(
            preg_replace('/[^A-Za-z0-9\-]/', '-', $restaurant->restaurant_name)
        );

        $itemName = strtolower(
            preg_replace('/[^A-Za-z0-9\-]/', '-', $request->item_name)
        );

        /* ======================================
           PATH: public/upload/restaurant/menu_items/{restaurantId}
        ====================================== */
        $uploadPath = public_path("upload/restaurant/menu_items/{$restaurantId}");

        // Create folder if not exists
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $filename = "{$itemName}-{$restaurantName}-{$restaurantId}-" . time() . ".{$extension}";

        // Move file to public folder
        $file->move($uploadPath, $filename);

        // Relative path for DB / frontend
        $relativePath = "upload/restaurant/menu_items/{$restaurantId}/{$filename}";

        return response()->json([
            'success'  => true,
            'filename' => $relativePath
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Image upload failed.',
            'error'   => $e->getMessage(),
            'line'    => $e->getLine()
        ], 500);
    }
}


    /**
     * Fetch All Items
     */
    public function fetch_all_items(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $items = MenuItem::where('restaurant_id', $restaurantId)->get();

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List items by category
     */
    public function fetch_menu_items_list(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $query = MenuItem::where('restaurant_id', $restaurantId);

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            return response()->json([
                'success' => true,
                'data' => $query->get()
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single menu item
     */
    public function fetch_menu_item(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $item = MenuItem::where('id', $id)
                            ->where('restaurant_id', $restaurantId)
                            ->first();

            if (!$item) {
                return response()->json(['message' => 'Menu item not found'], 404);
            }

            return response()->json($item);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Menu Item
     */
    public function update_menu_item(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $item = MenuItem::where('id', $id)
                            ->where('restaurant_id', $restaurantId)
                            ->first();

            if (!$item) {
                return response()->json(['message' => 'Menu item not found'], 404);
            }

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
                'message' => 'Menu item updated successfully.',
                'item' => $item
            ]);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Error while updating item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Menu Item
     */
    public function destroy(Request $request, $id)
{
    try {
        $restaurantId = $this->getRestaurantId($request);

        $item = MenuItem::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

        if (!$item) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        // ✅ DELETE IMAGE FROM public/
        if ($item->image) {
            $fullPath = public_path($item->image);

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }

        $item->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);

    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error while deleting item',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Public API for customers
     */
    public function public_menu_items(Request $request)
    {
        try {
            if (!$request->restaurant_id) {
                return response()->json(['message' => 'restaurant_id required'], 422);
            }

            $items = MenuItem::where('restaurant_id', $request->restaurant_id)->get();

            return response()->json([
                'status' => true,
                'data' => $items
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
