<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryPincodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('delivery_pincode')->id ?? null;

        return [
            'pincode' => 'sometimes|required|string|max:10|unique:delivery_pincodes,pincode,' . $id,
            'is_serviceable' => 'sometimes|boolean',
            'delivery_days_min' => 'sometimes|required|integer|min:0',
            'delivery_days_max' => 'sometimes|required|integer|min:0|gte:delivery_days_min',
        ];
    }
}
