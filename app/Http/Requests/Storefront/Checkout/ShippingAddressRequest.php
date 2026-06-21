<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
        $usingSaved = $this->input('address_choice') === 'saved';

        return [
            'address_choice' => ['required', Rule::in(['saved', 'new'])],
            'saved_address_id' => [$usingSaved ? 'required' : 'nullable', 'integer'],
            'first_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'last_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'address_line_1' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:190'],
            'address_line_2' => ['nullable', 'string', 'max:190'],
            'city' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'postal_code' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'delivery_instruction' => ['nullable', 'string', 'max:800'],
            'save_to_account' => ['nullable', 'boolean'],
        ];
    }
}
