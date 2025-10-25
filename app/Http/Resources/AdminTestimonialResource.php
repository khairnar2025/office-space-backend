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
            'client_user_id' => $this->client_user_id,
            'message'        => $this->message,
            'media_type'     => $this->media_type,
            'media_url' => $this->media_url
                ? asset('storage/' . $this->media_url)
                : null,
            'thumbnail_url' => $this->thumbnail_url
                ? asset('storage/' . $this->thumbnail_url)
                : null,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'client'         => [
                'id'    => $this->client->id,
                'name'  => $this->client->name,
                'email' => $this->client->email,
                'status' => $this->client->status,
            ],
        ];
    }
}
