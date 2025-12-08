<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    // GET RESTAURANT ID
    private function getRestaurantId(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        if (!$user || !$user->restaurant->id) {
            return null;
        }

        return $user->restaurant->id;
    }


      // LIST STAFF
    public function Staff_List(Request $request)
    {
  // get restaurant id from logged in user

        try {

                    $restaurantId = $this->getRestaurantId($request);
            if (!$restaurantId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant not found'
            ], 400);
        }

                $staff = Staff::where('restaurant_id', $restaurantId)->get();

              return response()->json([
            'status' => 'success',
            'data' => $staff
        ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['status' => 'success', 'data' => $staff]);
    }

    // CREATE STAFF
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:staffs,email',
                'phone' => 'required',
                'role' => 'required',
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // get restaurant id from logged in user
        $restaurantId = $this->getRestaurantId($request);

        if (!$restaurantId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant not found'
            ], 400);
        }

        // create staff
        $staff = Staff::create([
            'restaurant_id' => $restaurantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Crypt::encrypt($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff created successfully',
            'data' => $staff
        ]);
    }



    public function update(Request $request, $id)
{
    try {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:staffs,email,' . $id,
            'role' => 'required',
        ]);

        // update basic fields
        $staff->name = $request->name;
        $staff->email = $request->email;
        $staff->phone = $request->phone;
        $staff->role = $request->role;

        // UPDATE PASSWORD IF SENT
        if ($request->filled('password')) {
            $staff->password = Crypt::encrypt($request->password);
        }

        $staff->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Staff updated successfully',
            'data' => $staff
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


  // SHOW SINGLE STAFF
    public function show($id)
    {
        try {
            $staff = Staff::find($id);

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $staff]);

        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }


     // DELETE STAFF
    public function destroy($id)
    {
        try {
            $staff = Staff::find($id);

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            $staff->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Deleted'
            ]);

        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }


     // LOGIN
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required',
                'password' => 'required'
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        try {
            $staff = Staff::where('email', $request->email)->first();

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Invalid email'], 401);
            }

            if (Crypt::decrypt($staff->password) !== $request->password) {
                return response()->json(['status' => 'error', 'message' => 'Invalid password'], 401);
            }

            if ($staff->is_logged_in) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Already logged in on another device'
                ], 403);
            }

            $staff->is_logged_in = true;
            $staff->login_device = $request->device ?? $request->header('User-Agent');
            $staff->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => $staff
            ]);

        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // LOGOUT
    public function logout(Request $request)
    {
        try {
            $staff = Staff::find($request->id);

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Invalid staff'], 404);
            }

            $staff->is_logged_in = false;
            $staff->login_device = null;
            $staff->save();

            return response()->json(['status' => 'success', 'message' => 'Logout successful']);

        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

}
