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
            'profile_image' => $this->client?->profile_image
                ? asset('storage/' . $this->client->profile_image)
                : null,
            'message' => $this->message,
            'media_type' => $this->media_type,
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
