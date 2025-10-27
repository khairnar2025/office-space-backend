<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string|min:10',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'sections' => 'nullable|array',
            'sections.*.id' => 'nullable|integer|exists:blog_sections,id',
            'sections.*.heading' => 'nullable|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.attachment' => 'nullable|file|max:10240',
            'status' => 'boolean',
        ];
    }
}
