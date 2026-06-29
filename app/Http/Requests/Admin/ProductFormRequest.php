<?php

namespace App\Http\Requests\Admin;

use App\Enums\JerseyCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\Product;
use App\Support\ProductionTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'show_in_category_page' => ['nullable', 'boolean'],
            'attribute_value_ids' => ['nullable', 'array', 'max:500'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:attribute_values,id'],
            // Legacy columns remain supported during the compatibility period.
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id', 'different:category_id'],
            'product_type' => ['nullable', 'string', 'max:100'],
            'product_profile' => ['required', Rule::in(['standard', 'jersey', 'tshirt', 'other'])],
            'brand' => ['nullable', 'string', 'max:120'],
            'badge_label' => ['nullable', 'string', 'max:80'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'short_description' => ['nullable', 'string', 'max:1500'],
            'description_html' => ['nullable', 'string', 'max:100000'],
            'product_specification_text' => ['nullable', 'string', 'max:100000'],
            'detail_information_html' => ['nullable', 'string', 'max:100000'],
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
            'shipping_methods_enabled' => ['nullable', 'boolean'],
            'jersey_roster_enabled' => ['nullable', 'boolean'],
            'jersey_roster_optional' => ['nullable', 'boolean'],
            'jersey_roster_title' => ['nullable', 'string', 'max:180'],
            'jersey_roster_fields' => ['nullable', 'array', 'max:20'],
            'jersey_roster_fields.*.key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_\-]+$/', 'distinct'],
            'jersey_roster_fields.*.label' => ['nullable', 'string', 'max:120'],
            'jersey_roster_fields.*.type' => ['nullable', Rule::in(['text', 'number'])],
            'jersey_roster_fields.*.max_length' => ['nullable', 'integer', 'min:1', 'max:120'],
            'jersey_roster_fields.*.required' => ['nullable', 'boolean'],
            'jersey_roster_fields.*.enabled' => ['nullable', 'boolean'],
            'artwork_upload_enabled' => ['nullable', 'boolean'],
            'artwork_upload_required' => ['nullable', 'boolean'],
            'artwork_upload_title' => ['nullable', 'string', 'max:180'],
            'artwork_upload_description' => ['nullable', 'string', 'max:3000'],
            'artwork_upload_max_files' => ['nullable', 'integer', 'min:1', 'max:12'],
            'artwork_upload_max_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:25'],
            'artwork_upload_accepted_types' => ['nullable', 'string', 'max:500', 'regex:/^[a-z0-9,\s]+$/i'],
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
            'image_urls.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'image_urls.*.url' => ['nullable', 'url', 'max:2048'],
            'image_urls.*.name' => ['nullable', 'string', 'max:255'],
            'image_urls.*.alt' => ['nullable', 'string', 'max:255'],
            'image_urls.*.is_primary' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'new_image_primary_index' => ['nullable', 'integer', 'min:0', 'max:19'],

            'price_tiers' => ['nullable', 'array', 'max:100'],
            'price_tiers.*.label' => ['nullable', 'string', 'max:120'],
            'price_tiers.*.minimum_quantity' => ['required_with:price_tiers.*.unit_price', 'nullable', 'integer', 'min:1'],
            'price_tiers.*.maximum_quantity' => ['nullable', 'integer', 'gte:price_tiers.*.minimum_quantity'],
            'price_tiers.*.unit_price' => ['required_with:price_tiers.*.minimum_quantity', 'nullable', 'numeric', 'min:0'],
            'price_tiers.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'price_tiers.*.savings_label' => ['nullable', 'string', 'max:120'],

            'price_table_headers' => ['required', 'array', 'min:2', 'max:20'],
            'price_table_headers.0' => ['required', 'string', Rule::in(['Quantity'])],
            'price_table_headers.*' => ['required', 'string', 'max:150'],
            'price_table_rows' => ['required', 'array', 'min:1', 'max:200'],
            'price_table_rows.*' => ['required', 'array', 'max:20'],
            'price_table_rows.*.*' => ['nullable', 'string', 'max:500'],
            'price_table_ranges' => ['required', 'array', 'min:1', 'max:200'],
            'price_table_ranges.*' => ['required', 'array'],
            'price_table_ranges.*.minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'price_table_ranges.*.maximum_quantity' => ['nullable', 'integer', 'gte:price_table_ranges.*.minimum_quantity', 'max:1000000'],
            'price_table_highlight_column' => ['required', 'integer', 'min:1', 'max:19'],
            'price_table_note' => ['nullable', 'string', 'max:3000'],

            'option_groups' => ['nullable', 'array', 'max:100'],
            'option_groups.*.name' => ['nullable', 'string', 'max:160'],
            'option_groups.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'option_groups.*.section' => ['nullable', Rule::in(['product', 'decoration'])],
            'option_groups.*.type' => ['nullable', Rule::in(['image', 'swatch', 'buttons', 'select', 'checkbox', 'text', 'textarea', 'number', 'file', 'date'])],
            'option_groups.*.jersey_customization_type' => ['nullable', Rule::in(array_keys(JerseyCustomizationType::options()))],
            'option_groups.*.display_mode' => ['nullable', Rule::in(['hidden', 'fixed', 'customer'])],
            'option_groups.*.fixed_value_code' => ['nullable', 'string', 'max:180'],
            'option_groups.*.fixed_text_value' => ['nullable', 'string', 'max:2000'],
            'option_groups.*.show_in_summary' => ['nullable', 'boolean'],
            'option_groups.*.use_as_filter' => ['nullable', 'boolean'],
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
            'option_groups.*.values.*.jersey_customization_option_id' => ['nullable', 'integer', 'distinct', 'exists:jersey_customization_options,id'],
            'option_groups.*.values.*.description' => ['nullable', 'string', 'max:2000'],
            'option_groups.*.values.*.color_hex' => ['nullable', 'regex:/^#[0-9A-F]{6}$/'],
            'option_groups.*.values.*.image_url' => ['nullable', 'url', 'max:2048'],
            'option_groups.*.values.*.image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'option_groups.*.values.*.image_files' => ['nullable', 'array', 'max:12'],
            'option_groups.*.values.*.image_files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'option_groups.*.values.*.clear_images' => ['nullable', 'boolean'],
            'option_groups.*.values.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'option_groups.*.values.*.charge_type' => ['nullable', Rule::in(['included', 'per_unit', 'fixed_order'])],
            'option_groups.*.values.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'option_groups.*.values.*.is_default' => ['nullable', 'boolean'],
            'option_groups.*.values.*.is_active' => ['nullable', 'boolean'],

            'size_groups' => ['nullable', 'array', 'max:50'],
            'size_groups.*.existing_id' => ['nullable', 'integer', 'min:1'],
            'size_groups.*.size_option_group_id' => ['nullable', 'integer', 'distinct', 'exists:size_option_groups,id'],
            'size_groups.*.name' => ['nullable', 'string', 'max:120'],
            'size_groups.*.code' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'size_groups.*.description_html' => ['nullable', 'string', 'max:100000'],
            'size_groups.*.chart_html' => ['nullable', 'string', 'max:100000'],
            'size_groups.*.sizes_text' => ['nullable', 'string', 'max:5000'],
            'size_groups.*.is_active' => ['nullable', 'boolean'],
            'size_groups.*.chart_enabled' => ['nullable', 'boolean'],
            'size_groups.*.chart_title' => ['nullable', 'string', 'max:180'],
            'size_groups.*.chart_note' => ['nullable', 'string', 'max:2000'],
            'size_groups.*.chart_columns_text' => ['nullable', 'string', 'max:2000'],
            'size_groups.*.chart_rows_text' => ['nullable', 'string', 'max:30000'],
            'size_groups.*.chart_image_url' => ['nullable', 'url', 'max:2048'],
            'size_groups.*.chart_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'size_groups.*.clear_chart_image' => ['nullable', 'boolean'],

            'artwork_methods' => ['nullable', 'array', 'max:50'],
            'artwork_methods.*.name' => ['nullable', 'string', 'max:160'],
            'artwork_methods.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'artwork_methods.*.icon' => ['nullable', 'string', 'max:40'],
            'artwork_methods.*.description' => ['nullable', 'string', 'max:2000'],
            'artwork_methods.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'artwork_methods.*.requires_upload' => ['nullable', 'boolean'],
            'artwork_methods.*.is_active' => ['nullable', 'boolean'],

            'production_table_headers' => ['nullable', 'array', 'max:12'],
            'production_table_headers.*' => ['required', 'string', 'max:160', 'distinct'],
            'production_table_rows' => ['nullable', 'array', 'max:100'],
            'production_table_rows.*.range' => ['required', 'string', 'max:50'],
            'production_table_rows.*.cells' => ['nullable', 'array', 'max:12'],
            'production_table_rows.*.cells.*.enabled' => ['nullable', 'boolean'],
            'production_table_rows.*.cells.*.description' => ['nullable', 'string', 'max:2000'],
            'production_table_rows.*.cells.*.price_adjustment' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'production_table_rows.*.cells.*.production_time' => ['nullable', 'string', 'max:60'],

            // Flattened by prepareForValidation for the existing persistence layer.
            'production_speeds' => ['nullable', 'array', 'max:150'],
            'production_speeds.*.name' => ['nullable', 'string', 'max:160'],
            'production_speeds.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'production_speeds.*.description' => ['nullable', 'string', 'max:2000'],
            'production_speeds.*.price_adjustment' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'production_speeds.*.minimum_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'production_speeds.*.maximum_quantity' => ['nullable', 'integer', 'gte:production_speeds.*.minimum_quantity', 'max:1000000'],
            'production_speeds.*.minimum_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'production_speeds.*.maximum_days' => ['required', 'integer', 'gte:production_speeds.*.minimum_days', 'max:3650'],
            'production_speeds.*.is_active' => ['nullable', 'boolean'],

            'shipping_methods' => ['nullable', 'array', 'max:30'],
            'shipping_methods.*.name' => ['nullable', 'string', 'max:160'],
            'shipping_methods.*.code' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'shipping_methods.*.description' => ['nullable', 'string', 'max:2000'],
            'shipping_methods.*.price_adjustment' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'shipping_methods.*.charge_type' => ['nullable', Rule::in(['included', 'per_unit', 'fixed_order'])],
            'shipping_methods.*.minimum_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'shipping_methods.*.maximum_days' => ['nullable', 'integer', 'gte:shipping_methods.*.minimum_days', 'max:3650'],
            'shipping_methods.*.is_default' => ['nullable', 'boolean'],
            'shipping_methods.*.is_active' => ['nullable', 'boolean'],

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
        $routeProduct = $this->route('product');
        $sku = trim((string) $this->input('sku', ''));
        if ($sku === '') {
            $sku = $routeProduct?->sku ?: $this->generateSku((string) $this->input('name', 'Product'));
        }
        $this->merge(['sku' => $sku]);

        $showInCategoryPage = $this->boolean('show_in_category_page');
        $assignments = collect($this->input('category_assignments', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)->unique()->values();
        $primary = $this->input('primary_category_id');
        if (filled($primary)) {
            $primary = (int) $primary;
            if ($showInCategoryPage) {
                $assignments->prepend($primary);
            }
        } elseif ($assignments->isNotEmpty()) {
            // Every categorized product has one deterministic primary placement for
            // breadcrumbs, canonical grouping, exports, and legacy compatibility.
            $primary = (int) $assignments->first();
        } else {
            $primary = null;
        }

        if (! $showInCategoryPage) {
            $assignments = collect();
        }

        $this->merge([
            'primary_category_id' => $primary,
            'category_assignments' => $assignments->unique()->values()->all(),
            'show_in_category_page' => $showInCategoryPage,
            'attribute_value_ids' => collect($this->input('attribute_value_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all(),
        ]);

        $pricing = $this->normalizeVisiblePricing();
        $this->merge($pricing);

        $submittedMasterOptionIds = collect($this->input('option_groups', []))
            ->flatMap(static fn ($group) => collect(is_array($group) ? ($group['values'] ?? []) : [])
                ->pluck('jersey_customization_option_id'))
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $masterOptionLookup = JerseyCustomizationOption::query()
            ->active()
            ->whereIn('id', $submittedMasterOptionIds)
            ->get()
            ->keyBy('id');

        $usedGroupCodes = [];
        $optionGroups = collect($this->input('option_groups', []))
            ->map(function ($group, int $groupIndex) use (&$usedGroupCodes, $masterOptionLookup): array {
                if (! is_array($group)) {
                    return [];
                }

                $choiceTypes = ['image', 'swatch', 'buttons', 'select', 'checkbox'];
                $baseGroupCode = Str::slug((string) ($group['code'] ?? $group['name'] ?? '')) ?: 'feature-'.($groupIndex + 1);
                $groupCode = $baseGroupCode;
                $suffix = 2;
                while (in_array($groupCode, $usedGroupCodes, true)) {
                    $groupCode = $baseGroupCode.'-'.$suffix++;
                }
                $masterType = JerseyCustomizationType::tryFrom((string) ($group['jersey_customization_type'] ?? ''));
                if ($masterType) {
                    $group['name'] = $masterType->label();
                    // Enum values use underscores for stable PHP/database identifiers,
                    // while product option-group codes are URL-style slugs. Always
                    // regenerate the hidden code here so an internal field can never
                    // block an administrator from saving the product.
                    $baseGroupCode = $masterType->productCode() ?: 'feature-'.($groupIndex + 1);
                    $groupCode = $baseGroupCode;
                    $suffix = 2;
                    while (in_array($groupCode, $usedGroupCodes, true)) {
                        $groupCode = $baseGroupCode.'-'.$suffix++;
                    }
                }

                $usedGroupCodes[] = $groupCode;
                $group['code'] = $groupCode;
                $group['jersey_customization_type'] = $masterType?->value;
                // The simplified product-feature editor only creates storefront-selectable
                // features. The server enforces the same behavior rather than trusting a
                // modified hidden form value.
                $group['section'] = 'product';
                $group['display_mode'] = 'customer';
                $group['fixed_value_code'] = null;
                $group['fixed_text_value'] = null;
                $group['show_in_summary'] = filter_var($group['show_in_summary'] ?? true, FILTER_VALIDATE_BOOL);
                $group['is_required'] = filter_var($group['is_required'] ?? false, FILTER_VALIDATE_BOOL);
                $group['is_active'] = true;
                $group['use_as_filter'] = filter_var($group['use_as_filter'] ?? false, FILTER_VALIDATE_BOOL)
                    && in_array(($group['type'] ?? 'select'), $choiceTypes, true)
                    && ($group['display_mode'] ?? 'customer') !== 'hidden';

                $group['values'] = collect($group['values'] ?? [])
                    ->map(function ($value, int $valueIndex) use ($masterOptionLookup): array {
                        if (! is_array($value)) {
                            return [];
                        }
                        $masterOptionId = filter_var($value['jersey_customization_option_id'] ?? null, FILTER_VALIDATE_INT);
                        $value['jersey_customization_option_id'] = $masterOptionId !== false ? $masterOptionId : null;
                        $masterOption = $masterOptionId !== false ? $masterOptionLookup->get((int) $masterOptionId) : null;
                        if ($masterOption) {
                            $value['label'] = $masterOption->name;
                            $value['code'] = $masterOption->slug;
                            $value['description'] = $masterOption->description;
                            $value['color_hex'] = $masterOption->color_hex;
                            $value['image_url'] = null;
                        } else {
                            $value['code'] = Str::slug((string) ($value['code'] ?? $value['label'] ?? '')) ?: 'value-'.($valueIndex + 1);
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

        $normalizeRowsWithCodes = static function (array $rows, string $nameKey = 'name'): array {
            $used = [];

            return collect($rows)->map(function ($row, int $index) use (&$used, $nameKey): array {
                if (! is_array($row)) {
                    return [];
                }

                $base = Str::slug((string) ($row['code'] ?? $row[$nameKey] ?? '')) ?: 'item-'.($index + 1);
                $code = $base;
                $suffix = 2;
                while (in_array($code, $used, true)) {
                    $code = $base.'-'.$suffix++;
                }
                $used[] = $code;
                $row['code'] = $code;
                $row['is_active'] = true;

                return $row;
            })->values()->all();
        };

        $production = $this->normalizeProductionTable();
        $productionSpeeds = $normalizeRowsWithCodes($production['production_speeds']);

        $rosterFields = collect($this->input('jersey_roster_fields', []))
            ->map(function ($field, int $index): array {
                if (! is_array($field)) {
                    return [];
                }

                $field['key'] = Str::slug((string) ($field['key'] ?? $field['label'] ?? ''), '_') ?: 'field_'.($index + 1);
                $field['enabled'] = true;

                return $field;
            })->values()->all();

        $this->merge([
            'option_groups' => $optionGroups,
            'size_groups' => $normalizeRowsWithCodes((array) $this->input('size_groups', [])),
            'production_table_headers' => $production['production_table_headers'],
            'production_table_rows' => $production['production_table_rows'],
            'production_speeds' => $productionSpeeds,
            'shipping_methods' => $normalizeRowsWithCodes((array) $this->input('shipping_methods', [])),
            'jersey_roster_fields' => $rosterFields,
            'product_profile' => $this->input('product_profile', 'standard'),
            'shipping_methods_enabled' => $this->boolean('shipping_methods_enabled'),
            'jersey_roster_enabled' => $this->boolean('jersey_roster_enabled'),
            'jersey_roster_optional' => $this->boolean('jersey_roster_optional'),
            'artwork_upload_enabled' => $this->boolean('artwork_upload_enabled'),
            'artwork_upload_required' => $this->boolean('artwork_upload_required'),
            'is_featured' => $this->boolean('is_featured'),
            'is_customizable' => $this->boolean('is_customizable'),
            'is_active' => $this->boolean('is_active'),
            'track_inventory' => $this->boolean('track_inventory'),
            'allow_backorder' => $this->boolean('allow_backorder'),
            'robots_index' => $this->boolean('robots_index'),
            'robots_follow' => $this->boolean('robots_follow'),
        ]);
    }

    private function generateSku(string $name): string
    {
        $stem = Str::upper(Str::substr((string) preg_replace('/[^a-z0-9]+/i', '', $name), 0, 12));
        $stem = $stem !== '' ? $stem : 'PRODUCT';

        do {
            $candidate = 'NPS-'.$stem.'-'.Str::upper(Str::random(6));
        } while (Product::query()->where('sku', $candidate)->exists());

        return $candidate;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $groups = collect($this->input('option_groups', []))->values();
            $masterIds = $groups
                ->flatMap(static fn ($group) => collect($group['values'] ?? [])->pluck('jersey_customization_option_id'))
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values();
            $masterOptions = JerseyCustomizationOption::query()
                ->whereIn('id', $masterIds)
                ->get(['id', 'type', 'is_active'])
                ->keyBy('id');

            foreach ($groups as $groupIndex => $group) {
                $masterType = JerseyCustomizationType::tryFrom((string) data_get($group, 'jersey_customization_type', ''));
                $selectedIds = collect(data_get($group, 'values', []))
                    ->pluck('jersey_customization_option_id')
                    ->filter()
                    ->map(static fn ($id): int => (int) $id)
                    ->values();

                if ($selectedIds->duplicates()->isNotEmpty()) {
                    $validator->errors()->add("option_groups.{$groupIndex}.values", 'The same customization item cannot be selected more than once.');
                }

                foreach ($selectedIds as $valueIndex => $selectedId) {
                    $masterOption = $masterOptions->get($selectedId);
                    if (! $masterOption || ! $masterOption->is_active) {
                        $validator->errors()->add("option_groups.{$groupIndex}.values.{$valueIndex}.jersey_customization_option_id", 'The selected customization item is unavailable.');
                        continue;
                    }

                    if (! $masterType || $masterOption->type !== $masterType) {
                        $validator->errors()->add("option_groups.{$groupIndex}.values.{$valueIndex}.jersey_customization_option_id", 'The selected customization item does not belong to this feature type.');
                    }
                }
            }

            $ranges = collect($this->input('price_table_ranges', []))->values();
            $rows = collect($this->input('price_table_rows', []))->values();
            $tiers = collect($this->input('price_tiers', []))->values();

            if ($ranges->count() !== $rows->count()) {
                $validator->errors()->add('price_table_ranges', 'Every visible price row must have one minimum and maximum quantity range.');
            }

            $previousMaximum = null;
            $previousMinimum = null;
            $previousWasOpenEnded = false;
            foreach ($ranges as $index => $range) {
                $minimum = filter_var(data_get($range, 'minimum_quantity'), FILTER_VALIDATE_INT);
                $maximumRaw = data_get($range, 'maximum_quantity');
                $maximum = filled($maximumRaw) ? filter_var($maximumRaw, FILTER_VALIDATE_INT) : null;

                if ($index > 0 && $minimum !== false && $previousMinimum !== null && $minimum <= $previousMinimum) {
                    $validator->errors()->add("price_table_ranges.{$index}.minimum_quantity", 'Quantity starting values must increase from one row to the next.');
                }

                if ($index > 0 && $previousWasOpenEnded) {
                    $validator->errors()->add("price_table_ranges.{$index}.minimum_quantity", 'No row can follow an open-ended quantity row.');
                }

                if ($index > 0 && $minimum !== false && $previousMaximum !== null && $minimum !== $previousMaximum + 1) {
                    $validator->errors()->add("price_table_ranges.{$index}.minimum_quantity", 'Quantity rows must be continuous. Start this row at '.($previousMaximum + 1).'.');
                }

                if ($maximum === null && $index < $ranges->count() - 1) {
                    $validator->errors()->add("price_table_ranges.{$index}.maximum_quantity", 'Only the final quantity row may have no maximum quantity.');
                }

                if (! is_numeric(data_get($tiers, "{$index}.unit_price"))) {
                    $validator->errors()->add("price_table_rows.{$index}", 'Enter a valid unit price in the highlighted price column for this row.');
                }

                $previousMaximum = $maximum === false ? null : $maximum;
                $previousMinimum = $minimum === false ? $previousMinimum : $minimum;
                $previousWasOpenEnded = $maximum === null;
            }

            $productionHeaders = collect($this->input('production_table_headers', []))->values();
            $productionRows = collect($this->input('production_table_rows', []))->values();
            $parsedProductionRanges = [];

            foreach ($productionRows as $rowIndex => $row) {
                $rangeText = trim((string) data_get($row, 'range', ''));
                $parsed = $this->parseProductionRange($rangeText);
                if ($parsed === null) {
                    $validator->errors()->add("production_table_rows.{$rowIndex}.range", 'Enter a valid quantity range such as 1-40, 41+, or 25.');
                    continue;
                }

                foreach ((array) data_get($row, 'cells', []) as $columnIndex => $cell) {
                    if (! filter_var(data_get($cell, 'enabled', false), FILTER_VALIDATE_BOOL)) {
                        continue;
                    }

                    if (! filled($productionHeaders->get($columnIndex))) {
                        $validator->errors()->add("production_table_headers.{$columnIndex}", 'Enter a production option name for every enabled column.');
                    }

                    $productionTime = ProductionTime::parse(data_get($cell, 'production_time'));
                    if ($productionTime === null) {
                        $validator->errors()->add(
                            "production_table_rows.{$rowIndex}.cells.{$columnIndex}.production_time",
                            'Enter a valid production time such as 5 days or 5-15 days.'
                        );
                    }
                }

                foreach ($parsedProductionRanges as $previousIndex => $previous) {
                    $currentMaximum = $parsed['maximum_quantity'] ?? PHP_INT_MAX;
                    $previousMaximum = $previous['maximum_quantity'] ?? PHP_INT_MAX;
                    if ($parsed['minimum_quantity'] <= $previousMaximum && $previous['minimum_quantity'] <= $currentMaximum) {
                        $validator->errors()->add("production_table_rows.{$rowIndex}.range", 'Production quantity ranges cannot overlap another row.');
                        break;
                    }
                }

                $parsedProductionRanges[$rowIndex] = $parsed;
            }
        });
    }

    /**
     * Normalize the independent production table and flatten enabled cells into
     * the existing product_production_speeds persistence model.
     */
    private function normalizeProductionTable(): array
    {
        $submittedHeaders = collect($this->input('production_table_headers', []))->values();
        $headerIndexes = $submittedHeaders
            ->map(fn ($header, int $index): array => ['index' => $index, 'label' => trim((string) $header)])
            ->filter(fn (array $header): bool => $header['label'] !== '')
            ->take(12)
            ->values();
        $headers = $headerIndexes->pluck('label')->all();
        $rows = [];
        $speeds = [];

        foreach (collect($this->input('production_table_rows', []))->values()->take(100) as $rowIndex => $row) {
            if (! is_array($row)) {
                continue;
            }

            $rangeText = trim((string) ($row['range'] ?? ''));
            $parsedRange = $this->parseProductionRange($rangeText);
            $submittedCells = collect($row['cells'] ?? [])->values();
            $cells = [];

            foreach ($headerIndexes as $columnIndex => $header) {
                $cell = (array) $submittedCells->get($header['index'], []);
                $productionTime = ProductionTime::parse($cell['production_time'] ?? null);
                if ($productionTime === null && (array_key_exists('minimum_days', $cell) || array_key_exists('maximum_days', $cell))) {
                    $productionTime = ProductionTime::parse(ProductionTime::format(
                        $cell['minimum_days'] ?? 0,
                        $cell['maximum_days'] ?? ($cell['minimum_days'] ?? 0)
                    ));
                }
                $minimumDays = $productionTime['minimum_days'] ?? 0;
                $maximumDays = $productionTime['maximum_days'] ?? $minimumDays;
                $enabled = filter_var($cell['enabled'] ?? false, FILTER_VALIDATE_BOOL);
                $normalizedCell = [
                    'enabled' => $enabled,
                    'description' => filled($cell['description'] ?? null) ? trim((string) $cell['description']) : null,
                    'price_adjustment' => max(0, round((float) ($cell['price_adjustment'] ?? 0), 2)),
                    'production_time' => $productionTime['display'] ?? '',
                    'minimum_days' => $minimumDays,
                    'maximum_days' => $maximumDays,
                ];
                $cells[] = $normalizedCell;

                if ($enabled && $parsedRange !== null) {
                    $speeds[] = [
                        'name' => $header['label'],
                        'code' => null,
                        'description' => $normalizedCell['description'],
                        'price_adjustment' => $normalizedCell['price_adjustment'],
                        'minimum_quantity' => $parsedRange['minimum_quantity'],
                        'maximum_quantity' => $parsedRange['maximum_quantity'],
                        'minimum_days' => $minimumDays,
                        'maximum_days' => $maximumDays,
                        'is_active' => true,
                    ];
                }
            }

            if ($rangeText !== '' || collect($cells)->contains(fn (array $cell): bool => $cell['enabled'])) {
                $rows[] = ['range' => $rangeText, 'cells' => $cells];
            }
        }

        return [
            'production_table_headers' => $headers,
            'production_table_rows' => $rows,
            'production_speeds' => $speeds,
        ];
    }

    /** @return array{minimum_quantity:int, maximum_quantity:?int}|null */
    private function parseProductionRange(string $value): ?array
    {
        $value = trim(str_replace([',', '–', '—'], ['', '-', '-'], $value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d+)\s*(?:-|to)\s*(\d+)$/i', $value, $match) === 1) {
            $minimum = (int) $match[1];
            $maximum = (int) $match[2];

            return $minimum >= 1 && $maximum >= $minimum
                ? ['minimum_quantity' => $minimum, 'maximum_quantity' => $maximum]
                : null;
        }

        if (preg_match('/^(\d+)\s*\+$/', $value, $match) === 1) {
            $minimum = (int) $match[1];

            return $minimum >= 1
                ? ['minimum_quantity' => $minimum, 'maximum_quantity' => null]
                : null;
        }

        if (preg_match('/^(\d+)$/', $value, $match) === 1) {
            $quantity = (int) $match[1];

            return $quantity >= 1
                ? ['minimum_quantity' => $quantity, 'maximum_quantity' => $quantity]
                : null;
        }

        return null;
    }

    private function normalizeVisiblePricing(): array
    {
        $headers = collect($this->input('price_table_headers', []))
            ->map(fn ($header) => trim((string) $header))
            ->values();

        if ($headers->isEmpty()) {
            $headers = collect(['Quantity', 'Unit Price']);
        }
        $headers[0] = 'Quantity';
        if ($headers->count() === 1) {
            $headers->push('Unit Price');
        }

        $highlightColumn = filter_var($this->input('price_table_highlight_column', 1), FILTER_VALIDATE_INT);
        $highlightColumn = $highlightColumn === false ? 1 : max(1, min($headers->count() - 1, $highlightColumn));
        $ranges = $this->normalizeQuantityRanges($this->input('price_table_ranges', []));
        $rows = collect($this->input('price_table_rows', []))->values();
        $normalizedRows = [];
        $priceTiers = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $range = $ranges->get($index, []);
            $minimum = filter_var(data_get($range, 'minimum_quantity'), FILTER_VALIDATE_INT);
            $minimum = $minimum !== false && $minimum >= 1 ? $minimum : null;
            $maximumRaw = data_get($range, 'maximum_quantity');
            $maximum = filled($maximumRaw) ? filter_var($maximumRaw, FILTER_VALIDATE_INT) : null;
            $maximum = $maximum === false ? null : $maximum;

            $cells = collect($row)
                ->take($headers->count())
                ->map(fn ($cell) => trim((string) $cell))
                ->values()
                ->all();
            $cells = array_pad($cells, $headers->count(), '');
            $cells[0] = $minimum === null ? '' : (string) $minimum;
            $unitPriceColumn = $this->resolveLivePriceColumn($headers->all(), $cells, $highlightColumn);
            $unitPrice = $unitPriceColumn === null ? null : $this->parseMoney($cells[$unitPriceColumn] ?? null);
            $savingsLabel = $this->findSavingsLabel($headers->all(), $cells);

            $normalizedRows[] = $cells;
            $priceTiers[] = [
                'label' => $minimum === null ? null : (string) $minimum,
                'minimum_quantity' => $minimum,
                'maximum_quantity' => $maximum,
                'unit_price' => $unitPrice,
                'compare_at_price' => null,
                'savings_label' => $savingsLabel,
            ];
        }

        $usableTiers = collect($priceTiers)
            ->filter(fn ($tier) => is_int($tier['minimum_quantity']) && is_numeric($tier['unit_price']))
            ->values();
        $firstTier = $usableTiers->sortBy('minimum_quantity')->first();
        $basePrice = $firstTier['unit_price'] ?? null;
        $minimumQuantity = $firstTier['minimum_quantity'] ?? null;
        $maximumQuantity = null;
        if ($usableTiers->isNotEmpty() && $usableTiers->every(fn ($tier) => $tier['maximum_quantity'] !== null)) {
            $maximumQuantity = $usableTiers->max('maximum_quantity');
        }

        return [
            'price_table_headers' => $headers->all(),
            'price_table_rows' => $normalizedRows,
            'price_table_ranges' => $ranges->all(),
            'price_table_highlight_column' => $highlightColumn,
            'price_tiers' => $priceTiers,
            'base_price' => $basePrice ?? $this->input('base_price', 0),
            'minimum_quantity' => $minimumQuantity ?? $this->input('minimum_quantity', 1),
            'maximum_quantity' => $usableTiers->isNotEmpty() ? $maximumQuantity : $this->input('maximum_quantity'),
        ];
    }

    /**
     * Normalize the manually entered quantity ranges without overwriting the
     * administrator's Qty To values from the product editor.
     */
    private function normalizeQuantityRanges(mixed $submittedRanges): \Illuminate\Support\Collection
    {
        return collect(is_array($submittedRanges) ? $submittedRanges : [])
            ->values()
            ->map(function ($range): array {
                $range = is_array($range) ? $range : [];
                $minimum = filter_var(data_get($range, 'minimum_quantity'), FILTER_VALIDATE_INT);
                $maximumRaw = data_get($range, 'maximum_quantity');
                $maximum = filled($maximumRaw) ? filter_var($maximumRaw, FILTER_VALIDATE_INT) : null;

                return [
                    'minimum_quantity' => $minimum !== false ? $minimum : data_get($range, 'minimum_quantity'),
                    'maximum_quantity' => $maximum === false ? $maximumRaw : $maximum,
                ];
            });
    }

    private function resolveLivePriceColumn(array $headers, array $row, int $highlightColumn): ?int
    {
        if ($highlightColumn > 0 && $this->parseMoney($row[$highlightColumn] ?? null) !== null) {
            return $highlightColumn;
        }

        foreach ($headers as $index => $header) {
            if ($index === 0 || preg_match('/saving|discount|shipping|total|quantity|qty|percent/i', (string) $header)) {
                continue;
            }
            if (preg_match('/unit|price|each|custom|blank/i', (string) $header) && $this->parseMoney($row[$index] ?? null) !== null) {
                return $index;
            }
        }

        foreach ($row as $index => $cell) {
            if ($index > 0 && ! preg_match('/%/', (string) $cell) && $this->parseMoney($cell) !== null) {
                return $index;
            }
        }

        return null;
    }

    private function parseMoney(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^free$/i', $value) === 1) {
            return 0.0;
        }
        if (preg_match('/-?\d[\d,]*(?:\.\d+)?/', $value, $match) !== 1) {
            return null;
        }

        return round((float) str_replace(',', '', $match[0]), 2);
    }

    private function findSavingsLabel(array $headers, array $row): ?string
    {
        foreach ($headers as $index => $header) {
            if (preg_match('/saving|discount/i', (string) $header) === 1 && filled($row[$index] ?? null)) {
                return trim((string) $row[$index]);
            }
        }

        return null;
    }

    public function messages(): array
    {
        return [
            'option_groups.*.code.regex' => 'This customization feature has an invalid internal identifier. Refresh the page and try saving again.',
            'option_groups.*.code.distinct' => 'The same customization feature cannot be added more than once.',
            'option_groups.*.values.*.color_hex.regex' => 'Enter a valid HEX color such as #15345D or 15345D.',
            'option_groups.*.values.*.image_file.image' => 'Each option image must be a valid image file.',
            'option_groups.*.values.*.image_file.mimes' => 'Option images must be JPG, PNG, WebP, or AVIF files.',
            'option_groups.*.values.*.image_file.max' => 'Each option image must not exceed 5 MB.',
            'price_table_headers.0.in' => 'The first storefront price-table column must remain Quantity.',
            'price_tiers.*.unit_price.required_with' => 'Enter a valid unit price in the highlighted price column for every quantity row.',
        ];
    }
}
