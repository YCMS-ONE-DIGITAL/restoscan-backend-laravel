<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    protected $table = 'customer_details';

    protected $fillable = [
          'restaurant_id',
        'name',
        'phone',
    ];

    public $timestamps = true;

    // ⭐ One customer can have many orders
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
