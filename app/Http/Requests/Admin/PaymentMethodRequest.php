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
            'description' => trim((string) $this->input('description')) ?: null,
            'instructions' => trim((string) $this->input('instructions')) ?: null,
            'minimum_total' => $this->filled('minimum_total') ? $this->input('minimum_total') : null,
            'maximum_total' => $this->filled('maximum_total') ? $this->input('maximum_total') : null,
            'is_online' => $this->boolean('is_online'),
            'requires_provider_redirect' => $this->boolean('requires_provider_redirect'),
            'requires_manual_review' => $this->boolean('requires_manual_review'),
            'allows_saved_methods' => $this->boolean('allows_saved_methods'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    public function rules(): array
    {
        $methodId = $this->route('paymentMethod')?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('payment_methods', 'code')->ignore($methodId)],
            'provider' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'payment_type' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'badge' => ['nullable', 'string', 'max:80'],
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
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may contain lowercase letters, numbers, and hyphens only.',
            'provider.regex' => 'The provider may contain lowercase letters, numbers, and hyphens only.',
            'payment_type.regex' => 'The payment type may contain lowercase letters, numbers, and underscores only.',
        ];
    }
}
