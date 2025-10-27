<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TestimonialResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 'id'=>$this->id,
            'client_name' => $this->client_name ?? 'Anonymous',
            'role' => $this->role,
            'profile_image' => $this->profile_image
                ? asset('storage/' . $this->profile_image)
                : null,
            'message' => $this->message,
            'media_url' => $this->media_url
                ? asset('storage/' . $this->media_url)
                : null,
            'thumbnail_url' => $this->thumbnail_url
                ? asset('storage/' . $this->thumbnail_url)
                : null,
            'created_at'     => $this->created_at,
        ];
    }
}
