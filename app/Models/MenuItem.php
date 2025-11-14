<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Restaurant;

class MenuItem extends Model
{
    //
     use HasFactory;

    // IMPORTANT: Your custom table name
    protected $table = 'menu_itmes';

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'type',
        'is_available',
    ];

       /**
     * Each menu item belongs to a category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

      /**
     * Each menu item belongs to a restaurant.
     */
       public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
