<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Ensure the user is authenticated
        return Auth::guard('sanctum')->check();
    }

    public function rules()
    {
        $userId = Auth::guard('sanctum')->id();

        return [
            'name'          => 'sometimes|required|string|max:255',
            'company_name'  => 'sometimes|nullable|string|max:255',
            'gst_no'        => 'sometimes|nullable|string|max:50',
            'email'         => 'sometimes|required|email|unique:users,email,' . $userId,
            'phone_no'      => 'sometimes|nullable|string|max:20',
            'password'      => 'sometimes|nullable|string|min:6|confirmed',
            'profile_image' => 'sometimes|nullable|image|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required when provided.',
            'email.required' => 'Email is required when provided.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'This email is already taken.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'profile_image.image' => 'Profile image must be a valid image file.',
            'profile_image.max' => 'Profile image size must not exceed 2MB.',
        ];
    }
}
