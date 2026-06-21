<?php

namespace App\Http\Requests\Storefront\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{7,30}$/'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'preferred_sport' => ['nullable', Rule::in(['basketball', 'baseball', 'football', 'soccer', 'volleyball', 'hockey', 'cheerleading', 'training', 'other'])],
            'marketing_consent' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number using digits, spaces, +, -, parentheses, or dots.',
        ];
    }
}
