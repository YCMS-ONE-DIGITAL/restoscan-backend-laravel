<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Otp;
use App\Models\User;

class OtpController extends Controller
{
    // 🔹 Step 1: Send OTP
    public function sendOtp(Request $request)
    {
       $validated = $request->validate([
        'email' => 'required|email',
        'phone_number' => 'required|digits_between:10,12',
        'name' => 'required|string|min:2|max:50',
        'password' => 'required|string|min:6|max:50',
    ]);

    // Check if user already exists
    if (User::where('email', $request->email)->exists()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Email already registered. Please login.'
        ], 409);
    }

    if (User::where('phone_number', $request->phone_number)->exists()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Phone number already registered.'
        ], 409);
    }

        $email = $request->email;
        $otp = rand(100000, 999999);

         // Save OTP
    Otp::updateOrCreate(
        ['email' => $request->email],
        [
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]
    );

        // 🎨 Email Template
        $year = date('Y');
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin:auto; border:1px solid #ddd; border-radius:8px; overflow:hidden;'>
            <div style='background: linear-gradient(90deg, #007bff, #00c6ff); padding: 20px; text-align:center;'>
                <h2 style='color:#fff; margin:0;'>RestoScan Signup Verification</h2>
            </div>
            <div style='padding:25px;'>
                <p>Hello <strong>{$request->name}</strong>,</p>
                <p>Welcome to <strong>RestoScan</strong> 🎉</p>
                <p>Your One-Time Password (OTP) for signup verification is:</p>
                <div style='text-align:center; margin:30px 0;'>
                    <div style='font-size:32px; letter-spacing:8px; font-weight:bold; background-color:#f3f3f3; padding:20px; border-radius:10px; display:inline-block;'>$otp</div>
                </div>
                <p>This OTP is valid for <strong>10 minutes</strong>. Please do not share it with anyone.</p>
                <hr style='border:none; border-top:1px solid #eee; margin:30px 0;'>
                <p>Thanks for choosing <strong>RestoScan</strong> — Simplifying restaurant management!</p>
                <p style='font-size:13px; color:#999;'>© $year RestoScan. All rights reserved.</p>
            </div>
        </div>";

        // ✉️ Send Email
        Mail::html($html, function ($message) use ($email) {
            $message->to($email)
                    ->subject('🔐 Your OTP for RestoScan Signup');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent successfully to your email',
            'otp' =>"$otp",
        ]);
    }

    // 🔹 Step 2: Verify OTP and Create User
   public function verifyOtp(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
        'name' => 'required|string|min:2|max:50',
        'phone_number' => 'required|digits_between:10,12',
        'password' => 'required|string|min:6|max:50',
    ]);

    // Check if user already registered
    if (User::where('email', $request->email)->exists()) {
        return response()->json([
            'status' => 'error',
            'message' => 'User already registered.'
        ], 409);
    }

    // Get OTP
    $otpRecord = Otp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('is_used', false)
        ->latest()
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid OTP'
        ], 400);
    }

    if ($otpRecord->expires_at < now()) {
        return response()->json([
            'status' => 'error',
            'message' => 'OTP expired'
        ], 400);
    }

    // Mark OTP used
    $otpRecord->update(['is_used' => true]);

    // Create user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'User registered successfully',
        'user' => $user
    ]);
}

}
