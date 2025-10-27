<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogSection extends Model
{
    protected $fillable = ['blog_id', 'heading', 'content', 'attachment'];

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment ? asset('storage/' . $this->attachment) : null;
    }

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
