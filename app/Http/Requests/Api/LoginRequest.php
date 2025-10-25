<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Anyone can attempt login
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters long.',
        ];
    }
}
