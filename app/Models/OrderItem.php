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
        'item_note'
    ];

   public function menu_item()
{
    return $this->belongsTo(MenuItem::class, 'menu_item_id', 'id');
}

}
