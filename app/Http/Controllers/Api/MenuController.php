<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class MenuController extends Controller
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
     * List menus
     */
    public function index(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $menus = Menu::where('restaurant_id', $restaurantId)
                        ->orderBy('id', 'DESC')
                        ->get();

            return response()->json($menus);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create menu
     */
    public function store(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            // Manual validator for custom message
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ], [
                'name.required' => 'Menu name is required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $validated = $validator->validated();

            $menu = Menu::create([
                'restaurant_id' => $restaurantId,
                'name' => $validated['name'],
            ]);

            return response()->json([
                'message' => 'Menu created successfully',
                'menu' => $menu,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show single menu
     */
    public function show(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $menu = Menu::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

            return response()->json($menu);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update menu
     */
    public function update(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $menu = Menu::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

           $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ], [
                'name.required' => 'Menu name is required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $validated = $validator->validated();

            $menu->update($validated);

            return response()->json([
                'message' => 'Menu updated successfully',
                'menu' => $menu,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete menu
     */
    public function destroy(Request $request, $id)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);

            $menu = Menu::where('id', $id)
                        ->where('restaurant_id', $restaurantId)
                        ->first();

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

            $menu->delete();

            return response()->json(['message' => 'Menu deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
