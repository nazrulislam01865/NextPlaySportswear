<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('same_as_shipping')) {
            $this->merge(['same_as_shipping' => true]);
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
        $country = (string) $this->input('country', 'United States');
        $stateRequired = ! $same && ! $usingSaved && in_array($country, ['United States', 'Canada'], true);

        return [
            'same_as_shipping' => ['nullable', 'boolean'],
            'address_choice' => [$same ? 'exclude' : 'required', Rule::in(['saved', 'new'])],
            'saved_address_id' => [
                $same || ! $usingSaved ? 'exclude' : 'required',
                'integer',
                Rule::exists('customer_addresses', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereIn('type', ['billing', 'both'])),
            ],
            'first_name' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'last_name' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'company_name' => [$same || $usingSaved ? 'exclude' : 'nullable', 'string', 'max:160'],
            'address_line_1' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'min:4', 'max:190'],
            'address_line_2' => [$same || $usingSaved ? 'exclude' : 'nullable', 'string', 'max:190'],
            'city' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'min:2', 'max:120'],
            'state' => [$same || $usingSaved ? 'exclude' : ($stateRequired ? 'required' : 'nullable'), 'string', 'max:120'],
            'country' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'max:120'],
            'postal_code' => [$same || $usingSaved ? 'exclude' : 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'phone' => [$same || $usingSaved ? 'exclude' : 'nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'email' => [$same || $usingSaved ? 'exclude' : 'nullable', 'email:rfc', 'max:255'],
            'save_to_account' => [$same ? 'exclude' : 'nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a valid ZIP or postal code.',
            'phone.regex' => 'Enter a valid phone number using digits, spaces, +, -, parentheses, or dots.',
        ];
    }
}
