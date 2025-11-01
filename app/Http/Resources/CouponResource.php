<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'coupon_code'        => $this->coupon_code,
            'start_date'         => $this->start_date->toDateString(),
            'end_date'           => $this->end_date->toDateString(),
            'discount_type'      => $this->discount_type,
            'discount_percentage' => $this->discount_percentage,
            'description'        => $this->description,
            'status'             => (int) $this->status ? 1 : 0,
            'specialise'         => (int) $this->specialise ? 1 : 0,
            'created_at'         => $this->created_at->toDateTimeString(),
            'updated_at'         => $this->updated_at->toDateTimeString(),
        ];
    }
}
