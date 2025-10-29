<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow public and authenticated users
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'color_id'   => ['nullable', 'exists:colors,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
