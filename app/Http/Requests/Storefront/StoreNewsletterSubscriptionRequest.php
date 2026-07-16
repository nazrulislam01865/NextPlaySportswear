<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterSubscriptionRequest extends FormRequest
{
    protected $errorBag = 'newsletter';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'source' => trim((string) $this->input('source', 'homepage-newsletter')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['bail', 'required', 'email:rfc', 'max:190'],
            'source' => ['nullable', 'string', 'max:80'],
            // Invisible honeypot field. Real visitors leave it empty.
            'company' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address to join the list.',
            'email.email' => 'Please enter a valid email address.',
            'company.max' => 'Your subscription could not be submitted. Please try again.',
        ];
    }
}
