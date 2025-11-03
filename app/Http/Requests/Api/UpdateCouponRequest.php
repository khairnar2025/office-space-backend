<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'coupon_code'         => 'nullable|string|max:8|unique:coupons,coupon_code,' . $this->route('coupon')->id,
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'discount_type'       => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'description'         => 'nullable|string',
            'status'              => 'boolean',
            'specialise'          => 'boolean',
        ];
    }
}
