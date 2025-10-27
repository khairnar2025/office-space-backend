<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminBlogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'image' => $this->image_url,
            'status' => $this->status ? 1 : 0,
            'sections' => BlogSectionResource::collection($this->whenLoaded('sections')),
            'created_at' => $this->created_at,
        ];
    }
}
