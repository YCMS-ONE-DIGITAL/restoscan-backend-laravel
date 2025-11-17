<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AuthToken
{
    // ✅ Laravel 12+ मध्ये नाव define करणे पुरेसे आहे
    public static string $name = 'auth.token';

    public function handle(Request $request, Closure $next)
{
    // Accept both Cookie + Bearer Token
    $token = $request->cookie('auth_token') ?? $request->bearerToken();

    if (!$token) {
        return response()->json([
            'status' => 'error',
            'message' => 'Missing token',
        ], 401);
    }

    $user = User::where('remember_token', $token)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid or expired token',
        ], 401);
    }

    // Attach authenticated user
    $request->merge(['auth_user' => $user]);

    return $next($request);
}

}
