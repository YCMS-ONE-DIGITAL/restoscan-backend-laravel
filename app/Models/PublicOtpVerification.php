<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicOtpVerification extends Model
{
    //
     protected $fillable = [
        'restaurant_id',
        'phone',
        'otp',
        'is_verified',
        'otp_expires_at',
        'verified_expires_at',
    ];

    protected $dates = [
        'otp_expires_at',
        'verified_expires_at',
    ];
}
