<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Restaurant_table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
        $staff = Staff::where('api_token', $request->bearerToken())->first();

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
}
