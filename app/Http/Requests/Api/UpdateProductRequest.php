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
            'delete_pincodes' => 'nullable|array',
            'delete_pincodes.*' => 'integer|exists:delivery_pincodes,id',

            // ✅ Variants Validation
            'variants' => 'sometimes|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',

            // Partial update allowed
            'variants.*.color_id' => 'sometimes|integer|exists:colors,id',
            'variants.*.price' => 'sometimes|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'sometimes|integer|min:0',
            'variants.*.status' => 'sometimes|boolean',
            // ✅ File uploads
            'variants.*.thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'variants.*.gallery' => 'nullable|array',
            'variants.*.gallery.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',

            'delete_variant_thumbnail' => 'nullable|array',
            'delete_variant_thumbnail.*' => 'nullable|in:1',

            // ✅ Variant delete by ID
            'delete_variants' => 'nullable|array',
            'delete_variants.*' => 'integer|exists:product_variants,id',

            // ✅ Index-based Delete Variant Gallery
            'delete_variant_gallery_images' => 'nullable|array',
            'delete_variant_gallery_images.*' => 'array',
            'delete_variant_gallery_images.*.*' => 'integer|min:0',
        ];
    }

    /**
     * Custom, user-friendly validation messages.
     */
    public function messages()
    {
        return [
            // General fields
            'title.required' => 'Please provide a product title.',
            'title.string' => 'The title must be a valid text.',
            'title.max' => 'The title should not exceed 255 characters.',

            'description.string' => 'The description must be valid text.',

            'category_id.required' => 'Please select a category for this product.',
            'category_id.exists' => 'The selected category is invalid.',
            'status.boolean' => 'The status must be true or false.',

            // Delivery pincodes
            'delivery_pincodes.array' => 'Delivery pincodes must be an array.',
            'delivery_pincodes.*.exists' => 'One or more selected delivery pincodes are invalid.',

            // Variants
            'variants.array' => 'Variants data must be sent as an array.',
            'variants.*.id.exists' => 'One of the variants you are trying to update does not exist.',

            'variants.*.color_id.integer' => 'The color ID must be a valid number.',
            'variants.*.color_id.exists' => 'The selected color is invalid.',

            'variants.*.price.numeric' => 'The price must be a valid number.',
            'variants.*.price.min' => 'The price cannot be negative.',

            'variants.*.discount_price.numeric' => 'The discount price must be a valid number.',
            'variants.*.discount_price.min' => 'The discount price cannot be negative.',

            'variants.*.quantity.integer' => 'Quantity must be a valid number.',
            'variants.*.quantity.min' => 'Quantity cannot be negative.',

            // Files
            'variants.*.thumbnail.image' => 'The thumbnail must be a valid image file.',
            'variants.*.thumbnail.mimes' => 'The thumbnail must be a JPEG, PNG, JPG, GIF, or WEBP image.',
            'variants.*.thumbnail.max' => 'The thumbnail image size should not exceed 5MB.',

            'variants.*.gallery.array' => 'The gallery must be an array of images.',
            'variants.*.gallery.*.image' => 'Each gallery file must be a valid image.',
            'variants.*.gallery.*.mimes' => 'Each gallery image must be JPEG, PNG, JPG, GIF, or WEBP.',
            'variants.*.gallery.*.max' => 'Each gallery image must not exceed 5MB.',
            'variants.*.status' => 'sometimes|boolean',
            // Deletion arrays
            'delete_variant_thumbnail.array' => 'Invalid format for delete_variant_thumbnail.',
            'delete_variant_thumbnail.*.in' => 'Invalid delete flag for variant thumbnail.',

            'delete_variants.array' => 'Invalid format for delete_variants.',
            'delete_variants.*.exists' => 'One or more selected variants for deletion do not exist.',

            'delete_variant_gallery_images.array' => 'Invalid format for gallery image deletion.',
            'delete_variant_gallery_images.*.array' => 'Each variant’s gallery deletion list must be an array.',
            'delete_variant_gallery_images.*.*.integer' => 'Invalid image index for gallery deletion.',
        ];
    }
}
