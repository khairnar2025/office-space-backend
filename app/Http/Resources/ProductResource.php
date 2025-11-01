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
            'status' => (int)$this->status ? 1 : 0,

            // 'thumbnail' => $this->when($this->thumbnail ?? false, fn() => $this->thumbnail ? Storage::url($this->thumbnail) : null),
            // 'gallery' => collect($this->gallery ?? [])->map(fn($p) => Storage::url($p)),

            'delivery_pincodes' => $this->deliveryPincodes->map(function ($pincode) {
                return [
                    'id' => $pincode->id,
                    'pincode' => $pincode->pincode,
                    // 'shipping_cost' => $pincode->shipping_cost,
                    // 'is_serviceable' => $pincode->is_serviceable,
                    // 'delivery_days_min' => $pincode->delivery_days_min,
                    // 'delivery_days_max' => $pincode->delivery_days_max,
                ];
            }),

            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
