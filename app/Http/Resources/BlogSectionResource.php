<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'heading' => $this->heading,
            'content' => $this->content,
            'attachment' => $this->attachment_url,
        ];
    }
}
