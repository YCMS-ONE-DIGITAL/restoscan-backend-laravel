<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class MenuItem extends Model
{
    //
     use HasFactory;

    protected $fillable = [
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
     * Accessor: readable version of the 'type' field.
     */
    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'veg' => 'Veg',
            'non_veg' => 'Non-Veg',
            'egg' => 'Egg',
            default => 'Unknown',
        };
    }

    /**
     * Scope to easily filter available items.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to filter by food type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
