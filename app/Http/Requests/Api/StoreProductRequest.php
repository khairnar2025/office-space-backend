<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'sometimes|boolean',

            // delivery pincodes (pivot)
            'delivery_pincodes' => 'nullable|array',
            'delivery_pincodes.*' => 'integer|exists:delivery_pincodes,id',

            // Product images (multipart)
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',

            // Variants (array)
            'variants' => 'required|array|min:1',
            'variants.*.color_id' => 'nullable|integer|exists:colors,id',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
            // variant files validated in controller because of variant indexed file fields
        ];
    }

    public function messages()
    {
        return [
            'variants.required' => 'Please provide at least one variant.',
        ];
    }
}
