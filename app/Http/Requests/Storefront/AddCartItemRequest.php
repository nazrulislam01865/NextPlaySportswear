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
            // The storefront submits the complete human-readable option summary here.
            // CartService safely condenses the stored/display value to 80 characters, but
            // validation must accept the full summary first or valid configurations with
            // several selected options are rejected before they reach the cart service.
            'design_option' => ['nullable', 'string', 'max:1000'],
            'delivery_preference' => ['nullable', 'string', 'max:80'],
            'size_summary' => ['nullable', 'string', 'max:600'],
            'artwork_status' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'configuration_json' => ['nullable', 'json', 'max:1000000'],
            'artwork_files' => ['nullable', 'array', 'max:12'],
            'artwork_files.*' => ['file', 'mimes:pdf,svg,png,jpg,jpeg,webp', 'max:25600'],
            // Kept for backward compatibility with older cached product forms.
            'artwork_file' => ['nullable', 'file', 'mimes:pdf,svg,png,jpg,jpeg,webp', 'max:25600'],
        ];
    }
}
