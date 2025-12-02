<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

    // Save OTP
    PasswordResetToken::updateOrCreate(
        ['email' => $request->email],
        [
            'token' => $otp,
            'created_at' => now()
        ]
    );

    // Email details
    $email = $request->email;
    $name = $user->name ?? "User";
    $year = date('Y');

    // Email HTML Template
    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin:auto; border:1px solid #ddd; border-radius:8px; overflow:hidden;'>
        <div style='background: linear-gradient(90deg, #ff6b6b, #ff8e53); padding: 20px; text-align:center;'>
            <h2 style='color:#fff; margin:0;'>Password Reset OTP</h2>
        </div>
        <div style='padding:25px;'>
            <p>Hello <strong>{$name}</strong>,</p>
            <p>You requested to reset your password.</p>
            <p>Your One-Time Password (OTP) is:</p>
            <div style='text-align:center; margin:30px 0;'>
                <div style='font-size:32px; letter-spacing:8px; font-weight:bold; background-color:#f3f3f3; padding:20px; border-radius:10px; display:inline-block;'>$otp</div>
            </div>
            <p>This OTP is valid for <strong>10 minutes</strong>. Please do not share it with anyone.</p>
            <hr style='border:none; border-top:1px solid #eee; margin:30px 0;'>
            <p>If you did not request this, please ignore this email.</p>
            <p style='font-size:13px; color:#999;'>© $year RestoScan. All rights reserved.</p>
        </div>
    </div>";

    // Send email
    Mail::html($html, function ($message) use ($email) {
        $message->to($email)
                ->subject('🔐 RestoScan Password Reset OTP');
    });

    return response()->json([
        'status' => 'success',
        'message' => 'OTP sent successfully to your email'
    ]);
}


public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required'
    ]);

    $record = PasswordResetToken::where('email', $request->email)
        ->where('token', (string)$request->otp)
        ->first();

    if (!$record) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid OTP'
        ], 400);
    }

    // Expiry check
    if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
        return response()->json([
            'status' => 'error',
            'message' => 'OTP expired'
        ], 400);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'OTP verified successfully'
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

        // 1. Find OTP record
        $record = PasswordResetToken::where('email', $request->email)
            ->where('token', (string)$request->otp)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP'
            ], 400);
        }

        // 2. OTP EXPIRED CHECK (10 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP expired'
            ], 400);
        }

        // 3. Get user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // 4. Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 5. Delete OTP
        PasswordResetToken::where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successful'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);

    }
}


}
