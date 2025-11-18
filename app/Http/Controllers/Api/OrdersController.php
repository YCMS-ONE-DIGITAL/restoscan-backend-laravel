<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;

class OrdersController extends Controller
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
     * Create Order with Items
     */
    public function createOrderWithItems(Request $request)
    {

        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
            'items'    => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        // Create order
        $order = Order::create([
            'restaurant_id' => $restaurantId,
            'table_id'      => $validated['table_id'],
            'status'        => 'pending',
            'total_amount'  => 0,
        ]);

        $total = 0;

        foreach ($validated['items'] as $itemData) {
            $menu = MenuItem::find($itemData['menu_item_id']);

            $lineTotal = $menu->price * $itemData['quantity'];

            OrderItem::create([
                'order_id'     => $order->id,
                'menu_item_id' => $menu->id,
                'quantity'     => $itemData['quantity'],
                'price'        => $menu->price,
                'total'        => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        $order->update(['total_amount' => $total]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order'   => $order,
            'total'   => $total,
        ]);
    }

    /**
     * Fetch single order
     */
    public function fetchOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with(['items.menuItem', 'table'])
                      ->find($validated['order_id']);

        return response()->json([
            'success' => true,
            'data'    => $order
        ]);
    }

    /**
     * Fetch all orders for restaurant
     */
    public function fetch_all_orders(Request $request)
    {
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        $orders = Order::with('table')
                       ->where('restaurant_id', $restaurantId)
                       ->orderBy('id', 'desc')
                       ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders
        ]);
    }

    /**
     * Update Order
     */
    public function update_order(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'nullable|in:pending,preparing,served,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid',
            'payment_method' => 'nullable|in:cash,upi,card'
        ]);

        $order = Order::find($validated['order_id']);

        if ($request->filled('status')) {
            $order->status = $request->status;
        }

        if ($request->filled('payment_status')) {
            $order->payment_status = $request->payment_status;
        }

        if ($request->filled('payment_method')) {
            $order->payment_method = $request->payment_method;
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data'    => $order
        ]);
    }
}
