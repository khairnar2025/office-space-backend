<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id'   => $this->id,
            'status'     => $this->status,
            'total_amount' => $this->total_amount,
            'currency'   => $this->currency,
            'payment_id' => $this->razorpay_payment_id,
            'created_at' => $this->created_at->format('d M Y, h:i A'),
            
            'shipping' => [
                'name'    => $this->name,
                'phone'   => $this->phone,
                'address' => $this->address,
                'pincode' => $this->pincode,
                'city'    => $this->city,
                'state'   => $this->state,
            ],

            'items' => OrderItemResource::collection($this->whenLoaded('items'))
        ];
    }
}
