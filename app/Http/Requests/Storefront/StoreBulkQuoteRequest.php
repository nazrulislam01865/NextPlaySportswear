<?php

namespace App\Http\Requests\Storefront;

use App\Rules\VerifiedRecaptcha;
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
        // The prototype submits several products through the existing quote endpoint.
        // Keep legacy single-product requests working without a database change.
        $rawProducts = $this->input('quote_products_json');
        $quoteProducts = is_string($rawProducts) && strlen($rawProducts) <= 90000
            ? json_decode($rawProducts, true) : null;
        $isBuilderRequest = is_string($rawProducts);
        if ($isBuilderRequest) {
            $this->merge(['quote_products' => is_array($quoteProducts) && array_is_list($quoteProducts) ? $quoteProducts : []]);
            if (is_array($quoteProducts) && array_is_list($quoteProducts) && $quoteProducts !== []) {
                $first = is_array($quoteProducts[0]) ? $quoteProducts[0] : [];
                $total = 0;
                $selectedCustomizations = [];
                foreach ($quoteProducts as $entry) {
                    if (! is_array($entry)) continue;
                    $total += max(0, min(99999, (int) ($entry['quantity'] ?? 0)));
                    foreach ((array) ($entry['customizations'] ?? []) as $choice) {
                        if (is_string($choice)) $selectedCustomizations[] = $choice;
                    }
                }
                $quantityTier = $total < 50 ? '10-49' : ($total < 100 ? '50-99'
                    : ($total < 500 ? '100-499' : ($total < 1000 ? '500-999' : '1000-plus')));
                $this->merge([
                    'product_type' => mb_substr(trim((string) ($first['name'] ?? 'Bulk products')), 0, 190),
                    'estimated_quantity' => $quantityTier,
                    'sizes_needed' => mb_substr(trim((string) ($first['sizes'] ?? '')) ?: 'See product breakdown', 0, 190),
                    'artwork_details' => mb_substr('Product: '.trim((string) ($first['name'] ?? 'Bulk quote')).'. '
                        .(trim((string) ($first['description'] ?? '')) ?: trim((string) ($first['customization'] ?? '')) ?: 'See product specifications.'), 0, 5000),
                    'customization_types' => array_values(array_unique($selectedCustomizations)),
                ]);
            }
        }

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
            'phone_country_code_id' => $this->input('phone_country_code_id'),
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
            'email' => ['bail', 'required', 'email:rfc', 'max:190', ...($this->user()?->isCustomer() ? [Rule::in([strtolower($this->user()->email)])] : [])],
            'phone_country_code_id' => [
                'required',
                'integer',
                Rule::exists('country_calling_codes', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'phone' => ['bail', 'required', 'string', 'max:40', 'regex:/^[0-9+()\-\.\s]+$/'],
            'product_type' => ['bail', 'required', 'string', 'min:2', 'max:190'],
            'estimated_quantity' => [
                'required',
                Rule::in(['10-49', '50-99', '100-499', '500-999', '1000-plus']),
            ],
            'sizes_needed' => ['bail', 'required', 'string', 'min:1', 'max:190'],
            'budget_range' => [
                'nullable',
                Rule::exists('bulk_quote_budget_ranges', 'value')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'artwork_details' => ['bail', 'required', 'string', 'min:10', 'max:5000'],
            'customization_types' => ['array', 'max:6'],
            'customization_types.*' => [
                Rule::in(['logo', 'names-numbers', 'full-custom-design', 'embroidery', 'sublimation', 'not-sure']),
            ],
            'shipping_address' => ['bail', 'required', 'string', 'min:8', 'max:500'],
            'country' => ['required', Rule::exists('country_calling_codes', 'country_name')->where('is_active', true)],
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'preferred_shipping_method' => ['nullable', Rule::in(['standard', 'express', 'rush', 'recommend'])],
            'needed_by' => ['bail', 'required', 'date'],
            'event_date' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            // The JSON carrier is decoded to a bounded array by prepareForValidation.
            'quote_products_json' => ['sometimes', 'required', 'string', 'max:90000'],
            'quote_products' => ['required_with:quote_products_json', 'array', 'min:1', 'max:20'],
            'quote_products.*' => ['required', 'array'],
            'quote_products.*.mode' => ['required', Rule::in(['new', 'existing'])],
            'quote_products.*.name' => ['required', 'string', 'min:2', 'max:190'],
            'quote_products.*.description' => ['nullable', 'string', 'max:500'],
            'quote_products.*.quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'quote_products.*.material' => ['nullable', 'string', 'max:190'],
            'quote_products.*.colors' => ['nullable', 'string', 'max:190'],
            'quote_products.*.sizes' => ['nullable', 'string', 'max:190'],
            'quote_products.*.budget' => ['nullable', 'string', 'max:190'],
            'quote_products.*.customization' => ['nullable', 'string', 'max:500'],
            'quote_products.*.artwork_name' => ['nullable', 'string', 'max:255'],
            'quote_products.*.customizations' => ['nullable', 'array', 'max:5'],
            'quote_products.*.customizations.*' => [Rule::in(['logo', 'names-numbers', 'embroidery', 'sublimation', 'full-custom-design'])],
            'quote_products.*.catalog_product_id' => ['nullable', 'integer', 'min:1'],
            'quote_products.*.options' => ['nullable', 'array', 'max:30'],
            'quote_products.*.sizeQuantities' => ['nullable', 'array', 'max:10'],
            'quote_artworks' => ['nullable', 'array', 'max:20'],
            'quote_artworks.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'product_information' => ['nullable', 'array:product_id,options,sizes,notes'],
            'product_information.product_id' => ['required_with:product_information', 'integer', 'min:1'],
            'product_information.options' => ['nullable', 'array', 'max:30'],
            'product_information.sizes' => ['nullable', 'array', 'max:10'],
            'product_information.sizes.*' => ['array', 'max:80'],
            'product_information.notes' => ['nullable', 'string', 'max:500'],

            // Local development can submit without Google's CAPTCHA service.
            // All other environments continue to require server-side verification.
            'g-recaptcha-response' => app()->environment('local')
                ? ['exclude']
                : ['bail', 'required', 'string', 'max:4096', new VerifiedRecaptcha()],

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
            'g-recaptcha-response.required' => 'Please confirm that you are not a robot.',
            'quote_products.min' => 'Add at least one product to your request.',
            'quote_artworks.*.max' => 'Artwork must be smaller than 10 MB.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! is_string($this->input('quote_products_json'))) return;
            foreach ((array) $this->input('quote_products', []) as $index => $product) {
                if (! is_array($product)) continue;
                if (($product['mode'] ?? '') === 'new' && trim((string) ($product['description'] ?? '')) === '') {
                    $validator->errors()->add("quote_products.$index.description", 'Enter a product description.');
                }
                if (($product['mode'] ?? '') === 'existing' && (int) ($product['catalog_product_id'] ?? 0) < 1) {
                    $validator->errors()->add("quote_products.$index.catalog_product_id", 'Select a catalogue product.');
                }
                // Only unlisted products must supply all four specifications.
                // Catalogue products show the options assigned to that product;
                // unconfigured material, colour and size fields must not be forced.
                if (($product['mode'] ?? '') === 'new') {
                    foreach (['material' => 'material / fabric', 'colors' => 'colours', 'sizes' => 'size / dimensions'] as $key => $label) {
                        if (trim((string) ($product[$key] ?? '')) === '') {
                            $validator->errors()->add("quote_products.$index.$key", "Enter the $label for product ".($index + 1).'.');
                        }
                    }
                }
                // Catalogue quantity comes from the size table, so prevent a
                // mismatched headline total being saved or forwarded.
                if (($product['mode'] ?? '') === 'existing' && ! empty($product['sizeQuantities']) && is_array($product['sizeQuantities'])) {
                    $sizeTotal = 0;
                    foreach ($product['sizeQuantities'] as $amounts) {
                        if (! is_array($amounts)) continue;
                        foreach ($amounts as $amount) {
                            if (is_scalar($amount) && preg_match('/^\d{1,5}$/D', (string) $amount)) {
                                $sizeTotal += (int) $amount;
                            }
                        }
                    }
                    if ($sizeTotal !== (int) ($product['quantity'] ?? 0)) {
                        $validator->errors()->add("quote_products.$index.quantity", 'The quantity must equal the selected size quantities.');
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'organization' => 'company, team, or school',
            'phone_country_code_id' => 'country calling code',
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
            'product_information.product_id' => 'selected product',
            'product_information.notes' => 'product notes',
            'g-recaptcha-response' => 'CAPTCHA verification',
        ];
    }
}
