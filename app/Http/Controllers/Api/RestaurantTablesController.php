<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant_table;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RestaurantTablesController extends Controller
{
    //
    // Add seating table
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'table_no' => 'required|string|max:50',
                'seating_number' => 'required|integer|min:1',
            ]);

            $seating = Restaurant_table::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Seating table created successfully.',
                'data' => $seating,
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


    // List tables by restaurant
   public function index(Request $request)
{
    
    try {

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
        ]);

        $tables = Restaurant_table::where('restaurant_id', $validated['restaurant_id'])->get();

        return response()->json([
            'success' => true,
            'data' => $tables,
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



   public function show(Request $request)
{
    try {

        // Validate table_id
        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
        ]);

        // Fetch one table
        $table = Restaurant_table::find($validated['table_id']);

        return response()->json([
            'success' => true,
            'data' => $table,
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
            'received' => $request->all(),
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

// update only one table
public function update(Request $request){
    try {
        
        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
            'table_no' => "sometimes|string|max:50",
            'seating_number' => "sometimes|string|min:1",
        ]);

           // Fetch the table
        $table = Restaurant_table::find($validated['table_id']);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found.',
            ], 404);
        }

          // Update allowed fields
        $table->update($validated);

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
            'received' => $request->all(),
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }


    
}



}
