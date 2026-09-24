<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
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
            'provider' => Str::slug((string) ($this->input('provider') ?: 'manual')),
            'payment_type' => Str::slug((string) ($this->input('payment_type') ?: 'manual'), '_'),
            'badge' => trim((string) $this->input('badge')) ?: null,
            'footer_icon_alt' => trim((string) $this->input('footer_icon_alt')) ?: null,
            'description' => trim((string) $this->input('description')) ?: null,
            'instructions' => trim((string) $this->input('instructions')) ?: null,
            'minimum_total' => $this->filled('minimum_total') ? $this->input('minimum_total') : null,
            'maximum_total' => $this->filled('maximum_total') ? $this->input('maximum_total') : null,
            'is_online' => $this->boolean('is_online'),
            'requires_provider_redirect' => $this->boolean('requires_provider_redirect'),
            'requires_manual_review' => $this->boolean('requires_manual_review'),
            'allows_saved_methods' => Str::slug((string) ($this->input('provider') ?: 'manual')) === 'stripe' ? false : $this->boolean('allows_saved_methods'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'show_in_footer' => $this->boolean('show_in_footer'),
            'remove_footer_icon' => $this->boolean('remove_footer_icon'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    public function rules(): array
    {
        $methodId = $this->route('paymentMethod')?->id;
        $providers = array_keys((array) config('payments.gateways', []));
        $currentProvider = trim((string) ($this->route('paymentMethod')?->provider ?? ''));

        // Keep legacy/future provider records editable without registering a
        // non-functional gateway. New records are still limited to centrally
        // registered providers.
        if ($currentProvider !== '' && ! in_array($currentProvider, $providers, true)) {
            $providers[] = $currentProvider;
        }

        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('payment_methods', 'code')->ignore($methodId)],
            'provider' => ['required', 'string', 'max:80', Rule::in($providers)],
            'payment_type' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'badge' => ['nullable', 'string', 'max:80'],
            'footer_icon_alt' => ['nullable', 'string', 'max:180'],
            'footer_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:1024', 'dimensions:max_width=1600,max_height=800'],
            'remove_footer_icon' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:4000'],
            'minimum_total' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'maximum_total' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'gte:minimum_total'],
            'is_online' => ['boolean'],
            'requires_provider_redirect' => ['boolean'],
            'requires_manual_review' => ['boolean'],
            'allows_saved_methods' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'show_in_footer' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may contain lowercase letters, numbers, and hyphens only.',
            'provider.in' => 'Choose an available payment provider.',
            'payment_type.regex' => 'The payment type may contain lowercase letters, numbers, and underscores only.',
        ];
    }
}
