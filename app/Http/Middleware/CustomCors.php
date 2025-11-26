<?php
namespace App\Http\Middleware;

use Closure;

class CustomCors
{
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin');

        $allowedOrigins = [
            'http://localhost:5174',
        ];
        

        // Handle OPTIONS (Preflight) request FIRST
        if ($request->getMethod() === "OPTIONS") {
            $response = response()->json('OK', 200);
        } else {
            $response = $next($request);
        }

        // Allow only specific origins
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // Required for PUT, DELETE, POST, PATCH
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With');

        return $response;
    }
}
