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
            'price' => 'sometimes|required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'product_images' => 'nullable|array',
            'product_images.*' => 'image|mimes:jpeg,png,jpg|max:4096',
            'colors' => 'nullable|array',
            'colors.*' => 'string|max:7',
            'in_stock' => 'boolean',
            'status' => 'boolean',
            'remove_thumbnail' => 'nullable|boolean',
            'remove_gallery' => 'nullable|boolean',
        ];
    }
}
