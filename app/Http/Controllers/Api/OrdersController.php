<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;


class OrdersController extends Controller
{
    //
    public function createOrderWithItems(Request $request)
{
    try {

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id' => 'required|exists:restaurant_tables,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Step 1: Create Order First
        $order = Order::create([
            'restaurant_id' => $validated['restaurant_id'],
            'table_id' => $validated['table_id'],
            'status' => 'pending',
            'total_amount' => 0,
        ]);

        $total = 0;

        // Step 2: Insert multiple items
        foreach ($validated['items'] as $itemData) {

            $menu = MenuItem::find($itemData['menu_item_id']);
            $price = $menu->price;
            $qty = $itemData['quantity'];
            $lineTotal = $price * $qty;

            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $itemData['menu_item_id'],
                'quantity' => $qty,
                'price' => $price,
                'total' => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        // Step 3: Update order total
        $order->update(['total_amount' => $total]);

        return response()->json([
            'success' => true,
            'message' => 'Order created with items',
            'order' => $order,
            'total' => $total,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function fetchOrder(Request $request)
{
    $validated = $request->validate([
        'order_id' => 'required|exists:orders,id'
    ]);

    $order = Order::with([
        'items.menuItem',
        'table'
    ])
    ->where('id', $validated['order_id'])
    ->first();

    return response()->json([
        'success' => true,
        'data' => $order
    ]);
}

public function fetch_all_orders(Request $request)
{
    $validated = $request->validate([
        'restaurant_id' => 'required|exists:restaurants,id'
    ]);

    $orders = Order::with([
        'table'
    ])
    ->where('restaurant_id', $validated['restaurant_id'])
    ->orderBy('id', 'desc')
    ->get();

    return response()->json([
        'success' => true,
        'data' => $orders
    ]);
}

public function update_order(Request $request)
{
    try {

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'nullable|in:pending,preparing,served,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid',
            'payment_method' => 'nullable|in:cash,upi,card'
        ]);

        $order = Order::find($validated['order_id']);

        // Update values ONLY if provided
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
            'data' => $order
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Update failed',
            'error' => $e->getMessage()
        ], 500);
    }
}





}
