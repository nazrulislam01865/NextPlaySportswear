<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReferralCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'address_line_1' => trim((string) $this->input('address_line_1')),
            'address_line_2' => trim((string) $this->input('address_line_2')),
            'city' => trim((string) $this->input('city')),
            'state' => trim((string) $this->input('state')),
            'postal_code' => trim((string) $this->input('postal_code')),
            'country' => trim((string) $this->input('country', 'United Kingdom')),
            'phone' => trim((string) $this->input('phone')),
            'billing_same_as_shipping' => $this->boolean('billing_same_as_shipping'),
        ]);
    }

    public function rules(): array
    {
        $country = (string) $this->input('country');
        $stateRequired = in_array($country, ['United States', 'Canada'], true);
        $sameBilling = $this->boolean('billing_same_as_shipping');

        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'first_name' => ['required', 'string', 'min:2', 'max:120'],
            'last_name' => ['required', 'string', 'min:2', 'max:120'],
            'address_line_1' => ['required', 'string', 'min:4', 'max:190'],
            'address_line_2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'state' => [$stateRequired ? 'required' : 'nullable', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'country' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'delivery_preference' => ['required', Rule::in(['standard', 'express'])],
            'payment_method' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'billing_first_name' => [$sameBilling ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'billing_last_name' => [$sameBilling ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'billing_address_line_1' => [$sameBilling ? 'exclude' : 'required', 'string', 'min:4', 'max:190'],
            'billing_address_line_2' => [$sameBilling ? 'exclude' : 'nullable', 'string', 'max:190'],
            'billing_city' => [$sameBilling ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'billing_state' => [$sameBilling ? 'exclude' : 'nullable', 'string', 'max:120'],
            'billing_postal_code' => [$sameBilling ? 'exclude' : 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'billing_country' => [$sameBilling ? 'exclude' : 'required', 'string', 'max:120'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a valid postcode or ZIP code.',
            'billing_postal_code.regex' => 'Enter a valid billing postcode or ZIP code.',
            'terms.accepted' => 'Please accept the Terms & Conditions before continuing to review.',
        ];
    }
}
