<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|min:10',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'status' => 'boolean',
            'sections' => 'nullable|array',
            'sections.*.heading' => 'nullable|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.attachment' => 'nullable|file|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide the blog title.',
            'message.required' => 'The message is required.',
        ];
    }
}
