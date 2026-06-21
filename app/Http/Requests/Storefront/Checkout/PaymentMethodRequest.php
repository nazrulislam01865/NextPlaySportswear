<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $method = (string) $this->input('payment_method', 'card');

        if (str_starts_with($method, 'saved_card:')) {
            $this->merge([
                'payment_method' => 'saved_card',
                'saved_payment_method_id' => (int) str_replace('saved_card:', '', $method),
            ]);
        } elseif (! $this->filled('payment_method')) {
            $this->merge(['payment_method' => 'card']);
        }
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['card', 'paypal', 'invoice', 'saved_card'])],
            'saved_payment_method_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please choose a payment method.',
        ];
    }
}
