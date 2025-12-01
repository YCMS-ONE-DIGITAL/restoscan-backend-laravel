<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'image'
    ];

    /**
     * Each category belongs to a menu.
     */
    public function restaurant()
{
    return $this->belongsTo(Restaurant::class);
}


    /**
     * A category can have many menu items.
     */
    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }
}
