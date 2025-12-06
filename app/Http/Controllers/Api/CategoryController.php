<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Exception;

class CategoryController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        return $user && $user->restaurant ? $user->restaurant->id : null;
    }

    /**
     * Upload Category Image
     */
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:4096',
                'category_name' => 'required|string'
            ]);

            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $restaurant = $request->attributes->get('auth_user')->restaurant;

            // Sanitize names
            $restaurantName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '-', $restaurant->restaurant_name));
            $categoryName   = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '-', $request->category_name));

            $folder = "category_images/{$restaurantId}";
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $filename = "{$categoryName}-{$restaurantName}-{$restaurantId}-" . time() . ".{$extension}";
            $path = $file->storeAs($folder, $filename, 'public');

            return response()->json([
                'success' => true,
                'filename' => $path
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Category
     */
    public function store(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'nullable|string',
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
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch All Categories
     */
    public function index(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $categories = Category::where('restaurant_id', $restaurantId)->get();

            return response()->json($categories);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Category
     */
    public function update(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $category = Category::where('id', $id)
                                ->where('restaurant_id', $restaurantId)
                                ->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'nullable|string',
            ]);

            $category->name = $validated['name'];

            if (isset($validated['image'])) {
                // Delete old image
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }
                $category->image = $validated['image'];
            }

            $category->save();

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete Category
     */
    public function destroy(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $category = Category::where('id', $id)
                                ->where('restaurant_id', $restaurantId)
                                ->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $category->delete();

            return response()->json(['message' => 'Category deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
