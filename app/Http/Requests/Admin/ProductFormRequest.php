<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:220'],
            'slug' => ['required', 'string', 'max:240', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['required', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($productId)],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'primary_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_assignments' => ['nullable', 'array', 'max:100'],
            'category_assignments.*' => ['integer', 'distinct', 'exists:categories,id'],
            'attribute_value_ids' => ['nullable', 'array', 'max:500'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:attribute_values,id'],
            // Legacy columns remain supported during the compatibility period.
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id', 'different:category_id'],
            'product_type' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:120'],
            'badge_label' => ['nullable', 'string', 'max:80'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'short_description' => ['nullable', 'string', 'max:1500'],
            'description_html' => ['nullable', 'string', 'max:100000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'maximum_quantity' => ['nullable', 'integer', 'gte:minimum_quantity', 'max:1000000'],
            'is_featured' => ['nullable', 'boolean'],
            'is_customizable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'track_inventory' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:-1000000', 'max:100000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'allow_backorder' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'shipping_class' => ['nullable', 'string', 'max:100'],
            'tax_class' => ['nullable', 'string', 'max:100'],
            'tags_text' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            'features' => ['nullable', 'array', 'max:50'],
            'features.*' => ['nullable', 'string', 'max:500'],
            'specifications' => ['nullable', 'array', 'max:100'],
            'specifications.*.name' => ['nullable', 'string', 'max:150'],
            'specifications.*.value' => ['nullable', 'string', 'max:1000'],

            'image_urls' => ['nullable', 'array', 'max:30'],
            'image_urls.*.url' => ['nullable', 'url', 'max:2048'],
            'image_urls.*.alt' => ['nullable', 'string', 'max:255'],
            'image_urls.*.is_primary' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],

            'price_tiers' => ['nullable', 'array', 'max:100'],
            'price_tiers.*.label' => ['nullable', 'string', 'max:120'],
            'price_tiers.*.minimum_quantity' => ['required_with:price_tiers.*.unit_price', 'nullable', 'integer', 'min:1'],
            'price_tiers.*.maximum_quantity' => ['nullable', 'integer', 'gte:price_tiers.*.minimum_quantity'],
            'price_tiers.*.unit_price' => ['required_with:price_tiers.*.minimum_quantity', 'nullable', 'numeric', 'min:0'],
            'price_tiers.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'price_tiers.*.savings_label' => ['nullable', 'string', 'max:120'],

            'price_table_headers' => ['nullable', 'array', 'max:20'],
            'price_table_headers.*' => ['nullable', 'string', 'max:150'],
            'price_table_rows' => ['nullable', 'array', 'max:200'],
            'price_table_rows.*' => ['nullable', 'array', 'max:20'],
            'price_table_rows.*.*' => ['nullable', 'string', 'max:500'],
            'price_table_highlight_column' => ['nullable', 'integer', 'min:0', 'max:19'],
            'price_table_note' => ['nullable', 'string', 'max:3000'],

            'option_groups' => ['nullable', 'array', 'max:100'],
            'option_groups.*.name' => ['nullable', 'string', 'max:160'],
            'option_groups.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'option_groups.*.section' => ['nullable', Rule::in(['product', 'decoration'])],
            'option_groups.*.type' => ['nullable', Rule::in(['image', 'swatch', 'buttons', 'select', 'checkbox', 'text', 'textarea', 'number', 'file', 'date'])],
            'option_groups.*.description' => ['nullable', 'string', 'max:2000'],
            'option_groups.*.placeholder' => ['nullable', 'string', 'max:255'],
            'option_groups.*.is_required' => ['nullable', 'boolean'],
            'option_groups.*.minimum_selections' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'option_groups.*.maximum_selections' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'option_groups.*.accepted_file_types' => ['nullable', 'string', 'max:500'],
            'option_groups.*.maximum_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
            'option_groups.*.is_active' => ['nullable', 'boolean'],
            'option_groups.*.values' => ['nullable', 'array', 'max:200'],
            'option_groups.*.values.*.label' => ['nullable', 'string', 'max:180'],
            'option_groups.*.values.*.code' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'option_groups.*.values.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'option_groups.*.values.*.description' => ['nullable', 'string', 'max:2000'],
            'option_groups.*.values.*.color_hex' => ['nullable', 'regex:/^#[0-9A-F]{6}$/'],
            'option_groups.*.values.*.image_url' => ['nullable', 'url', 'max:2048'],
            'option_groups.*.values.*.image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'option_groups.*.values.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'option_groups.*.values.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'option_groups.*.values.*.is_default' => ['nullable', 'boolean'],
            'option_groups.*.values.*.is_active' => ['nullable', 'boolean'],

            'size_groups' => ['nullable', 'array', 'max:50'],
            'size_groups.*.name' => ['nullable', 'string', 'max:120'],
            'size_groups.*.code' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'size_groups.*.sizes_text' => ['nullable', 'string', 'max:5000'],
            'size_groups.*.is_active' => ['nullable', 'boolean'],

            'artwork_methods' => ['nullable', 'array', 'max:50'],
            'artwork_methods.*.name' => ['nullable', 'string', 'max:160'],
            'artwork_methods.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'artwork_methods.*.icon' => ['nullable', 'string', 'max:40'],
            'artwork_methods.*.description' => ['nullable', 'string', 'max:2000'],
            'artwork_methods.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'artwork_methods.*.requires_upload' => ['nullable', 'boolean'],
            'artwork_methods.*.is_active' => ['nullable', 'boolean'],

            'production_speeds' => ['nullable', 'array', 'max:50'],
            'production_speeds.*.name' => ['nullable', 'string', 'max:160'],
            'production_speeds.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'production_speeds.*.description' => ['nullable', 'string', 'max:2000'],
            'production_speeds.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'production_speeds.*.minimum_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'production_speeds.*.maximum_days' => ['nullable', 'integer', 'gte:production_speeds.*.minimum_days', 'max:3650'],
            'production_speeds.*.is_active' => ['nullable', 'boolean'],

            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string', 'max:5000'],
            'faqs.*.is_active' => ['nullable', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:2000'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'og_image_url' => ['nullable', 'url', 'max:2048'],
            'robots_index' => ['nullable', 'boolean'],
            'robots_follow' => ['nullable', 'boolean'],
            'schema_json_text' => ['nullable', 'json', 'max:50000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignments = collect($this->input('category_assignments', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)->unique()->values();
        $primary = $this->input('primary_category_id');
        if (filled($primary)) {
            $primary = (int) $primary;
            $assignments->prepend($primary);
        } elseif ($assignments->isNotEmpty()) {
            // Every categorized product has one deterministic primary placement for
            // breadcrumbs, canonical grouping, exports, and legacy compatibility.
            $primary = (int) $assignments->first();
        } else {
            $primary = null;
        }
        $this->merge([
            'primary_category_id' => $primary,
            'category_assignments' => $assignments->unique()->values()->all(),
            'attribute_value_ids' => collect($this->input('attribute_value_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all(),
        ]);

        $optionGroups = collect($this->input('option_groups', []))
            ->map(function ($group): array {
                if (! is_array($group)) {
                    return [];
                }

                $group['values'] = collect($group['values'] ?? [])
                    ->map(function ($value): array {
                        if (! is_array($value)) {
                            return [];
                        }
                        $hex = trim((string) ($value['color_hex'] ?? ''));

                        if ($hex !== '') {
                            $hex = ltrim($hex, '#');

                            if (preg_match('/^[0-9a-fA-F]{3}$/', $hex) === 1) {
                                $hex = implode('', array_map(
                                    static fn (string $character): string => $character.$character,
                                    str_split($hex)
                                ));
                            }

                            $value['color_hex'] = '#'.strtoupper($hex);
                        } else {
                            $value['color_hex'] = null;
                        }

                        $value['image_url'] = filled($value['image_url'] ?? null)
                            ? trim((string) $value['image_url'])
                            : null;

                        return $value;
                    })
                    ->values()
                    ->all();

                return $group;
            })
            ->values()
            ->all();

        $this->merge([
            'option_groups' => $optionGroups,
            'is_featured' => $this->boolean('is_featured'),
            'is_customizable' => $this->boolean('is_customizable'),
            'is_active' => $this->boolean('is_active'),
            'track_inventory' => $this->boolean('track_inventory'),
            'allow_backorder' => $this->boolean('allow_backorder'),
            'robots_index' => $this->boolean('robots_index'),
            'robots_follow' => $this->boolean('robots_follow'),
        ]);
    }

    public function messages(): array
    {
        return [
            'option_groups.*.values.*.color_hex.regex' => 'Enter a valid HEX color such as #15345D or 15345D.',
            'option_groups.*.values.*.image_file.image' => 'Each option image must be a valid image file.',
            'option_groups.*.values.*.image_file.mimes' => 'Option images must be JPG, PNG, WebP, or AVIF files.',
            'option_groups.*.values.*.image_file.max' => 'Each option image must not exceed 5 MB.',
        ];
    }
}
