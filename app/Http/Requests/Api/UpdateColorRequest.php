<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => 'sometimes|required|string|max:255|unique:colors,name,' . $this->route('color')->id,
            'status' => 'boolean',
        ];
    }
}
