<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'positions' => ['required', 'array', 'min:1', 'max:1000'],
            'positions.*.id' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'positions.*.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'positions.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
