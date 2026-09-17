<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

abstract class CustomerLoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
