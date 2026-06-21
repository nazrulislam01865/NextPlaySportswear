<?php

namespace App\Http\Requests\Storefront\Account;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
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
        $currentYear = (int) now()->format('Y');

        return [
            'card_number' => ['required', 'string', 'regex:/^[0-9\s-]{12,23}$/'],
            'expiry_month' => ['required', 'integer', 'between:1,12'],
            'expiry_year' => ['required', 'integer', 'between:' . $currentYear . ',' . ($currentYear + 25)],
            'cvv' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'billing_name' => ['nullable', 'string', 'max:160'],
            'is_default' => ['nullable', 'boolean'],
            'card_consent' => ['accepted'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $month = (int) $this->input('expiry_month');
            $year = (int) $this->input('expiry_year');

            if ($year === (int) now()->format('Y') && $month < (int) now()->format('n')) {
                $validator->errors()->add('expiry_month', 'The card expiration date must be in the future.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'card_number.regex' => 'Enter a valid card number using digits only.',
            'cvv.regex' => 'Enter a valid CVV. This value is validated but never stored.',
            'card_consent.accepted' => 'Please confirm that card details must be tokenized and raw card/CVV values are not stored.',
        ];
    }
}
