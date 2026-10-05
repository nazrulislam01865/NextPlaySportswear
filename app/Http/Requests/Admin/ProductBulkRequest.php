<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1', 'max:200'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'archive', 'feature', 'unfeature', 'production_enable', 'production_disable', 'delete'])],
            'production_method_ids' => ['nullable', 'array', 'max:30', 'required_if:action,production_enable'],
            'production_method_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('production_methods', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }
}
