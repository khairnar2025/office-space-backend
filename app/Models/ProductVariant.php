<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'price',
        'discount_price',
        'quantity',
        'in_stock',
        'thumbnail',
        'gallery',
        'status'
    ];

    protected $casts = ['gallery' => 'array', 'in_stock' => 'boolean', 'status' => 'boolean'];

    protected $appends = [
        'thumbnail_url',
        'gallery_urls',
        'is_in_stock',
        'color_info_id',
        'color_name',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail
            ? asset(Storage::url($this->thumbnail))
            : null;
    }

    public function getGalleryUrlsAttribute()
    {
        return collect($this->gallery ?? [])
            ->map(fn($p) => asset(Storage::url($p)))
            ->values()
            ->toArray();
    }

    public function getIsInStockAttribute()
    {
        return $this->quantity > 0;
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function getColorInfoAttribute()
    {
        if (!$this->relationLoaded('color') || !$this->color) {
            return null;
        }

        return [
            'color_id' => $this->color->id,
            'color_name' => $this->color->name,
        ];
    }
    public function getColorIdFromRelationAttribute()
    {
        return $this->color?->id;
    }
    public function getColorInfoIdAttribute()
    {
        return $this->color?->id;
    }

    public function getColorInfoNameAttribute()
    {
        return $this->color?->name;
    }
    public function getColorNameAttribute()
    {
        return $this->color?->name;
    }
}
