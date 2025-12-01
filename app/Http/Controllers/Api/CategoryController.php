<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        return $user && $user->restaurant ? $user->restaurant->id : null;
    }

    /**
     * Upload Category Image (Same as MenuItem)
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category_name' => 'required|string'
        ]);

        $restaurantId = $this->getRestaurantId($request);
        if (!$restaurantId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Restaurant Name
        $restaurant = $request->attributes->get('auth_user')->restaurant;
        $restaurantName = strtolower(
            preg_replace('/[^A-Za-z0-9\-]/', '-', $restaurant->restaurant_name)
        );

        // Category Name
        $categoryName = strtolower(
            preg_replace('/[^A-Za-z0-9\-]/', '-', $request->category_name)
        );

        // Folder
        $folder = "category_images/{$restaurantId}";

        // File
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        // Filename
        $filename = "{$categoryName}-{$restaurantName}-{$restaurantId}-" . time() . ".{$extension}";

        // Store
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'success' => true,
            'filename' => $path
        ]);
    }

    /**
     * Create Category (store only image path)
     */
    public function store(Request $request)
    {
        $restaurantId = $this->getRestaurantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|string', // path from uploadImage()
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ]);
    }

    /**
     * Fetch All
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
     * Update Category
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
            'image' => 'nullable|string', // new path from uploadImage()
        ]);

        $category->name = $validated['name'];

        if (isset($validated['image'])) {
            $category->image = $validated['image'];
        }

        $category->save();

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Delete Category
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

        // Delete image
        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
