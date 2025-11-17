<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');

        // user ला restaurant नसेल तर null
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
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        return Menu::where('restaurant_id', $restaurantId)
                   ->orderBy('id', 'DESC')
                   ->get();
    }

    /**
     * Create menu
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

        $menu = Menu::create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Menu created successfully',
            'menu' => $menu,
        ]);
    }

    /**
     * Show single menu
     */
    public function show(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $menu = Menu::where('id', $id)
                    ->where('restaurant_id', $restaurantId)
                    ->first();

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        return response()->json($menu);
    }

    /**
     * Update menu
     */
    public function update(Request $request, $id)
{
    $restaurantId = $this->getRestaurantId($request);

    $menu = Menu::where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->first();

    if (!$menu) {
        return response()->json(['message' => 'Menu not found'], 404);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $menu->update($validated);

    return response()->json([
        'message' => 'Menu updated successfully',
        'menu' => $menu
    ]);
}


    /**
     * Delete menu
     */
    public function destroy(Request $request, $id)
    {
        $restaurantId = $this->getRestaurantId($request);

        $menu = Menu::where('id', $id)
                    ->where('restaurant_id', $restaurantId)
                    ->first();

        if (!$menu) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        $menu->delete();

        return response()->json([
            'message' => 'Menu deleted successfully'
        ]);
    }
}
