<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Restaurant_table;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use Throwable;

class StaffController extends Controller
{
    /* ===============================
       GET RESTAURANT ID
    =============================== */
    private function getRestaurantId(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        if (!$user || !$user->restaurant) {
            return null;
        }

        return $user->restaurant->id;
    }


    public function handle($request, Closure $next)
{
    $token = $request->bearerToken();

    if (!$token) {
        return response()->json(['status' => 'error', 'message' => 'Token missing'], 401);
    }

    $staff = Staff::where('api_token', $token)->first();

    if (!$staff) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    $request->attributes->set('auth_staff', $staff);

    return $next($request);
}


    /* ===============================
       LIST STAFF
    =============================== */
    public function Staff_List(Request $request)
    {
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant not found'
            ], 400);
        }

        $staff = Staff::where('restaurant_id', $restaurantId)
            ->select('id', 'name', 'email', 'phone', 'role', 'is_logged_in')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $staff
        ]);
    }

    /* ===============================
       CREATE STAFF
    =============================== */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:staffs,email',
                'phone'    => 'required|string',
                'role'     => 'required|string',
                'password' => 'required|min:4',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }

        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant not found'
            ], 400);
        }

        $staff = Staff::create([
            'restaurant_id' => $restaurantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Hash::make($request->password), // ✅ HASHED
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff created successfully',
            'data' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
            ]
        ]);
    }

    /* ===============================
       UPDATE STAFF
    =============================== */
    public function update(Request $request, $id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff not found'
            ], 404);
        }

        $request->validate([
            'name'  => 'required|string',
            'email' => 'required|email|unique:staffs,email,' . $id,
            'role'  => 'required|string',
            'phone' => 'required|string',
        ]);

        $staff->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role'  => $request->role,
        ]);

        // OPTIONAL PASSWORD UPDATE
        if ($request->filled('password')) {
            $staff->password = Hash::make($request->password);
            $staff->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Staff updated successfully'
        ]);
    }

    /* ===============================
       SHOW SINGLE STAFF
    =============================== */
    public function show($id)
    {
        $staff = Staff::select(
            'id', 'name', 'email', 'phone', 'role', 'is_logged_in'
        )->find($id);

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $staff
        ]);
    }

    /* ===============================
       DELETE STAFF
    =============================== */
    public function destroy($id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff not found'
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Staff deleted successfully'
        ]);
    }

    /* ===============================
       STAFF LOGIN
    =============================== */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $staff = Staff::where('email', $request->email)->first();

        if (!$staff || !Hash::check($request->password, $staff->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password'
            ], 401);
        }

        if ($staff->is_logged_in) {
            return response()->json([
                'status' => 'error',
                'message' => 'Already logged in on another device'
            ], 403);
        }

        $token = bin2hex(random_bytes(40));

        $staff->update([
            'is_logged_in' => true,
            'login_device' => $request->header('User-Agent'),
            'api_token' => $token,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'role' => $staff->role,
                'restaurant_id' => $staff->restaurant_id,
            ]
        ]);
    }

    /* ===============================
       STAFF LOGOUT
    =============================== */
    public function logout(Request $request)
    {
        $staff = $request->attributes->get('auth_staff');

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $staff->update([
            'is_logged_in' => false,
            'login_device' => null,
            'api_token' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }



     public function tablelist(Request $request)
    {
        // Staff injected by middleware
        $staff = $request->attributes->get('auth_staff');
        // dd($staff);
        // dd($staff->restaurant_id);


        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $tables = Restaurant_table::where(
                'restaurant_id',
                $staff->restaurant_id
            )
            ->select(
                'id',
                'table_no',
                'seating_number',
                'status'
            )
            ->orderBy('table_no')
            ->get();
            

        return response()->json([
            'status' => 'success',
            'data' => $tables
        ]);
    }

    /* ===============================
       UPDATE TABLE STATUS
    =============================== */
    public function updateTableStatus(Request $request, $id)
    {
        $staff = $request->attributes->get('auth_staff');

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'status' => 'required|in:available,occupied,reserved'
        ]);

        $table = Restaurant_table::where('id', $id)
            ->where('restaurant_id', $staff->restaurant_id)
            ->first();

        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table not found'
            ], 404);
        }

        $table->status = $request->status;
        $table->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Table status updated',
            'data' => $table
        ]);
    }


    public function categories(Request $request)
    {
        try {
            
                    $staff = $request->attributes->get('auth_staff');
if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

            $restaurantId = $staff->restaurant_id;

            if (!$restaurantId) {
                return response()->json(['message' => 'Restaurant not found'], 404);
            }

            $categories = Category::where('restaurant_id', $restaurantId)->get();

            return response()->json($categories);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


public function fetch_menu_items_list(Request $request)
{
    try {
        $staff = $request->attributes->get('auth_staff');

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $restaurantId = $staff->restaurant_id;

        $query = MenuItem::where('restaurant_id', $restaurantId)
            ->where('is_available', 1); // ✅ always only available items

        // ✅ CATEGORY FILTER
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // ✅ VEG / NONVEG FILTER
        if ($request->filled('type')) {
            $query->where('type', $request->type); // veg / nonveg
        }

        // ✅ SEARCH FILTER
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('name')->get()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


public function placeOrder(Request $request)
{
    try {
        $staff = $request->attributes->get('auth_staff');

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.item_note' => 'nullable|string',

            'table_id' => 'nullable|exists:restaurant_tables,id',
            'table_name' => 'nullable|string',
            'order_note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        $total = collect($validated['items'])->sum(
            fn ($i) => $i['price'] * $i['quantity']
        );

        $order = Order::create([
            'restaurant_id' => $staff->restaurant_id,
            'staff_id' => $staff->id,
            'table_name' => $validated['table_name'] ?? null,
            'table_id' => $validated['table_id'] ?? null,
            'order_note' => $validated['order_note'] ?? null,
            // 'payment_status' => 'nullable|in:pending,paid',
            // 'payment_method' => 'nullable|in:cash,upi,card',
            'total_amount' => $total,
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'total' => $item['price'] * $item['quantity'],
                'item_note' => $item['item_note'] ?? null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}



    // fetchorder
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
            $staff = $request->attributes->get('auth_staff');

            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
             $restaurantId = $staff->restaurant_id;
            
            if (!$restaurantId) {
                return response()->json(['success' => false, 'message' => 'Restaurant not found'], 404);
            }

            // $perPage = (int) $request->get('per_page', 10);

            $orders = Order::with(['table', 'items.menu_item'])
                ->where('restaurant_id', $restaurantId)
                ->orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                // 'pagination' => [
                //     'total' => $orders->total(),
                //     'per_page' => $orders->perPage(),
                //     'current_page' => $orders->currentPage(),
                //     'last_page' => $orders->lastPage(),
                //     'next_page_url' => $orders->nextPageUrl(),
                //     'prev_page_url' => $orders->previousPageUrl(),
                // ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

public function ordersByTable(Request $request)
{
    $staff = $request->attributes->get('auth_staff');

    if (!$staff) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    $tableId = $request->query('table_id');

    if (!$tableId) {
        return response()->json([
            'status' => 'error',
            'message' => 'table_id is required'
        ], 400);
    }

    $order = Order::with(['items.menu_item', 'table'])
        ->where('restaurant_id', $staff->restaurant_id) // ✅ IMPORTANT
        ->where('table_id', $tableId)
        ->where('status', 'pending') // optional but recommended
        ->latest()
        ->first();

    if (!$order) {
        return response()->json([
            'status' => 'success',
            'data' => null   // 👈 empty but NOT error
        ]);
    }

    return response()->json([
        'status' => 'success',
        'data' => $order
    ]);
}




}
