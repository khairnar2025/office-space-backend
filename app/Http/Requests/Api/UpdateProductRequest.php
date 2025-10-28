<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'colors' => 'nullable|array',
            'colors.*' => 'integer|exists:colors,id',
            'delivery_pincodes' => 'nullable|array',
            'delivery_pincodes.*' => 'exists:delivery_pincodes,id',

            'in_stock' => 'boolean',
            'price' => 'sometimes|required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'delete_gallery' => 'nullable|array',
            'delete_gallery.*' => 'integer',
            'delete_colors' => 'nullable|array',
            'delete_colors.*' => 'integer|exists:colors,id',
        ];
    }
}
