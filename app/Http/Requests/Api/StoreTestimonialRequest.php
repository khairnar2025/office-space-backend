<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => 'required|string',
            'role' => 'required|string',
            'message' => 'required|string|min:10',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov|max:10240', // 10MB max
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.required' => 'Please provide the client name.',
            'role.required' => 'Please provide the role.',
            'message.required' => 'The testimonial message is required.',
            'message.min' => 'Your message must be at least 10 characters long.',
            'media_file.mimes' => 'The media file must be an image (jpg, jpeg, png) or video (mp4, mov).',
            'media_file.max' => 'Media file size cannot exceed 10MB.',
            'thumbnail.mimes' => 'The thumbnail must be an image file (JPG, JPEG, PNG).',
            'thumbnail.max' => 'The thumbnail image size cannot exceed 2MB.',
        ];
    }
}
