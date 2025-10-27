<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = ['title', 'message', 'image', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(BlogSection::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
