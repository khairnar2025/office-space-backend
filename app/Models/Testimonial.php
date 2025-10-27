<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'client_name',
        'role',
        'profile_image',
        'message',
        'media_url',
        'thumbnail_url',
        'status'
    ];
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
