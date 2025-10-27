<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail
                ? asset('storage/' . $this->thumbnail)
                : null,

            // ✅ Full URLs for gallery images
            'gallery' => $this->gallery
                ? collect($this->gallery)->map(fn($img) => asset('storage/' . $img))
                : [],
            'colors' => $this->colors ?? [],
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'in_stock' => (bool) $this->in_stock ? 1 : 0,
            'status' => (bool) $this->status ? 1 : 0,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
