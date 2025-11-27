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

    // ---------------------------------------
    // GENERATE RAW + HASH TOKEN (same as signup)
    // ---------------------------------------
    $rawToken = base64_encode(random_bytes(64));  // cookie
    $hashedToken = hash('sha256', $rawToken);     // database

    // SAVE IN DB
    $user->remember_token = $hashedToken;
    $user->login_ip = $request->ip();
    $user->login_ua = $request->userAgent();
    $user->save();

    // SEND RAW TOKEN IN COOKIE (localhost-friendly)
    return response()
        ->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ],
        ])
        ->cookie(
            'auth_token',
            $rawToken,
            60 * 24 * 7,
            '/',
            null,
            false,   // ✔ localhost → secure=false
            true,
            false,
            'Lax'
        );
}


    // 🔹 LOGOUT
 public function logout(Request $request)
{
    // Get raw token
    $rawToken = $request->cookie('auth_token') ?? $request->bearerToken();

    if (!$rawToken) {
        return response()->json([
            'status' => 'error',
            'message' => 'Token missing',
        ], 400);
    }

    // 🔥 Fix: decode URL encoded token
    $rawToken = urldecode($rawToken);

    // Hash it to match DB
    $hashed = hash('sha256', $rawToken);

    $user = User::where('remember_token', $hashed)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid token',
        ], 401);
    }

    // Clear credentials
    $user->remember_token = null;
    $user->login_ip = null;
    $user->login_ua = null;
    $user->save();

    return response()
        ->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ])
        ->withCookie(cookie()->forget('auth_token'));
}

}
