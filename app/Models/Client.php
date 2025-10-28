<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['title', 'logo', 'website', 'status'];
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
