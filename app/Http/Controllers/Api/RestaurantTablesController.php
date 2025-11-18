<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant_table;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RestaurantTablesController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');

        if (!$user || !$user->restaurant) {
            return null;
        }

        return $user->restaurant->id;
    }

    // ---------------------- ADD Table ----------------------
    public function store(Request $request)
    {
        try {

            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $validated = $request->validate([
                'table_no' => 'required|string|max:50',
                'seating_number' => 'required|integer|min:1',
            ]);

            $seating = Restaurant_table::create([
                'restaurant_id' => $restaurantId,
                'table_no' => $validated['table_no'],
                'seating_number' => $validated['seating_number'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Table added successfully.',
                'data' => $seating,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // ---------------------- LIST Tables ----------------------
    public function index(Request $request)
    {
        try {

            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $tables = Restaurant_table::where('restaurant_id', $restaurantId)->get();

            return response()->json([
                'success' => true,
                'data' => $tables,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ---------------------- UPDATE Table ----------------------
    public function update(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $validated = $request->validate([
                'table_id' => 'required|exists:restaurant_tables,id',
                'table_no' => "sometimes|string|max:50",
                'seating_number' => "sometimes|integer|min:1",
            ]);

            $table = Restaurant_table::where('id', $validated['table_id'])
                ->where('restaurant_id', $restaurantId)
                ->first();

            if (!$table) {
                return response()->json(['message' => 'Table not found'], 404);
            }

            $updateData = $validated;
            unset($updateData['table_id']);

            $table->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully.',
                'data' => $table,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $category = Restaurant_table::where('id', $id)
                            ->where('restaurant_id', $restaurantId)
                            ->first();

        if (!$category) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Table deleted successfully']);
    }
}
