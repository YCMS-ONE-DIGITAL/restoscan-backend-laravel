<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Create a category for a restaurant (with try catch)
     */
    public function store(Request $request)
    {
        try {

            // Validation
            $validated = $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'name' => 'required|string|max:255',
            ]);

            // Create category
            $category = Category::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category,
            ], 200);

        } catch (ValidationException $e) {

            // Validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            // Log error
            Log::error("Category create error: " . $e->getMessage());

            // Unexpected Server Error
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating category.',
                'error' => $e->getMessage(), // debug use
            ], 500);
        }
    }


    /**
     * Get all categories by restaurant_id
     */
    public function index(Request $request)
    {
        try {

            if (!$request->has('restaurant_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'restaurant_id is required (e.g. ?restaurant_id=1)',
                ], 400);
            }

            $categories = Category::where('restaurant_id', $request->restaurant_id)
                                //   ->with('items')
                                  ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Exception $e) {

            Log::error("Category fetch error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch categories.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
 * Update a category using ?category_id=
 */
public function update(Request $request)
{
    try {

        // Check if category_id is given in query
        $categoryId = $request->query('category_id');
        
        if (!$categoryId) {
            return response()->json([
                'success' => false,
                'message' => 'category_id is required in query (e.g. ?category_id=5)',
            ], 400);
        }

        // Find Category
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        // Validate incoming fields
        $validated = $request->validate([
            'restaurant_id' => 'sometimes|exists:restaurants,id',
            'name' => 'sometimes|string|max:255',
        ]);

        // Update data
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $category,
        ], 200);

    } catch (ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while updating category.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
