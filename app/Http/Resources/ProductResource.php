<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'status' => (bool)$this->status,

            'thumbnail' => $this->when($this->thumbnail ?? false, fn() => $this->thumbnail ? Storage::url($this->thumbnail) : null),
            'gallery' => collect($this->gallery ?? [])->map(fn($p) => Storage::url($p)),

            'delivery_pincodes' => $this->deliveryPincodes->pluck('pincode'),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
