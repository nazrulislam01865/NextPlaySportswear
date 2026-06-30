<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    public function rules(): array
    {
        return [
            'terms' => ['accepted'],
            'idempotency_key' => ['required', 'string', 'size:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the Terms, Privacy Policy, and Custom Product Production Policy before placing the order.',
        ];
    }
}
