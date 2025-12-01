<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ForgetPasswordController extends Controller
{
    // 1️⃣ SEND OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        $otp = rand(100000, 999999);

        PasswordResetToken::updateOrCreate(
            ['email' => $request->email],
            [
                'token' => $otp,
                'created_at' => now()
            ]
        );

        // TODO: Email/SMS
        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent',
            'otp_debug' => $otp
        ]);
    }


    // 2️⃣ VERIFY OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'otp' => 'required'
        ]);

        $record = PasswordResetToken::where([
            'email' => $request->email,
            'token' => $request->otp
        ])->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP'
            ], 400);
        }

        // OTP expiry 10 minutes
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP expired'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified'
        ]);
    }


    // 3️⃣ RESET PASSWORD
  public function resetPassword(Request $request)
{
    try {

        $request->validate([
            'email' => 'required',
            'otp' => 'required',
            'new_password' => 'required|min:6'
        ]);

        $record = PasswordResetToken::where([
            'email' => $request->email,
            'token' => $request->otp
        ])->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

PasswordResetToken::where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successful'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage() // ⭐ HERE — exact error shown
        ], 500);

    }
}


}
