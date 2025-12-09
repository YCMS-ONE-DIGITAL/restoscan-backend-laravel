<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use App\Models\Restaurant_table;
use App\Models\Restaurant;
use App\Models\CustomerDetail;
use App\Models\Staff;

class Order extends Model
{
    //
     protected $fillable = [
        'restaurant_id',
        'table_id',
        'customer_id',
        'staff_id',
        'order_type',
        'status',
        'total_amount',
        'payment_status',
        'payment_method',
        'order_note'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table()
    {
        return $this->belongsTo(Restaurant_table::class, 'table_id');
    }

    public function customer() {
   return $this->belongsTo(CustomerDetail::class, 'customer_id');
}

public function restaurant()
{
    return $this->belongsTo(Restaurant::class, 'restaurant_id');
}

public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');  // 👈 NEW RELATION
    }




}
