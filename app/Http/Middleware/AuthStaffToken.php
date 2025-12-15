<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Staff;

use Symfony\Component\HttpFoundation\Response;

class AuthStaffToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
       public static string $name = 'staff.auth';

    public function handle(Request $request, Closure $next): Response
    {
          $staffId = $request->header('Staff-Id');
          

        if (!$staffId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff-Id missing'
            ], 401);
        }

        $staff = Staff::where('id', $staffId)
            ->where('is_logged_in', true)
            ->first();

        if (!$staff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->attributes->set('auth_staff', $staff);

        return $next($request);
    }
}
