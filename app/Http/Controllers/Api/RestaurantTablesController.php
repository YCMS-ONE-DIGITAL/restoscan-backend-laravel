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
            if (!$request->restaurant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'restaurant_id is required (e.g. ?restaurant_id=1)'
                ], 400);
            }

            $tables = Restaurant_table::where('restaurant_id', $request->restaurant_id)->get();

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
}
