<?php

namespace App\Http\Requests\Storefront\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
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
        return [
            'type' => ['required', Rule::in(['shipping', 'billing', 'both'])],
            'first_name' => ['required', 'string', 'min:2', 'max:120'],
            'last_name' => ['required', 'string', 'min:2', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'address_line_1' => ['required', 'string', 'min:4', 'max:190'],
            'address_line_2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'delivery_instruction' => ['nullable', 'string', 'max:800'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a valid zip or postal code.',
            'phone.regex' => 'Enter a valid phone number using digits, spaces, +, -, parentheses, or dots.',
        ];
    }
}
