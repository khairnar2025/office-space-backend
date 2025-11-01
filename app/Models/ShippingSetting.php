<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'minimum_free_shipping_amount',
        'shipping_cost'
    ];
}
