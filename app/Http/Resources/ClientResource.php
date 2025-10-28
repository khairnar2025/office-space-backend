<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'logo'   => $this->logo
                ? asset(Storage::url($this->logo)) 
                : null,
            'status' => (bool) $this->status ? 1 : 0,
        ];
    }
}
