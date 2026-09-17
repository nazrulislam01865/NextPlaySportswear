<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    protected function prepareForValidation(): void
    {
        $method = trim((string) $this->input('payment_method'));

        if (str_starts_with($method, 'saved_card:')) {
            $this->merge([
                'payment_method' => 'saved_card',
                'saved_payment_method_id' => (int) str_replace('saved_card:', '', $method),
            ]);
        }
    }

    public function rules(): array
    {
        $usingSaved = $this->input('payment_method') === 'saved_card';

        return [
            'payment_method' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$|^saved_card$/'],
            'saved_payment_method_id' => [
                $usingSaved ? 'required' : 'nullable',
                'integer',
                Rule::exists('customer_payment_methods', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'card_number' => ['prohibited'],
            'cvv' => ['prohibited'],
            'cvc' => ['prohibited'],
            'expiry' => ['prohibited'],
            'payment_token' => ['prohibited'],
            'gateway_token' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please choose a payment method.',
            'payment_method.regex' => 'Please choose a valid payment method.',
        ];
    }
}
