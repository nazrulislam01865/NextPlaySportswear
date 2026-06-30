<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
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
        $country = (string) $this->input('country', 'United States');
        $stateRequired = ! $usingSaved && in_array($country, ['United States', 'Canada'], true);

        return [
            'address_choice' => ['required', Rule::in(['saved', 'new'])],
            'saved_address_id' => [
                $usingSaved ? 'required' : 'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereIn('type', ['shipping', 'both'])),
            ],
            'first_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:2', 'max:120'],
            'last_name' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:2', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'address_line_1' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:4', 'max:190'],
            'address_line_2' => ['nullable', 'string', 'max:190'],
            'city' => [$usingSaved ? 'nullable' : 'required', 'string', 'min:2', 'max:120'],
            'state' => [$stateRequired ? 'required' : 'nullable', 'string', 'max:120'],
            'country' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120'],
            'postal_code' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'phone' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'delivery_instruction' => ['nullable', 'string', 'max:800'],
            'save_to_account' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a valid ZIP or postal code.',
            'phone.regex' => 'Enter a valid delivery phone number using digits, spaces, +, -, parentheses, or dots.',
        ];
    }
}
