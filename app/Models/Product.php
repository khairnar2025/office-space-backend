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
        'in_stock',
        'price',
        'discount_price',
        'status',
    ];

    protected $casts = [
        'gallery' => 'array',
        'in_stock' => 'boolean',
        'status' => 'boolean',
    ];
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class);
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    public function getGalleryUrlsAttribute()
    {
        $gallery = $this->gallery ?? [];

        // Make sure it's an array
        if (!is_array($gallery)) {
            $gallery = json_decode($gallery, true) ?? [];
        }

        // Map only valid strings
        return array_values(array_filter(array_map(function ($image) {
            if (is_string($image) && !empty($image)) {
                return asset('storage/' . $image);
            }
            return null;
        }, $gallery)));
    }
    public function deliveryPincodes()
    {
        return $this->belongsToMany(
            DeliveryPincode::class,
            'product_pincode', // pivot table name
            'product_id',      // foreign key on pivot table for Product
            'delivery_pincode_id' // foreign key on pivot table for DeliveryPincode
        );
    }
    public function getFinalPriceAttribute()
    {
        return $this->discount_price ?? $this->price;
    }
}
