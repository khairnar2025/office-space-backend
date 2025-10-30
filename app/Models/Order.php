<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'total_amount',
        'currency',
        'name',
        'email',
        'phone',
        'address',
        'pincode',
        'city',
        'state',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
