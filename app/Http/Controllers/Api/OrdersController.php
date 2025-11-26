<?php

namespace App\Http\Controllers\Api;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\CustomerDetail;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // ✅ VALIDATION
        $validated = $request->validate([
            'order_type' => 'required|in:dine_in,parcel,delivery',

            // ✅ Only dine_in will require table later
            'table_id' => 'nullable|exists:restaurant_tables,id',

            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',

            'items'    => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        // ✅ Dine-In must have table_id
        if ($validated['order_type'] === "dine_in" && !$validated['table_id']) {
            return response()->json(['message' => 'Table required for dine-in'], 422);
        }

        // ✅ Create or fetch customer if phone provided
        $customerId = null;

        if (!empty($validated['customer_phone'])) {
            $customer = CustomerDetail::firstOrCreate(
                [
                    'phone' => $validated['customer_phone'],
                    'restaurant_id' => $restaurantId
                ],
                [
                    'name' => $validated['customer_name'] ?? "Unknown Customer"
                ]
            );

            $customerId = $customer->id;
        }

        // ✅ Create New Order
        $order = Order::create([
            'restaurant_id' => $restaurantId,
            'order_type'    => $validated['order_type'],
            'table_id'      => $validated['order_type'] === "dine_in" ? $validated['table_id'] : null,
            'customer_id'   => $customerId,
            'status'        => 'pending',
            'total_amount'  => 0,
        ]);

        $total = 0;

        // ✅ Add Order Items & Calculate Total
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

        // ✅ Update total amount
        $order->update(['total_amount' => $total]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order'   => $order->load(['table', 'items.menu_item', 'customer']),
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

        $order = Order::with(['items.menu_item', 'table'])
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

    $perPage = $request->get('per_page', 10); // ✅ Default 10

    $orders = Order::with([
        'table',
        'items',
        'items.menu_item',
        'customer'
    ])
    ->where('restaurant_id', $restaurantId)
    ->orderBy('id', 'desc')
    ->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $orders->items(),
        'pagination' => [
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'next_page_url' => $orders->nextPageUrl(),
            'prev_page_url' => $orders->previousPageUrl(),
        ]
    ]);
}



    /**
     * Update Order
     */
   public function update_order(Request $request)
{
    $validated = $request->validate([
        'order_id' => 'required|exists:orders,id',

        'status' => 'nullable|in:pending,kot,preparing,served,completed,cancelled',
        'payment_status' => 'nullable|in:pending,paid',
        'payment_method' => 'nullable|in:cash,upi,card',

        'items' => 'nullable|array',
        'items.*.order_item_id' => 'required|exists:order_items,id',
        'items.*.quantity' => 'required|integer|min:1',

        // ⭐ ADD THIS
        'deleted_items' => 'nullable|array',
        'deleted_items.*' => 'exists:order_items,id',
    ]);

    $order = Order::find($validated['order_id']);

    // ⭐ Update basic fields
    if ($request->filled('status')) {
        $order->status = $request->status;
    }
    if ($request->filled('payment_status')) {
        $order->payment_status = $request->payment_status;
    }
    if ($request->filled('payment_method')) {
        $order->payment_method = $request->payment_method;
    }

    // ⭐ DELETE removed items FIRST
    if ($request->has('deleted_items')) {
        foreach ($request->deleted_items as $delId) {
            OrderItem::where('id', $delId)
                ->where('order_id', $order->id) // safety
                ->delete();
        }
    }

    // ⭐ Update remaining items
    if ($request->has('items')) {
        foreach ($request->items as $item) {

            $orderItem = OrderItem::where('id', $item['order_item_id'])
                ->where('order_id', $order->id)
                ->first();

            if ($orderItem) {
                $orderItem->quantity = $item['quantity'];
                $orderItem->total = $orderItem->price * $orderItem->quantity;
                $orderItem->save();
            }
        }
    }

    // ⭐ Recalculate Total
    $newTotal = OrderItem::where('order_id', $order->id)->sum('total');
    $order->total_amount = $newTotal;
    $order->save();

    return response()->json([
        'success' => true,
        'message' => 'Order updated successfully',
        'data' => $order
    ]);
}






public function dashboardStats(Request $request)
{
    $restaurantId = $this->getRestaurantId($request);

    if (!$restaurantId) {
        return response()->json(['message' => 'Restaurant not found'], 404);
    }

    // ✅ Today Orders
    $todayOrders = Order::where('restaurant_id', $restaurantId)
        ->whereDate('created_at', today())
        ->count();

    // ✅ Today Earnings
    $todayEarnings = Order::where('restaurant_id', $restaurantId)
        ->whereDate('created_at', today())
        ->sum('total_amount');

    // ✅ Today's Unique Customers
    $todayCustomers = Order::where('restaurant_id', $restaurantId)
        ->whereDate('created_at', today())
        ->distinct('customer_id')
        ->count('customer_id');

    // ✅ Last 7 days earnings chart
    $last7Days = Order::where('restaurant_id', $restaurantId)
        ->whereDate('created_at', '>=', now()->subDays(6))
        ->selectRaw("DATE(created_at) as date, SUM(total_amount) as total")
        ->groupBy('date')
        ->orderBy('date', 'ASC')
        ->get();


           // ✅ Today's Orders List (FULL DETAIL for dashboard)
        $orders = Order::with(['items.menu_item', 'table', 'customer'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->get();

    // ✅ Average Daily Earnings
    $avgDailyEarnings = $last7Days->avg('total') ?? 0;



    return response()->json([
        'success' => true,
        'todayOrders' => $todayOrders,
        'todayEarnings' => $todayEarnings,
        'todayCustomers' => $todayCustomers,
        'avgDailyEarnings' => round($avgDailyEarnings),
        'salesChart' => $last7Days,
        "orders" => $orders,
    ]);
}



public function filterOrders(Request $request)
{
    $restaurantId = $this->getRestaurantId($request);

    if (!$restaurantId) {
        return response()->json(['message' => 'Restaurant not found'], 404);
    }

    $dateRangeType = $request->query('dateRangeType', 'today');
    $startDate     = $request->query('startDate');
    $endDate       = $request->query('endDate');
    $status        = $request->query('status');
    $paymentStatus = $request->query('payment_status');

    $orders = Order::with(['items.menu_item', 'table', 'customer'])
        ->where('restaurant_id', $restaurantId);

    switch ($dateRangeType) {
        case "yesterday":
            $orders->whereDate('created_at', today()->subDay());
            break;

        case "last7days":
            $orders->whereBetween('created_at', [
                now()->subDays(7)->startOfDay(),
                now()->endOfDay(),
            ]);
            break;

        case "currentMonth":
            $orders->whereMonth('created_at', now()->month)
                   ->whereYear('created_at', now()->year);
            break;

        case "lastMonth":
            $orders->whereMonth('created_at', now()->subMonth()->month)
                   ->whereYear('created_at', now()->subMonth()->year);
            break;

        case "custom":
            if ($startDate && $endDate) {
                $orders->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            }
            break;

        default:
            $orders->whereDate('created_at', today());
    }

    if (!empty($status)) {
        $orders->where('status', $status);
    }

    if (!empty($paymentStatus)) {
        $orders->where('payment_status', $paymentStatus);
    }

    $perPage = $request->query('per_page', 10);

    $orders = $orders->orderBy('id', 'desc')->paginate($perPage);

    // ✅ Guarantee order_type NEVER NULL (Fix React crash)
    $orders->getCollection()->transform(function ($order) {
        $order->order_type = $order->order_type ?? 'dine_in';
        return $order;
    });

    return response()->json([
        'success' => true,
        'data' => $orders->items(),
        'pagination' => [
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'next_page_url' => $orders->nextPageUrl(),
            'prev_page_url' => $orders->previousPageUrl(),
        ]
    ]);
}



    public function generateBill(Order $order)
    {
        $order->load([
            'items',
            'items.menu_item',
            'restaurant',
            'table',
            'customer',
        ]);

        // ✅ 58mm thermal width = 165pt
        $customPaper = [0, 0, 165, 600];
        // dd($order->toArray());


        
        return Pdf::loadView('bill', compact('order'))
            ->setPaper($customPaper, 'portrait')
            ->setWarnings(false)
            ->stream("bill-{$order->id}.pdf");
    }


}
