<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    protected function prepareForValidation(): void
    {
        $choice = (string) $this->input('contact_choice', 'new');

        $this->merge([
            'contact_choice' => in_array($choice, ['saved', 'new'], true) ? $choice : 'new',
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'order_note' => trim((string) $this->input('order_note')),
            'save_to_account' => $this->boolean('save_to_account'),
        ]);
    }

    public function rules(): array
    {
        $usingSaved = $this->input('contact_choice') === 'saved';

        return [
            'contact_choice' => ['required', Rule::in(['saved', 'new'])],
            'email' => [$usingSaved ? 'nullable' : 'required', 'email:rfc,dns', 'max:255'],
            'phone' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'first_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:2', 'max:120'],
            'last_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:2', 'max:120'],
            'order_note' => ['nullable', 'string', 'max:1200'],
            'save_to_account' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number using digits, spaces, +, -, parentheses, or dots.',
        ];
    }
}
