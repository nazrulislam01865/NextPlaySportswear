<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'category_csv' => ['required', 'file', 'extensions:csv,xlsx,xls', 'mimes:csv,txt,xlsx,xls', 'max:2048'],
        ];
    }
}
