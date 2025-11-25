<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    //
 private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');

        if (!$user || !$user->restaurant) {
            return null;
        }

        return $user->restaurant->id;
    }


    public function customers(Request $request)
{
    $restaurantId = $this->getRestaurantId($request);

    if (!$restaurantId) {
        return response()->json(['message' => 'Restaurant not found'], 404);
    }

    $search = $request->search;

    $customers = Order::where('restaurant_id', $restaurantId)
        ->whereNotNull('customer_id') // ✅ ignore orders without customer
        ->select(
            'customer_id',
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('MAX(created_at) as last_order_time')
        )
        ->groupBy('customer_id')
        ->with(['customer:id,name,phone']) // ✅ eager load only needed columns
        ->get()
        ->map(function ($o) use ($search) {
            if (!$o->customer) return null; // ✅ prevent null access

            return [
                'id' => $o->customer->id,
                'name' => $o->customer->name,
                'phone' => $o->customer->phone,
                'total_orders' => $o->total_orders,
                'last_order_time' => $o->last_order_time,
            ];
        })
        ->filter(); // ✅ remove null entries safely

    // ✅ Apply search AFTER mapping
    if ($search) {
        $customers = $customers->filter(fn($c) =>
            str_contains(strtolower($c['name']), strtolower($search)) ||
            str_contains($c['phone'], $search)
        )->values();
    }

    return response()->json([
        'success' => true,
        'data' => $customers->values()
    ]);
}


public function orderHistory(Request $request, $customerId)
{
    $restaurantId = $this->getRestaurantId($request);

    $orders = Order::with(['items.menu_item', 'table'])
        ->where('restaurant_id', $restaurantId)
        ->where('customer_id', $customerId)
        ->orderBy('id', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $orders
    ]);
}


}
