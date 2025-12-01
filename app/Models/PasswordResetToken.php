<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    //
    protected $table = 'password_reset_tokens';   // ⭐ YOUR TABLE NAME

    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    public $timestamps = false;
        protected $primaryKey = null;                // ⭐ No primary key
    public $incrementing = false;                // ⭐ Prevents Laravel from using ID
}
