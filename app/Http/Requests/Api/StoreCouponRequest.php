<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize()
    {
        // Adjust authorization logic as needed
        return true;
    }

    public function rules()
    {
        return [
            'coupon_code'         => 'required|string|unique:coupons,coupon_code|max:8',
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'discount_type'       => 'required|in:fixed,percentage',
            'discount_percentage' => 'required|numeric|min:0',
            'description'         => 'nullable|string',
            'status'              => 'boolean',
            'specialise'          => 'boolean',
        ];
    }
}
