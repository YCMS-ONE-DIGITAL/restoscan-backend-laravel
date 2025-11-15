<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MenuItem;

class OrderItem extends Model
{
    //
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'price',
        'total',
    ];

   public function menuItem()
{
    return $this->belongsTo(MenuItem::class, 'menu_item_id', 'id');
}

}
