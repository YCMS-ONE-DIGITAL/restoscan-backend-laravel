<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CustomerDetail;
use App\Models\MenuItem;

class OrdersPublicController extends Controller
{

    public function create(Request $request)
    {
        // ⭐ STEP 1: VALIDATION
        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id'      => 'required|exists:restaurant_tables,id',

            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:15',

            'order_note'    => 'nullable|string',   // ⭐ NEW

            'items'                       => 'required|array|min:1',
            'items.*.menu_item_id'        => 'required|exists:menu_items,id',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.item_note'           => 'nullable|string',   // ⭐ NEW
        ]);


        // ⭐ STEP 2: FIND OR CREATE CUSTOMER
        $customer = CustomerDetail::firstOrCreate(
            [
                'phone' => $data['phone'],
                'restaurant_id' => $data['restaurant_id'],
            ],
            [
                'name' => $data['name'],
            ]
        );


        // ⭐ STEP 3: CREATE ORDER
        $order = Order::create([
            'restaurant_id' => $data['restaurant_id'],
            'table_id'      => $data['table_id'],
            'customer_id'   => $customer->id,
            'status'        => 'pending',
            'payment_status'=> 'pending',
            'total_amount'  => 0,
            'order_note'    => $data['order_note'] ?? null,   // ⭐ NEW
        ]);


        // ⭐ STEP 4: CREATE ORDER ITEMS + TOTAL
        $total = 0;

        foreach ($data['items'] as $i) {

            $menu = MenuItem::find($i['menu_item_id']);
            $lineTotal = $menu->price * $i['quantity'];

            OrderItem::create([
                'order_id'     => $order->id,
                'menu_item_id' => $menu->id,
                'quantity'     => $i['quantity'],
                'price'        => $menu->price,
                'total'        => $lineTotal,
                'item_note'    => $i['item_note'] ?? null,    // ⭐ NEW
            ]);

            $total += $lineTotal;
        }

        // ⭐ UPDATE TOTAL
        $order->update(['total_amount' => $total]);


        // ⭐ RESPONSE
        return response()->json([
            'status'      => true,
            'message'     => 'Order placed successfully',
            'order_id'    => $order->id,
            'customer_id' => $customer->id,
            'total'       => $total,
        ]);
    }



    public function publicOrderHistory(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'phone' => 'required|string|max:20',
        ]);

        $orders = Order::with([
                'items.menu_item',
                'table'
            ])
            ->where('restaurant_id', $validated['restaurant_id'])
            ->whereHas('customer', function ($q) use ($validated) {
                $q->where('phone', $validated['phone']);
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

}
