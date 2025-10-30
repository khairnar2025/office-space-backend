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
            'email'         => 'sometimes|required|email|unique:users,email,' . $userId,
            'password'      => 'sometimes|nullable|string|min:6|confirmed',
            'profile_image' => 'sometimes|nullable|image|max:2048',
        ];
    }
}
