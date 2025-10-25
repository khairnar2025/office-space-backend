<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'client_user_id',
        'message',
        'media_type',
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
    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }
}
