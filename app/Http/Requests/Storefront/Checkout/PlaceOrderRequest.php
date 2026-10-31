<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    public function rules(): array
    {
        return [
            'confirm_details' => ['accepted'],
            'payment_method' => ['required', Rule::in(['stripe'])],
            'billing_same_as_shipping' => ['required', 'boolean'],
            'billing_first_name' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', 'min:2', 'max:120'],
            'billing_last_name' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', 'min:2', 'max:120'],
            'billing_email' => ['required_if:billing_same_as_shipping,0', 'nullable', 'email:rfc', 'max:255'],
            'billing_country' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', Rule::exists('country_calling_codes', 'country_name')->where('is_active', true)],
            'billing_address_line_1' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', 'min:4', 'max:190'],
            'billing_address_line_2' => ['nullable', 'string', 'max:190'],
            'billing_city' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', 'min:2', 'max:120'],
            'billing_postal_code' => ['required_if:billing_same_as_shipping,0', 'nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'terms' => ['accepted'],
            'idempotency_key' => ['required', 'string', 'size:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_details.accepted' => 'Please confirm that the order details are correct before placing the order.',
            'payment_method.in' => 'Choose the available secure payment method.',
            'terms.accepted' => 'Please accept the Terms, Privacy Policy, and Custom Product Production Policy before placing the order.',
        ];
    }
}
