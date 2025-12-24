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
$rawToken = bin2hex(random_bytes(32));   // SAFE TOKEN
    $hashedToken = hash('sha256', $rawToken);     // database

    // SAVE IN DB
    $user->remember_token = $hashedToken;
    $user->login_ip = $request->ip();
    $user->login_ua = $request->userAgent();
    $user->save();

    // $isProduction = app()->environment('production');


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
        //    env('SESSION_DOMAIN'),
        //     app()->environment('production'),
            null,
            false,
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
    // $rawToken = urldecode($rawToken);

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
    
    // $isProduction = app()->environment('production');


    return response()
        ->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ])
        ->cookie(
        'auth_token', 
        '',           // Empty value
        -1,           // Expire now
        '/', 
        //  env('SESSION_DOMAIN'),
        //  app()->environment('production'),
        null,
        false,
        true,
        false,
        'Lax'
    );
}



public function changePassword(Request $request)
{
    $user = $request->attributes->get('auth_user');

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    // Validation
    $request->validate([
        'old_password' => 'required|string|min:6',
        'new_password' => 'required|string|min:6|confirmed', // requires new_password_confirmation
    ]);

    // Check old password
    if (!\Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Old password is incorrect'
        ], 400);
    }

    // Update password
    $user->password = \Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Password changed successfully'
    ]);
}


public function update(Request $request)
{
    $user = $request->attributes->get('auth_user');

    // Validate input
    $request->validate([
        'name'  => 'required|string|max:255',
        'phone' => 'nullable|string|max:15',
    ]);

    // Update user details
    $user->name = $request->name;
    $user->phone_number = $request->phone; // match DB column
    $user->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);
}

}
