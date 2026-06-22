<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_slug' => ['required', 'string', 'max:180'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'design_option' => ['nullable', 'string', 'max:80'],
            'delivery_preference' => ['nullable', 'string', 'max:80'],
            'size_summary' => ['nullable', 'string', 'max:600'],
            'artwork_status' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'configuration_json' => ['nullable', 'json', 'max:50000'],
            'artwork_file' => ['nullable', 'file', 'mimes:pdf,svg,png,jpg,jpeg,webp', 'max:15360'],
        ];
    }
}
