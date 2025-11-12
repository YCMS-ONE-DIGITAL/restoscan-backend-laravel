<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of menus.
     * (Optionally filter by restaurant_id)
     */
    public function index(Request $request)
    {
        $query = Menu::with('restaurant', 'categories.items');

        // Filter by restaurant_id if present
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->get('restaurant_id'));
        }

        $menus = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * Store a new menu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'name' => 'required|string|max:255',
        ]);

        $menu = Menu::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully.',
            'data' => $menu,
        ]);
    }

    /**
     * Show a single menu with categories & items.
     */
   /**
 * Display a single menu using query parameter (?menu_id=)
 */
public function show(Request $request)
{
    $menuId = $request->query('menu_id');

    if (!$menuId) {
        return response()->json([
            'success' => false,
            'message' => 'menu_id is required in query parameters (e.g. ?menu_id=1)',
        ], 400);
    }

    $menu = Menu::with('restaurant', 'categories.items')->find($menuId);

    if (!$menu) {
        return response()->json([
            'success' => false,
            'message' => 'Menu not found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $menu,
    ]);
}


    /**
     * Update a menu.
     */
   /**
 * Update a menu using ?menu_id=
 */
public function update(Request $request)
{
    $menuId = $request->query('menu_id');

    if (!$menuId) {
        return response()->json([
            'success' => false,
            'message' => 'menu_id is required in query parameters (e.g. ?menu_id=1)',
        ], 400);
    }

    $menu = Menu::find($menuId);

    if (!$menu) {
        return response()->json([
            'success' => false,
            'message' => 'Menu not found.',
        ], 404);
    }

    $validated = $request->validate([
        'restaurant_id' => 'sometimes|exists:restaurants,id',
        'name' => 'sometimes|string|max:255',
    ]);

    $menu->update($validated);

    return response()->json([
        'success' => true,
        'message' => 'Menu updated successfully.',
        'data' => $menu,
    ]);
}

    /**
     * Delete a menu.
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully.',
        ]);
    }
}
