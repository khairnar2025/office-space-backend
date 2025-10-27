<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminTestimonialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'client_name' => $this->client_name,
            'role' => $this->role,
            'profile_image' => $this->profile_image
                ? asset('storage/' . $this->profile_image)
                : null,
            'message'        => $this->message,
            'media_url' => $this->media_url
                ? asset('storage/' . $this->media_url)
                : null,
            'thumbnail_url' => $this->thumbnail_url
                ? asset('storage/' . $this->thumbnail_url)
                : null,
            'status'        => $this->status ? 1 : 0,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
