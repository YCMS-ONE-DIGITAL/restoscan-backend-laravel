<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
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
     * Create category
     */
    public function store(Request $request)
    {
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ]);
    }

    /**
     * Fetch all categories of logged-in restaurant
     */
    public function index(Request $request)
    {
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $categories = Category::where('restaurant_id', $restaurantId)->get();

        return response()->json($categories);
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $category = Category::where('id', $id)
                            ->where('restaurant_id', $restaurantId)
                            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Delete category
     */
    public function destroy(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $category = Category::where('id', $id)
                            ->where('restaurant_id', $restaurantId)
                            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
