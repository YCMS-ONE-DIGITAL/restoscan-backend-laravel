<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant_table;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MenuItemController extends Controller
{
    /**
     * Add a new menu item
     */
   public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'category_id'    => 'required|exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:1',
            'type'           => 'required|in:veg,non_veg,egg',
            'image'          => 'nullable|string',
            'is_available'   => 'boolean',
        ]);
        

        $item = MenuItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully.',
            'data' => $item,
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

// fetch menu items by categories 
   public function fetch_menu_items_list(Request $request)
{
    try {

        // Validate only restaurant and category
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'category_id'  => 'required|exists:categories,id',
        ]);

        // Fetch items
        $items = MenuItem::where('restaurant_id', $validated['restaurant_id'])
                         ->where('category_id', $validated['category_id'])
                         ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
  public function fetch_menu_item(Request $request)
{
    try {

        // Validate only item id
        $validated = $request->validate([
            'menu_item_id'  => 'required|exists:menu_items,id',
        ]);

        // Fetch item
        $item = MenuItem::find($validated['menu_item_id']);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function update_menu_item(Request $request)
{
    try {

        // Validate ID + optional fields
        $validated = $request->validate([
            'menu_item_id'   => 'required|exists:menu_items,id',
            'name'           => 'sometimes|string|max:255',
            'description'    => 'sometimes|string|nullable',
            'price'          => 'sometimes|numeric|min:1',
            'type'           => 'sometimes|in:veg,non_veg,egg',
            'image'          => 'sometimes|string|nullable',
            'is_available'   => 'sometimes|boolean',
        ]);

        // Fetch the item
        $item = MenuItem::find($validated['menu_item_id']);

        // Remove ID from update array
        unset($validated['menu_item_id']);

        // Update item
        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully.',
            'data' => $item,
        ]);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}






}
