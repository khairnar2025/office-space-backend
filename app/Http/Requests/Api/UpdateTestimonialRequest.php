<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_user_id' => 'sometimes|required|exists:users,id',
            'message'        => 'sometimes|required|string|min:10',
            'media_file'     => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov|max:10240',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'is_active'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'client_user_id.required' => 'Please provide the client user.',
            'client_user_id.exists'   => 'The selected client does not exist.',
            'message.required'        => 'The testimonial message is required.',
            'message.min'             => 'The message must be at least 10 characters.',
            'media_file.mimes'        => 'The file must be a valid image or video (jpg, png, mp4, mov).',
            'media_file.max'          => 'The file size cannot exceed 10MB.',
            'thumbnail.mimes' => 'The thumbnail must be an image file (JPG, JPEG, PNG).',
            'thumbnail.max' => 'The thumbnail image size cannot exceed 2MB.',
        ];
    }
}
