<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'topic' => trim((string) $this->input('topic')),
            'order_number' => $this->filled('order_number')
                ? strtoupper(trim((string) $this->input('order_number')))
                : null,
            'message' => trim((string) $this->input('message')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:120'],
            'email' => ['bail', 'required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\.\s]+$/'],
            'topic' => [
                'required',
                Rule::in([
                    'product-question',
                    'order-support',
                    'customization',
                    'bulk-quote',
                    'shipping',
                    'return-refund',
                    'website-help',
                    'other',
                ]),
            ],
            'order_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z0-9\-]+$/'],
            'message' => ['bail', 'required', 'string', 'min:10', 'max:5000'],

            // Invisible honeypot field. Real visitors leave it empty.
            'company' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'company.max' => 'Your message could not be submitted. Please try again.',
            'phone.regex' => 'Please enter a valid phone number.',
            'order_number.regex' => 'The order number may only contain letters, numbers, and hyphens.',
        ];
    }
}
