<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

        $this->merge([
            'name' => $name,
            'code' => Str::slug($code),
            'description' => trim((string) $this->input('description')),
            'base_price' => $this->input('base_price'),
            'per_item_price' => $this->input('per_item_price'),
            'free_shipping_minimum' => $this->input('free_shipping_minimum') !== '' ? $this->input('free_shipping_minimum') : null,
            'minimum_quantity' => $this->input('minimum_quantity') !== '' ? $this->input('minimum_quantity') : null,
            'maximum_quantity' => $this->input('maximum_quantity') !== '' ? $this->input('maximum_quantity') : null,
            'minimum_subtotal' => $this->input('minimum_subtotal') !== '' ? $this->input('minimum_subtotal') : null,
            'maximum_subtotal' => $this->input('maximum_subtotal') !== '' ? $this->input('maximum_subtotal') : null,
            'country' => trim((string) $this->input('country')) ?: null,
            'state' => trim((string) $this->input('state')) ?: null,
            'minimum_days' => $this->input('minimum_days'),
            'maximum_days' => $this->input('maximum_days'),
            'starts_after_artwork_approval' => $this->boolean('starts_after_artwork_approval'),
            'is_quote_based' => $this->boolean('is_quote_based'),
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
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
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
            'maximum_days.gte' => 'Maximum days must be greater than or equal to minimum days.',
        ];
    }
}
