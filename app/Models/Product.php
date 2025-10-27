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
        'colors',
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
}
