<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Restaurant;

use Illuminate\Database\Eloquent\Model;

class Restaurant_table extends Model
{
    //
     use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'table_no',
        'seating_number',
        'status'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
