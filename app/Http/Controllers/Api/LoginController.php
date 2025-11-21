<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class LoginController extends Controller
{


    
    // 🔹 LOGIN
    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found',
        ], 404);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid password',
        ], 401);
    }

    // Create secure token
    $token = base64_encode(Str::random(60));
    $user->remember_token = $token;
    $user->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ],
    ])->cookie(
        'auth_token',
        $token,
        60 * 24 * 7,  // 7 days
        '/',
        null,
        true,         // secure
        true,         // HttpOnly
        false,
        'None'
    );
}


    // 🔹 LOGOUT
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token missing',
            ], 400);
        }

        // ✅ Find user with remember_token
        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ], 401);
        }

        // ✅ Clear token
        $user->remember_token = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }
}
