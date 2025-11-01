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
            'in_stock' => (int)$this->is_in_stock ? 1 : 0,
            'thumbnail' => $this->thumbnail_url,
            'gallery' => $this->gallery_urls,
            'status' => (int)$this->status ? 1 : 0,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
