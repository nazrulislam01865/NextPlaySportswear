<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customizationTypes = collect((array) $this->input('customization_types', []))
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'organization' => trim((string) $this->input('organization')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'product_type' => trim((string) $this->input('product_type')),
            'estimated_quantity' => trim((string) $this->input('estimated_quantity')),
            'sizes_needed' => trim((string) $this->input('sizes_needed')),
            'budget_range' => $this->filled('budget_range') ? trim((string) $this->input('budget_range')) : null,
            'artwork_details' => trim((string) $this->input('artwork_details')),
            'customization_types' => $customizationTypes,
            'shipping_address' => trim((string) $this->input('shipping_address')),
            'country' => trim((string) $this->input('country')),
            'state_province' => $this->filled('state_province') ? trim((string) $this->input('state_province')) : null,
            'postal_code' => $this->filled('postal_code') ? trim((string) $this->input('postal_code')) : null,
            'preferred_shipping_method' => $this->filled('preferred_shipping_method') ? trim((string) $this->input('preferred_shipping_method')) : null,
            'additional_notes' => $this->filled('additional_notes') ? trim((string) $this->input('additional_notes')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['bail', 'required', 'string', 'min:2', 'max:120'],
            'organization' => ['bail', 'required', 'string', 'min:2', 'max:160'],
            'email' => ['bail', 'required', 'email:rfc', 'max:190'],
            'phone' => ['bail', 'required', 'string', 'max:40', 'regex:/^[0-9+()\-\.\s]+$/'],
            'product_type' => ['bail', 'required', 'string', 'min:2', 'max:190'],
            'estimated_quantity' => [
                'required',
                Rule::in(['10-49', '50-99', '100-499', '500-999', '1000-plus']),
            ],
            'sizes_needed' => ['bail', 'required', 'string', 'min:1', 'max:190'],
            'budget_range' => ['nullable', Rule::in(['under-500', '500-1500', '1500-5000', '5000-plus', 'not-sure'])],
            'artwork_details' => ['bail', 'required', 'string', 'min:10', 'max:5000'],
            'customization_types' => ['array', 'max:6'],
            'customization_types.*' => [
                Rule::in(['logo', 'names-numbers', 'full-custom-design', 'embroidery', 'sublimation', 'not-sure']),
            ],
            'shipping_address' => ['bail', 'required', 'string', 'min:8', 'max:500'],
            'country' => ['required', Rule::in(['United States', 'Canada', 'United Kingdom', 'Australia', 'Other'])],
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'preferred_shipping_method' => ['nullable', Rule::in(['standard', 'express', 'rush', 'recommend'])],
            'needed_by' => ['bail', 'required', 'date'],
            'event_date' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],

            // Invisible honeypot field. Real visitors leave it empty.
            'company' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'company.max' => 'Your request could not be submitted. Please try again.',
            'phone.regex' => 'Please enter a valid phone or WhatsApp number.',
            'attachment.max' => 'The attachment may not be larger than 10MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'organization' => 'company, team, or school',
            'product_type' => 'items needed / product type',
            'estimated_quantity' => 'estimated quantity',
            'sizes_needed' => 'sizes needed',
            'budget_range' => 'budget range',
            'artwork_details' => 'artwork, logo, or customization details',
            'customization_types' => 'customization type',
            'shipping_address' => 'shipping address',
            'state_province' => 'state / province',
            'postal_code' => 'ZIP / postal code',
            'preferred_shipping_method' => 'preferred shipping method',
            'needed_by' => 'needed by / delivery date',
            'event_date' => 'event date',
            'additional_notes' => 'additional notes',
        ];
    }
}
