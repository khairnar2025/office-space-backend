<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
class UpdateAddressesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return Auth::guard('sanctum')->check();
    }

    public function rules()
    {
        return [
            'billing_address'  => 'sometimes|array',
            'shipping_address' => 'sometimes|array',
        ];
    }
}
