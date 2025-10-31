<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryPincodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pincode' => 'required|string|max:10|unique:delivery_pincodes,pincode',
            'shipping_cost' => 'sometimes|numeric|min:0',
            'is_serviceable' => 'sometimes|boolean',
            'delivery_days_min' => 'required|integer|min:0',
            'delivery_days_max' => 'required|integer|min:0|gte:delivery_days_min',
        ];
    }
}
