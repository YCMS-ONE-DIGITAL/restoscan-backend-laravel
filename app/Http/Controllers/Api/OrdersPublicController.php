<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CustomerDetail;
use App\Models\MenuItem;
use App\Models\PublicOtpVerification;

class OrdersPublicController extends Controller
{
    public function create(Request $request)
    {   
        // ⭐ STEP 0: VALIDATION
        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id'      => 'required|exists:restaurant_tables,id',
            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:15',
            'order_note'    => 'nullable|string',
            'items'                       => 'required|array|min:1',
            'items.*.menu_item_id'        => 'required|exists:menu_items,id',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.item_note'           => 'nullable|string',
        ]);

        // ⭐ STEP 1: ALWAYS GET LATEST OTP RECORD
        $otpCheck = PublicOtpVerification::where('restaurant_id', $data['restaurant_id'])
            ->where('phone', $data['phone'])
            ->orderBy('id', 'desc')
            ->first();


            // \Log::info("ORDER_START", $request->all());

$latestOtp = PublicOtpVerification::where('restaurant_id', $request->restaurant_id)
    ->where('phone', $request->phone)
    ->orderBy('id', 'desc')
    ->first();

// \Log::info("LATEST_OTP_RECORD", $latestOtp ? $latestOtp->toArray() : null);


        // ⭐ STEP 2: VERIFY OTP STATUS
        if (!$otpCheck || !$otpCheck->is_verified) {
            return response()->json([
                'status' => false,
                'message' => 'Phone not verified. Please verify OTP first.'
            ], 403);
        }

        // ⭐ STEP 3: VERIFY EXPIRY
        if ($otpCheck->verified_expires_at && now()->greaterThan($otpCheck->verified_expires_at)) {
            $otpCheck->update(['is_verified' => false]);

            return response()->json([
                'status' => false,
                'message' => 'Phone verification expired. Please re-verify OTP.'
            ], 403);
        }

        // ⭐ STEP 4: FIND OR CREATE CUSTOMER
        $customer = CustomerDetail::firstOrCreate(
            [
                'phone' => $data['phone'],
                'restaurant_id' => $data['restaurant_id'],
            ],
            [
                'name' => $data['name'],
            ]
        );

        // ⭐ STEP 5: CREATE ORDER
        $order = Order::create([
            'restaurant_id' => $data['restaurant_id'],
            'table_id'      => $data['table_id'],
            'customer_id'   => $customer->id,
            'status'        => 'pending',
            'payment_status'=> 'pending',
            'total_amount'  => 0,
            'order_note'    => $data['order_note'] ?? null,
        ]);

        // ⭐ STEP 6: ADD ORDER ITEMS
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
                'item_note'    => $i['item_note'] ?? null,
            ]);

            $total += $lineTotal;
        }

        // ⭐ UPDATE ORDER TOTAL
        $order->update(['total_amount' => $total]);

        return response()->json([
            'status'      => true,
            'message'     => 'Order placed successfully',
            'order_id'    => $order->id,
            'customer_id' => $customer->id,
            'total'       => $total,
        ]);
    }


    // ⭐ ORDER HISTORY
    public function publicOrderHistory(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'phone'         => 'required|string|max:20',
        ]);

        $orders = Order::with(['items.menu_item', 'table'])
            ->where('restaurant_id', $validated['restaurant_id'])
            ->whereHas('customer', function ($q) use ($validated) {
                $q->where('phone', $validated['phone']);
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders'  => $orders,
        ]);
    }
}
