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
        // Read raw token from cookie or bearer
        $rawToken = $request->cookie('auth_token') ?? $request->bearerToken();

        if (!$rawToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing token',
            ], 401);
        }

        // Hash the raw token to compare with DB
        $rawToken = urldecode($rawToken);   // <-- THIS SOLVES EVERYTHING

        $hashed = hash('sha256', $rawToken);

        $user = User::where('remember_token', $hashed)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // 🔒 DEVICE + IP LOCK (Prevents token theft)
        if (
            $user->login_ip !== $request->ip() ||
            $user->login_ua !== $request->userAgent()
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token used from different device or browser.',
            ], 401);
        }

        // Attach authenticated user
        $request->attributes->set('auth_user', $user);


        
//         \Log::info("RAW TOKEN FROM COOKIE: " . ($request->cookie('auth_token')));
// \Log::info("RAW TOKEN URLDECODED: " . urldecode($request->cookie('auth_token')));
// \Log::info("HASHED TOKEN: " . hash('sha256', urldecode($request->cookie('auth_token'))));
// \Log::info("Middleware executed!");



        return $next($request);
    }

}
