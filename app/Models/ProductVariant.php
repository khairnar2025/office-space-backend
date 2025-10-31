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
        'gallery'
    ];

    protected $casts = ['gallery' => 'array', 'in_stock' => 'boolean'];

    protected $appends = ['thumbnail_url', 'gallery_urls', 'is_in_stock'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? Storage::url($this->thumbnail) : null;
    }

    public function getGalleryUrlsAttribute()
    {
        return collect($this->gallery ?? [])
            ->map(fn($p) => Storage::url($p))
            ->values()
            ->toArray();
    }

    public function getIsInStockAttribute()
    {
        return $this->quantity > 0;
    }
}
