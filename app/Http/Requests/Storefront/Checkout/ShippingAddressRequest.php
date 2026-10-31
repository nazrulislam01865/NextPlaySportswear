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
        // The prototype has one recipient-name field; persist the existing
        // first/last data contract without creating new database fields.
        $name = trim((string) $this->input('recipient_name', ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name, 2);
            $this->merge(['first_name' => $parts[0] ?? '', 'last_name' => $parts[1] ?? '']);
        }
        $secondary = (array) $this->input('shipments', []);
        foreach ($secondary as $index => $shipment) {
            if (! is_array($shipment)) continue;
            if ($this->boolean('shipments.'.$index.'.same_as_first')) {
                foreach (['first_name', 'last_name', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code', 'phone', 'email'] as $key) {
                    $secondary[$index][$key] = $this->input($key, '');
                }
            } elseif (trim((string) ($shipment['recipient_name'] ?? '')) !== '') {
                $parts = preg_split('/\s+/', trim($shipment['recipient_name']), 2);
                $secondary[$index]['first_name'] = $parts[0] ?? '';
                $secondary[$index]['last_name'] = $parts[1] ?? '';
            }
            $secondary[$index]['same_as_first'] = $this->boolean('shipments.'.$index.'.same_as_first');
        }
        $this->merge(['shipments' => $secondary]);

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
            'needed_by' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'shipments' => ['sometimes', 'array', 'max:100'],
            'shipments.*.recipient_name' => ['nullable', 'string', 'max:240'],
            'shipments.*.same_as_first' => ['nullable', 'boolean'],
            'shipments.*.first_name' => ['required', 'string', 'min:2', 'max:120'],
            'shipments.*.last_name' => ['required', 'string', 'min:2', 'max:120'],
            'shipments.*.company_name' => ['nullable', 'string', 'max:160'],
            'shipments.*.address_line_1' => ['required', 'string', 'min:4', 'max:190'],
            'shipments.*.address_line_2' => ['nullable', 'string', 'max:190'],
            'shipments.*.city' => ['required', 'string', 'min:2', 'max:120'],
            'shipments.*.state' => ['nullable', 'string', 'max:120'],
            'shipments.*.country' => ['required', 'string', 'max:120', Rule::exists('country_calling_codes', 'country_name')->where('is_active', true)],
            'shipments.*.postal_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\s]{3,30}$/'],
            'shipments.*.phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-\s().]{7,40}$/'],
            'shipments.*.email' => ['required', 'email:rfc', 'max:255'],
            'shipments.*.delivery_instruction' => ['nullable', 'string', 'max:800'],
            'shipments.*.needed_by' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
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
            'state' => ['nullable', 'string', 'max:120'],
            'country' => [$usingSaved ? 'nullable' : 'required', 'string', 'max:120', Rule::exists('country_calling_codes', 'country_name')->where('is_active', true)],
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
