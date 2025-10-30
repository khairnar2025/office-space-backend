<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product->title,
            'thumbnail' => $this->product->thumbnail
                ? asset('storage/' . $this->product->thumbnail)
                : null,
            'color' => $this->color?->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->price * $this->quantity,
        ];
    }
}
