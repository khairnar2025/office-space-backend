<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'gallery',
        'category_id',
        'color_id',
        'in_stock',
        'price',
        'discount_price',
        'status',
    ];

    protected $casts = [
        'gallery' => 'array',
        'colors' => 'array',
        'in_stock' => 'boolean',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class);
    }
}
