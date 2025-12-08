<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    //
    protected $table = 'staffs';

    protected $fillable = [
        'restaurant_id',
        'name',
        'email',
        'phone',
        'role',
        'password',
        'is_logged_in',
        'login_device',
    ];
}
