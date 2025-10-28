<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPincode extends Model
{
    protected $fillable = ['pincode', 'is_serviceable', 'delivery_days_min', 'delivery_days_max'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_pincode');
    }
    /**
     * Scope to get only serviceable pincodes
     */
    public function scopeServiceable($query)
    {
        return $query->where('is_serviceable', true);
    }
}
