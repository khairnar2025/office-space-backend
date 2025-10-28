<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => 'sometimes|required|string|max:255|unique:categories,name,' . $this->route('category')->id,
            'status' => 'boolean',
        ];
    }
}
