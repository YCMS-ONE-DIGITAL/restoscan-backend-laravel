<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicOtpVerification;

class PublicOtpController extends Controller
{
    // SEND OTP
    public function sendOtp(Request $request)
{
    $request->validate([
        'restaurant_id' => 'required|exists:restaurants,id',
        'phone' => 'required|digits:10',
    ]);

    // 🔥 Check existing record
    $existing = PublicOtpVerification::where('restaurant_id', $request->restaurant_id)
        ->where('phone', $request->phone)
        ->first();

    // 🔥 USER ALREADY VERIFIED & VALID
    if ($existing && $existing->is_verified && $existing->verified_expires_at > now()) {
        return response()->json([
            'status' => true,
            'message' => "Already verified — no OTP needed",
            'already_verified' => true
        ]);
    }

    // 🔥 Generate 4-digit OTP
    $otp = rand(1111, 9999);

    $expiry = now()->addMinutes(10);

    PublicOtpVerification::updateOrCreate(
        [
            'restaurant_id' => $request->restaurant_id,
            'phone' => $request->phone
        ],
        [
            'otp' => $otp,
            'is_verified' => false,
            'otp_expires_at' => $expiry,
            'verified_expires_at' => null
        ]
    );

    // ⭐ Fast2SMS DLT Format
    $fields = [
        "sender_id" => "DMSTCH",
        "message" => "159475",    // TEMPLATE ID
        "variables_values" => $otp,
        "route" => "dlt",
        "numbers" => $request->phone,
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => [
            "authorization: " . env("FAST2SMS_API_KEY"),
            "accept: */*",
            "cache-control: no-cache",
            "content-type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return response()->json([
            'status' => false,
            'message' => "SMS failed: " . $err
        ], 500);
    }

    return response()->json([
        'status' => true,
        'message' => "OTP sent successfully",
        'already_verified' => false
    ]);
}


    // VERIFY OTP
   public function verifyOtp(Request $request)
{

    // \Log::info("VERIFY_REQUEST", $request->all());

    $request->validate([
        'restaurant_id' => 'required|exists:restaurants,id',
        'phone' => 'required|digits:10',
        'otp'   => 'required|digits:4'
    ]);


    // \Log::info("VERIFY_REQUEST", $request->all());

$record = PublicOtpVerification::where('restaurant_id', $request->restaurant_id)
    ->where('phone', $request->phone)
    ->orderBy('id', 'desc')
    ->first();

// \Log::info("VERIFY_RECORD_BEFORE", $record ? $record->toArray() : null);


    // ⭐ ALWAYS GET LATEST OTP RECORD
    $record = PublicOtpVerification::where('restaurant_id', $request->restaurant_id)
        ->where('phone', $request->phone)
        ->orderBy('id', 'desc')
        ->first();

    if (!$record) {
        return response()->json(['status'=>false, 'message'=>'OTP not found'], 404);
    }

    // ⭐ WRONG OTP
    if ($record->otp != $request->otp) {
        return response()->json(['status'=>false, 'message'=>'Invalid OTP'], 400);
    }

    // ⭐ EXPIRED OTP
    if (now()->greaterThan($record->otp_expires_at)) {
        return response()->json(['status'=>false, 'message'=>'OTP expired'], 400);
    }

    // ⭐ UPDATE VERIFIED → valid for 2 days
    $record->update([
        'is_verified' => true,
        'verified_expires_at' => now()->addDays(2)
    ]);
    // \Log::info("VERIFY_RECORD_AFTER", $record->fresh()->toArray());

    return response()->json([
        'status' => true,
        'message' => "OTP verified successfully"
    ]);
}

}
