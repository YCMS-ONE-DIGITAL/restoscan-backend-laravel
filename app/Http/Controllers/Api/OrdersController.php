<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\CustomerDetail;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdersController extends Controller
{
    private function getRestaurantId(Request $request)
    {
        $user = $request->get('auth_user');
        return ($user && $user->restaurant) ? $user->restaurant->id : null;
    }

    /**
     * Create Order with Items (atomic: DB transaction)
     */
    public function createOrderWithItems(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'order_type' => 'required|in:dine_in,parcel,delivery',
                'table_id' => 'nullable|exists:restaurant_tables,id',
                'customer_name'  => 'nullable|string|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'order_note' => 'nullable|string',
                'items'    => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity'     => 'required|integer|min:1',
                'items.*.item_note'    => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Dine-in requires table
            if ($data['order_type'] === "dine_in" && empty($data['table_id'])) {
                return response()->json(['success' => false, 'message' => 'Table required for dine-in'], 422);
            }

            // Create or fetch customer (optional)
            $customerId = null;
            if (!empty($data['customer_phone'])) {
                $customer = CustomerDetail::firstOrCreate(
                    [
                        'phone' => $data['customer_phone'],
                        'restaurant_id' => $restaurantId
                    ],
                    [
                        'name' => $data['customer_name'] ?? "Unknown Customer"
                    ]
                );
                $customerId = $customer->id;
            }

            DB::beginTransaction();

            // Create Order
            $order = Order::create([
                'restaurant_id' => $restaurantId,
                'order_type'    => $data['order_type'],
                'table_id'      => ($data['order_type'] === "dine_in") ? $data['table_id'] : null,
                'customer_id'   => $customerId,
                'order_note'    => $data['order_note'] ?? null,
                'status'        => 'pending',
                'total_amount'  => 0,
            ]);

            $total = 0;

            // Add Items - ensure menu item belongs to same restaurant (safety)
            foreach ($data['items'] as $itemData) {
                $menu = MenuItem::find($itemData['menu_item_id']);

                if (!$menu || $menu->restaurant_id != $restaurantId) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid menu item: {$itemData['menu_item_id']}"
                    ], 422);
                }

                // calculate carefully
                $price = (float) $menu->price;
                $quantity = (int) $itemData['quantity'];
                $lineTotal = round($price * $quantity, 2);

                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $menu->id,
                    'quantity'     => $quantity,
                    'price'        => $price,
                    'total'        => $lineTotal,
                    'item_note'    => $itemData['item_note'] ?? null,
                ]);

                $total += $lineTotal;
            }

            // Update order total
            $order->total_amount = round($total, 2);
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order'   => $order->load(['table', 'items.menu_item', 'customer']),
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch single order by order_id (in body)
     */
    public function fetchOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::with(['items.menu_item', 'table', 'customer'])
                        ->find($request->order_id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order fetched successfully',
                'data' => $order
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all orders (paginated)
     */
    public function fetch_all_orders(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $perPage = (int) $request->get('per_page', 10);

            $orders = Order::with(['table', 'items.menu_item', 'customer'])
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

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Order (status, payment, items and deleted_items)
     */
public function update_order(Request $request)
{
    try {

        /* =========================
           VALIDATION
        ========================= */
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',

            'status' => 'nullable|in:pending,kot,preparing,served,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid',
            'payment_method' => 'nullable|in:cash,upi,card',
            'order_note' => 'nullable|string',

            'items' => 'nullable|array',

            // Existing order item (update)
            'items.*.order_item_id' => 'nullable|exists:order_items,id',

            // New menu item (add)
            'items.*.menu_item_id' =>
                'required_without:items.*.order_item_id|exists:menu_items,id',

            'items.*.quantity' => 'required|integer|min:1',
            'items.*.item_note' => 'nullable|string',

            // Deleted items
            'deleted_items' => 'nullable|array',
            'deleted_items.*' => 'exists:order_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        /* =========================
           FETCH ORDER
        ========================= */
        $order = Order::find($data['order_id']);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // 🔐 Restaurant safety check
        $restaurantId = $this->getRestaurantId($request);
        if ($restaurantId && $order->restaurant_id != $restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();

        /* =========================
           UPDATE ORDER FIELDS
        ========================= */
        if (array_key_exists('order_note', $data)) {
            $order->order_note = $data['order_note'];
        }
        if (array_key_exists('status', $data)) {
            $order->status = $data['status'];
        }
        if (array_key_exists('payment_status', $data)) {
            $order->payment_status = $data['payment_status'];
        }
        if (array_key_exists('payment_method', $data)) {
            $order->payment_method = $data['payment_method'];
        }

        /* =========================
           DELETE ITEMS
        ========================= */
        if (!empty($data['deleted_items'])) {
            OrderItem::where('order_id', $order->id)
                ->whereIn('id', $data['deleted_items'])
                ->delete();
        }

        /* =========================
           UPDATE / ADD ITEMS
        ========================= */
        if (!empty($data['items'])) {
            foreach ($data['items'] as $itm) {

                // 🛡 SAFETY: skip invalid mixed payload
                if (!empty($itm['order_item_id']) && !empty($itm['menu_item_id'])) {
                    continue;
                }

                /* 🔁 UPDATE EXISTING ITEM */
                if (!empty($itm['order_item_id'])) {

                    $orderItem = OrderItem::where('id', $itm['order_item_id'])
                        ->where('order_id', $order->id)
                        ->first();

                    if ($orderItem) {
                        $orderItem->quantity = (int) $itm['quantity'];
                        $orderItem->total = round(
                            $orderItem->price * $orderItem->quantity,
                            2
                        );
                        $orderItem->item_note = $itm['item_note'] ?? null;
                        $orderItem->save();
                    }
                }

                /* ➕ ADD NEW MENU ITEM */
                elseif (!empty($itm['menu_item_id'])) {

                    $menu = MenuItem::find($itm['menu_item_id']);

                    if ($menu) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'menu_item_id' => $menu->id,
                            'name' => $menu->name,
                            'price' => $menu->price,
                            'quantity' => (int) $itm['quantity'],
                            'total' => round(
                                $menu->price * $itm['quantity'],
                                2
                            ),
                            'item_note' => $itm['item_note'] ?? null,
                        ]);
                    }
                }
            }
        }

        /* =========================
           RECALCULATE TOTAL
        ========================= */
        $order->total_amount = round(
            OrderItem::where('order_id', $order->id)->sum('total'),
            2
        );

        $order->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->load([
                'items.menu_item',
                'table',
                'customer'
            ])
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update order',
            'error_details' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Dashboard stats (today, last7days etc)
     */
    public function dashboardStats(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $today = now()->toDateString();

            $todayOrders = Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $today)->count();

            $todayEarnings = Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $today)->sum('total_amount');

            $todayCustomers = Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $today)
                ->whereNotNull('customer_id')
                ->distinct('customer_id')
                ->count('customer_id');

            $last7Days = Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->selectRaw("DATE(created_at) as date, SUM(total_amount) as total")
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $orders = Order::with(['items.menu_item', 'table', 'customer'])
                ->where('restaurant_id', $restaurantId)
    ->whereNotIn('status', ['completed', 'cancelled']) // 👉 completed + cancelled exclude
                ->whereDate('created_at', $today)
                ->orderBy('id', 'desc')
                ->get();

            $avgDailyEarnings = $last7Days->avg('total') ?? 0;

            return response()->json([
                'success' => true,
                'todayOrders' => $todayOrders,
                'todayEarnings' => (float) $todayEarnings,
                'todayCustomers' => $todayCustomers,
                'avgDailyEarnings' => round($avgDailyEarnings),
                'salesChart' => $last7Days,
                'orders' => $orders,
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Filter Orders (date range, status, payment_status) - paginated
     */
    public function filterOrders(Request $request)
    {
        try {
            $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            $dateRangeType = $request->query('dateRangeType', 'today');
            $startDate     = $request->query('startDate');
            $endDate       = $request->query('endDate');
            $status        = $request->query('status');
            $paymentStatus = $request->query('payment_status');

            $ordersQuery = Order::with(['items.menu_item', 'table', 'customer'])
                ->where('restaurant_id', $restaurantId);

            switch ($dateRangeType) {
                case "yesterday":
                    $ordersQuery->whereDate('created_at', today()->subDay());
                    break;
                case "last7days":
                    $ordersQuery->whereBetween('created_at', [
                        now()->subDays(7)->startOfDay(), now()->endOfDay()
                    ]);
                    break;
                case "currentMonth":
                    $ordersQuery->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year);
                    break;
                case "lastMonth":
                    $ordersQuery->whereMonth('created_at', now()->subMonth()->month)
                                ->whereYear('created_at', now()->subMonth()->year);
                    break;
                case "custom":
                    if ($startDate && $endDate) {
                        $ordersQuery->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay(),
                        ]);
                    }
                    break;
                default:
                    $ordersQuery->whereDate('created_at', today());
            }

            if (!empty($status)) {
                $ordersQuery->where('status', $status);
            }
            if (!empty($paymentStatus)) {
                $ordersQuery->where('payment_status', $paymentStatus);
            }

            $perPage = (int) $request->query('per_page', 10);
            $orders = $ordersQuery->orderBy('id', 'desc')->paginate($perPage);

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

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate Bill (returns view or PDF stream)
     * Usage: pass order id in route model binding or call with order id
     */
    public function generateBill(Order $order)
    {
        try {
            $order->load(['items', 'items.menu_item', 'restaurant', 'table', 'customer']);

            // if you want to return pdf stream uncomment below (requires barryvdh/laravel-dompdf)
            // $customPaper = [0, 0, 165, 600];
            // return Pdf::loadView('bill', compact('order'))
            //     ->setPaper($customPaper, 'portrait')
            //     ->setWarnings(false)
            //     ->stream("bill-{$order->id}.pdf");

            return view('bill', compact('order'));
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Payment history (simple)
     */
    public function paymenthistory(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user || !$user->restaurant) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $restaurantId = $user->restaurant->id;

            $payments = Order::where('restaurant_id', $restaurantId)
                ->orderBy('created_at', 'desc')
                ->get([
                    'id',
                    'total_amount',
                    'payment_method',
                    'created_at',
                    'payment_status'
                ]);

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
