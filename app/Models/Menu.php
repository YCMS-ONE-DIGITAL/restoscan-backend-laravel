<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Restaurant;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    //
 use HasFactory;

    protected $fillable = ['restaurant_id', 'name'];

   /**
     * Each menu belongs to a restaurant.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * A menu can have many categories.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }


}
