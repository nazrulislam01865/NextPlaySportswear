<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('same_as_shipping')) {
            return;
        }

        $choice = (string) $this->input('address_choice', 'new');

        if (str_starts_with($choice, 'saved:')) {
            $this->merge([
                'address_choice' => 'saved',
                'saved_address_id' => (int) str_replace('saved:', '', $choice),
            ]);
        } elseif (! $this->filled('address_choice')) {
            $this->merge(['address_choice' => 'new']);
        }
    }

    public function rules(): array
    {
        $same = $this->boolean('same_as_shipping');
        $usingSaved = $this->input('address_choice') === 'saved';

        return [
            'same_as_shipping' => ['nullable', 'boolean'],
            'address_choice' => [$same ? 'nullable' : 'required', Rule::in(['saved', 'new'])],
            'saved_address_id' => [$same || ! $usingSaved ? 'nullable' : 'required', 'integer'],
            'first_name' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'last_name' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'address_line_1' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:190'],
            'address_line_2' => ['nullable', 'string', 'max:190'],
            'city' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'postal_code' => [$same || $usingSaved ? 'nullable' : 'required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'save_to_account' => ['nullable', 'boolean'],
        ];
    }
}
