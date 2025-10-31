<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'color_id' => $this->color_id,
            'color_name' => $this->color?->name,
            'price' => (float)$this->price,
            'discount_price' => $this->discount_price ? (float)$this->discount_price : null,
            'quantity' => (int)$this->quantity,
            'in_stock' => (bool)$this->is_in_stock,
            'thumbnail' => $this->thumbnail_url,
            'gallery' => $this->gallery_urls,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
