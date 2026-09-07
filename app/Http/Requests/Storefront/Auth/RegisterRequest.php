<?php

namespace App\Http\Requests\Storefront\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            // Verification proves ownership/deliverability, so registration
            // avoids network-dependent DNS validation on the request path.
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'website' => ['nullable', 'max:0'],
            'terms' => ['accepted'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the privacy, return, and custom order terms before creating an account.',
            'website.max' => 'The registration request could not be accepted.',
            'email.unique' => 'A customer account already exists with this email address. Please sign in or reset your password.',
        ];
    }
}
