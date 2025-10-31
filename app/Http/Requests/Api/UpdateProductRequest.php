<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|boolean',

            'delivery_pincodes' => 'nullable|array',
            'delivery_pincodes.*' => 'integer|exists:delivery_pincodes,id',

            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',

            // Variants array optional on update
            'variants' => 'sometimes|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.color_id' => 'nullable|integer|exists:colors,id',
            'variants.*.price' => 'sometimes|required|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'sometimes|required|integer|min:0',

            // Deletion lists
            'delete_variants' => 'nullable|array',
            'delete_variants.*' => 'integer|exists:product_variants,id',

            'delete_gallery_images' => 'nullable|array', // product gallery filenames to delete
            'delete_gallery_images.*' => 'string',

            // For variant-specific gallery deletes: expect structure like:
            // delete_variant_gallery_images[<variant_id>][] = filename1, filename2
            // validated loosely in controller
        ];
    }
}
