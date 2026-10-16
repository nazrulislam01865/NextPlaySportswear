<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,avif',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:5120',
            ],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon' => [
                'nullable',
                'file',
                'mimes:ico,png,jpg,jpeg,webp,avif',
                'mimetypes:image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/webp,image/avif',
                'max:2048',
            ],
            'remove_favicon' => ['nullable', 'boolean'],
        ];
    }
}
