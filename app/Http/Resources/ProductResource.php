<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail_url,
            'gallery' => $this->gallery_urls,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'colors' => $this->colors->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'status' => (int)$c->status]),
            'in_stock' => (int)$this->in_stock,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'status' => (int)$this->status,
        ];
    }
}
