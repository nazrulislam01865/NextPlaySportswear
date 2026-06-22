<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1', 'max:200'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'archive', 'feature', 'unfeature', 'show_in_menu', 'hide_from_menu'])],
        ];
    }
}
