<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\ShippingMethod;

class ShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $code = trim((string) ($this->input('code') ?: Str::slug($name)));

        $chargeApplication = (string) ($this->input('charge_application') ?: 'per_order');
        if (! array_key_exists($chargeApplication, ShippingMethod::chargeApplicationOptions())) {
            $chargeApplication = 'per_order';
        }

        $chargeAmount = $this->input('charge_amount');
        $chargeAmount = $chargeAmount === '' || $chargeAmount === null ? 0 : max(0, (float) $chargeAmount);

        if ($chargeApplication === 'included') {
            $chargeAmount = 0;
        }

        $this->merge([
            'name' => $name,
            'code' => Str::slug($code),
            'description' => trim((string) $this->input('description')),
            'charge_amount' => $chargeAmount,
            'charge_application' => $chargeApplication,
            'base_price' => $chargeApplication === 'per_order' ? $chargeAmount : 0,
            'per_item_price' => in_array($chargeApplication, ['per_product', 'per_item'], true) ? $chargeAmount : 0,
            'free_shipping_minimum' => null,
            'minimum_quantity' => null,
            'maximum_quantity' => null,
            'minimum_subtotal' => null,
            'maximum_subtotal' => null,
            'country' => null,
            'state' => null,
            'minimum_days' => $this->input('minimum_days'),
            'maximum_days' => $this->input('maximum_days'),
            'starts_after_artwork_approval' => $this->boolean('starts_after_artwork_approval'),
            'is_quote_based' => false,
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        $shippingMethod = $this->route('shipping_method') ?? $this->route('shippingMethod');
        $id = is_object($shippingMethod) ? $shippingMethod->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('shipping_methods', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'charge_amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'charge_application' => ['required', Rule::in(array_keys(ShippingMethod::chargeApplicationOptions()))],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'per_item_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'free_shipping_minimum' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'minimum_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['nullable', 'integer', 'gte:minimum_quantity', 'max:1000000'],
            'minimum_subtotal' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'maximum_subtotal' => ['nullable', 'numeric', 'gte:minimum_subtotal', 'max:999999999.99'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'minimum_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'maximum_days' => ['required', 'integer', 'gte:minimum_days', 'max:3650'],
            'starts_after_artwork_approval' => ['nullable', 'boolean'],
            'is_quote_based' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Use lowercase letters, numbers, and hyphens only, for example standard-shipping.',
            'charge_application.in' => 'Choose how the extra charge should be applied.',
            'maximum_days.gte' => 'Maximum days must be greater than or equal to minimum days.',
        ];
    }
}
