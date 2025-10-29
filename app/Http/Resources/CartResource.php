<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_title' => $item->product->title,
                    'thumbnail' => $item->product->thumbnail_url ?? null,
                    'color' => $item->color ? [
                        'id' => $item->color->id,
                        'name' => $item->color->name,
                    ] : null,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'original_price' => (float) $item->product->price,
                    'discount_price' => (float) ($item->product->discount_price ?? $item->product->price),
                    'subtotal' => (float) $item->price * $item->quantity,

                ];
            }),
            'total' => $this->items->sum(fn($i) => $i->price * $i->quantity),
        ];
    }
}
