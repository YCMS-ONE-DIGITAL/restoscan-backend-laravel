<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use App\Models\Restaurant_table;

class Order extends Model
{
    //
     protected $fillable = [
        'restaurant_id',
        'table_id',
        'status',
        'total_amount',
        'payment_status',
        'payment_method',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table()
    {
        return $this->belongsTo(Restaurant_table::class, 'table_id');
    }
}
