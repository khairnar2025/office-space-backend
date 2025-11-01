<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'coupon_code',
        'start_date',
        'end_date',
        'discount_type',
        'discount_percentage',
        'description',
        'status',
        'specialise',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
        'specialise' => 'boolean',
        'discount_percentage' => 'decimal:2',
    ];
}
