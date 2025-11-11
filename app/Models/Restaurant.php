<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // ✅ imported User model


class Restaurant extends Model
{
    use HasFactory; // ✅ keep this

    protected $fillable = [
        'user_id',
        'restaurant_name',
        'address',
        'city',
        'state',
        'pincode',
        'contact_number',
    ];

    // 🔹 One restaurant belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 One restaurant has many menu items (future use)
    // public function menuItems()
    // {
    //     return $this->hasMany(MenuItem::class);
    // }
}
