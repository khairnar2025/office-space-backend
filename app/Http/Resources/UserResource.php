<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'status'            => $this->status ? 1 : 0,
            'profile_image'     => $this->profile_image ? asset('storage/' . $this->profile_image) : null,
            'company_name'      => $this->company_name,
            'gst_no'            => $this->gst_no ?? null,
            'phone_no'          => $this->phone_no ?? null,
            'billing_address'   => $this->billing_address ? json_decode($this->billing_address, true) : null,
            'shipping_address'  => $this->shipping_address ? json_decode($this->shipping_address, true) : null,
            'created_at'        => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            //'updated_at'        => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
