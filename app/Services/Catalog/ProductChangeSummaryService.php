<?php

namespace App\Services\Catalog;

use App\Models\Product;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ProductChangeSummaryService
{
    /**
     * Build a stable, human-oriented snapshot of every product section that can
     * be changed from the admin product form.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Product $product): array
    {
        $product->loadMissing([
            'categories',
            'attributeValues.attribute',
            'images',
            'optionGroups.values',
            'sizeGroups.sizes',
            'sizeGroups.masterGroup',
            'priceTiers',
            'fabricPriceTables.tiers',
            'artworkMethods',
            'productionSpeeds.productionMethod',
            'shippingMethods',
            'faqs',
        ]);

        return $this->normalize([
            'Basic details' => $this->attributes($product, [
                'name', 'slug', 'sku', 'status', 'product_type', 'gender_id', 'product_profile', 'brand',
                'badge_label', 'badge_color', 'short_description', 'is_active', 'is_featured',
                'is_customizable', 'sort_order', 'published_at',
            ]),
            'Categories and filters' => [
                'category_id' => $product->category_id,
                'subcategory_id' => $product->subcategory_id,
                'tags' => $product->tags,
                'categories' => $product->categories->map(fn ($category): array => [
                    'id' => $category->id,
                    'is_primary' => (bool) $category->pivot->is_primary,
                    'is_featured' => (bool) $category->pivot->is_featured,
                    'sort_order' => (int) $category->pivot->sort_order,
                ])->values()->all(),
                'attribute_values' => $product->attributeValues->map(fn ($value): array => [
                    'id' => $value->id,
                    'attribute_id' => $value->attribute_id,
                    'sort_order' => (int) ($value->pivot->sort_order ?? 0),
                ])->sortBy('id')->values()->all(),
            ],
            'Pricing' => [
                'fields' => $this->attributes($product, [
                    'base_price', 'compare_at_price', 'cost_price', 'currency',
                    'minimum_quantity', 'maximum_quantity', 'price_table_headers',
                    'price_table_rows', 'price_table_highlight_column', 'price_table_note',
                ]),
                'tiers' => $this->relationRows($product->priceTiers, [
                    'label', 'minimum_quantity', 'maximum_quantity', 'unit_price',
                    'compare_at_price', 'savings_label', 'sort_order',
                ]),
                'fabric_tables' => $product->fabricPriceTables->map(fn ($table): array => [
                    'jersey_customization_option_id' => $table->jersey_customization_option_id,
                    'fabric_key' => $table->fabric_key,
                    'fabric_code' => $table->fabric_code,
                    'fabric_label' => $table->fabric_label,
                    'price_table_headers' => $table->price_table_headers,
                    'price_table_rows' => $table->price_table_rows,
                    'price_table_ranges' => $table->price_table_ranges,
                    'price_table_highlight_column' => $table->price_table_highlight_column,
                    'price_table_note' => $table->price_table_note,
                    'is_active' => (bool) $table->is_active,
                    'sort_order' => (int) $table->sort_order,
                    'tiers' => $this->relationRows($table->tiers, [
                        'label', 'minimum_quantity', 'maximum_quantity', 'unit_price',
                        'compare_at_price', 'savings_label', 'sort_order',
                    ]),
                ])->values()->all(),
            ],
            'Inventory and delivery settings' => $this->attributes($product, [
                'track_inventory', 'stock_quantity', 'low_stock_threshold', 'allow_backorder',
                'weight', 'dimensions', 'shipping_class', 'tax_class',
            ]),
            'Product content' => [
                'fields' => $this->attributes($product, [
                    'description_html', 'detail_information_html', 'customization_artwork_html',
                    'fulfillment_html', 'features', 'specifications',
                ]),
                'faqs' => $this->relationRows($product->faqs, [
                    'question', 'answer', 'is_active', 'sort_order',
                ]),
            ],
            'Images' => $this->relationRows($product->images, [
                'media_library_image_id', 'path', 'url', 'alt_text', 'is_primary', 'sort_order',
            ]),
            'Customization options' => $product->optionGroups->map(fn ($group): array => [
                'name' => $group->name,
                'code' => $group->code,
                'section' => $group->section,
                'type' => $group->type,
                'jersey_customization_type' => $group->jersey_customization_type,
                'display_mode' => $group->display_mode,
                'fixed_value_code' => $group->fixed_value_code,
                'fixed_text_value' => $group->fixed_text_value,
                'show_in_summary' => (bool) $group->show_in_summary,
                'use_as_filter' => (bool) $group->use_as_filter,
                'catalog_attribute_id' => $group->catalog_attribute_id,
                'description' => $group->description,
                'placeholder' => $group->placeholder,
                'is_required' => (bool) $group->is_required,
                'minimum_selections' => $group->minimum_selections,
                'maximum_selections' => $group->maximum_selections,
                'accepted_file_types' => $group->accepted_file_types,
                'maximum_file_size_mb' => $group->maximum_file_size_mb,
                'validation_rules' => $group->validation_rules,
                'is_active' => (bool) $group->is_active,
                'sort_order' => (int) $group->sort_order,
                'values' => $this->relationRows($group->values, [
                    'jersey_customization_option_id', 'world_cup_customization_option_id', 'label', 'code', 'description',
                    'color_hex', 'image_path', 'image_url', 'image_gallery', 'price_adjustment',
                    'charge_type', 'stock_quantity', 'is_default', 'is_active', 'sort_order',
                ]),
            ])->values()->all(),
            'Sizes' => $product->sizeGroups->map(fn ($group): array => [
                'size_option_group_id' => $group->size_option_group_id,
                'name' => $group->name,
                'code' => $group->code,
                'description_html' => $group->description_html,
                'chart_enabled' => (bool) $group->chart_enabled,
                'chart_html' => $group->chart_html,
                'chart_title' => $group->chart_title,
                'chart_note' => $group->chart_note,
                'chart_columns' => $group->chart_columns,
                'chart_rows' => $group->chart_rows,
                'chart_image_path' => $group->chart_image_path,
                'chart_image_url' => $group->chart_image_url,
                'is_active' => (bool) $group->is_active,
                'sort_order' => (int) $group->sort_order,
                'sizes' => $this->relationRows($group->sizes, [
                    'label', 'code', 'price_adjustment', 'sort_order', 'is_active',
                ]),
            ])->values()->all(),
            'Production methods' => [
                'enabled' => (bool) $product->production_methods_enabled,
                'headers' => $product->production_table_headers,
                'rows' => $product->production_table_rows,
                'methods' => $this->relationRows($product->productionSpeeds, [
                    'production_method_id', 'name', 'code', 'description', 'price_adjustment',
                    'minimum_quantity', 'maximum_quantity', 'minimum_days', 'maximum_days',
                    'is_active', 'sort_order',
                ]),
            ],
            'Shipping methods' => [
                'enabled' => (bool) $product->shipping_methods_enabled,
                'methods' => $this->relationRows($product->shippingMethods, [
                    'shipping_method_id', 'name', 'code', 'description', 'price_adjustment',
                    'charge_type', 'charge_application', 'base_price', 'per_item_price',
                    'free_shipping_minimum', 'minimum_days', 'maximum_days',
                    'starts_after_artwork_approval', 'is_quote_based', 'is_default',
                    'is_active', 'sort_order',
                ]),
            ],
            'Roster fields' => $this->attributes($product, [
                'jersey_roster_enabled', 'jersey_roster_optional', 'jersey_roster_title',
                'jersey_roster_fields',
            ]),
            'Artwork upload' => [
                'fields' => $this->attributes($product, [
                    'artwork_upload_enabled', 'artwork_upload_required', 'artwork_upload_title',
                    'artwork_upload_description', 'artwork_upload_max_files',
                    'artwork_upload_max_file_size_mb', 'artwork_upload_accepted_types',
                ]),
                'legacy_methods' => $this->relationRows($product->artworkMethods, [
                    'name', 'code', 'icon', 'description', 'price_adjustment', 'requires_upload',
                    'is_active', 'sort_order',
                ]),
            ],
            'SEO and sharing' => $this->attributes($product, [
                'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
                'og_title', 'og_description', 'og_image_url', 'robots_index',
                'robots_follow', 'schema_json',
            ]),
            'Storefront metrics' => $this->attributes($product, [
                'rating_average', 'reviews_count', 'recent_viewers_count',
                'favorites_count', 'recent_orders_count',
            ]),
        ]);
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function summarize(array $before, array $after): string
    {
        $changedSections = collect(array_unique(array_merge(array_keys($before), array_keys($after))))
            ->filter(fn (string $section): bool => ($before[$section] ?? null) !== ($after[$section] ?? null))
            ->values();

        if ($changedSections->isEmpty()) {
            return 'Saved with no visible field changes';
        }

        if ($changedSections->count() === 1) {
            return $changedSections->first().' updated';
        }

        if ($changedSections->count() <= 3) {
            $last = $changedSections->pop();

            return $changedSections->implode(', ').' and '.$last.' updated';
        }

        $visible = $changedSections->take(3)->implode(', ');
        $remaining = $changedSections->count() - 3;

        return $visible.' and '.$remaining.' more '.($remaining === 1 ? 'section' : 'sections').' updated';
    }

    /**
     * Build concise, human-readable audit details for admin notifications.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<int, string>
     */
    public function changeDetails(array $before, array $after): array
    {
        $details = [];
        $currency = (string) (Arr::get($after, 'Pricing.fields.currency') ?: Arr::get($before, 'Pricing.fields.currency') ?: 'USD');

        $fieldGroups = [
            'Basic details' => [
                'name' => ['Product name'],
                'slug' => ['URL slug'],
                'sku' => ['SKU'],
                'status' => ['Status'],
                'product_type' => ['Product type'],
                'gender_id' => ['Gender', 'reference'],
                'product_profile' => ['Product profile'],
                'brand' => ['Brand'],
                'badge_label' => ['Badge label'],
                'badge_color' => ['Badge color'],
                'short_description' => ['Short description', 'content'],
                'is_active' => ['Active status', 'boolean'],
                'is_featured' => ['Featured status', 'boolean'],
                'is_customizable' => ['Customizable status', 'boolean'],
                'sort_order' => ['Sort order'],
                'published_at' => ['Published date'],
            ],
            'Pricing.fields' => [
                'base_price' => ['Price', 'money'],
                'compare_at_price' => ['Compare-at price', 'money'],
                'cost_price' => ['Cost price', 'money'],
                'currency' => ['Currency'],
                'minimum_quantity' => ['Minimum quantity'],
                'maximum_quantity' => ['Maximum quantity'],
                'price_table_headers' => ['Price table headers', 'content'],
                'price_table_rows' => ['Price table rows', 'content'],
                'price_table_highlight_column' => ['Price table highlight'],
                'price_table_note' => ['Price table note', 'content'],
            ],
            'Inventory and delivery settings' => [
                'track_inventory' => ['Inventory tracking', 'boolean'],
                'stock_quantity' => ['Stock quantity'],
                'low_stock_threshold' => ['Low-stock threshold'],
                'allow_backorder' => ['Backorders', 'boolean'],
                'weight' => ['Weight'],
                'dimensions' => ['Dimensions', 'content'],
                'shipping_class' => ['Shipping class'],
                'tax_class' => ['Tax class'],
            ],
            'Product content.fields' => [
                'description_html' => ['Description', 'content'],
                'detail_information_html' => ['Detail information', 'content'],
                'customization_artwork_html' => ['Customization artwork content', 'content'],
                'fulfillment_html' => ['Fulfillment content', 'content'],
                'features' => ['Features', 'content'],
                'specifications' => ['Specifications', 'content'],
            ],
            'Roster fields' => [
                'jersey_roster_enabled' => ['Roster fields', 'boolean'],
                'jersey_roster_optional' => ['Optional roster', 'boolean'],
                'jersey_roster_title' => ['Roster title'],
                'jersey_roster_fields' => ['Roster field configuration', 'content'],
            ],
            'Artwork upload.fields' => [
                'artwork_upload_enabled' => ['Artwork upload', 'boolean'],
                'artwork_upload_required' => ['Artwork upload required', 'boolean'],
                'artwork_upload_title' => ['Artwork upload title'],
                'artwork_upload_description' => ['Artwork upload description', 'content'],
                'artwork_upload_max_files' => ['Artwork maximum files'],
                'artwork_upload_max_file_size_mb' => ['Artwork maximum file size'],
                'artwork_upload_accepted_types' => ['Artwork accepted file types'],
            ],
            'SEO and sharing' => [
                'meta_title' => ['SEO title'],
                'meta_description' => ['SEO description', 'content'],
                'meta_keywords' => ['SEO keywords', 'content'],
                'canonical_url' => ['Canonical URL'],
                'og_title' => ['Social title'],
                'og_description' => ['Social description', 'content'],
                'og_image_url' => ['Social image'],
                'robots_index' => ['Search indexing', 'boolean'],
                'robots_follow' => ['Search link following', 'boolean'],
                'schema_json' => ['Schema data', 'content'],
            ],
            'Storefront metrics' => [
                'rating_average' => ['Rating average'],
                'reviews_count' => ['Reviews count'],
                'recent_viewers_count' => ['Recent viewers count'],
                'favorites_count' => ['Favorites count'],
                'recent_orders_count' => ['Recent orders count'],
            ],
        ];

        foreach ($fieldGroups as $basePath => $fields) {
            foreach ($fields as $field => $config) {
                $label = $config[0];
                $type = $config[1] ?? null;
                $path = $basePath.'.'.$field;
                $oldValue = Arr::get($before, $path);
                $newValue = Arr::get($after, $path);

                if ($oldValue === $newValue) {
                    continue;
                }

                $details[] = $this->formatChangeDetail($label, $oldValue, $newValue, $type ?? null, $currency);
            }
        }

        $sectionDetails = [
            'Categories and filters' => 'Updated categories or catalog filters',
            'Pricing.tiers' => 'Updated price tiers',
            'Pricing.fabric_tables' => 'Updated fabric price tables',
            'Product content.faqs' => 'Updated product FAQs',
            'Images' => 'Updated product images',
            'Customization options' => 'Updated customization options',
            'Sizes' => 'Updated size options or size chart',
            'Production methods' => 'Updated production methods',
            'Shipping methods' => 'Updated shipping methods',
            'Artwork upload.legacy_methods' => 'Updated artwork methods',
        ];

        foreach ($sectionDetails as $path => $message) {
            if (Arr::get($before, $path) !== Arr::get($after, $path)) {
                $details[] = $message;
            }
        }

        return collect($details)->filter()->unique()->take(12)->values()->all();
    }

    /** @param array<int, string> $fields */
    private function attributes(Product $product, array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (string $field): array => [$field => $product->getAttribute($field)])
            ->all();
    }

    /**
     * @param iterable<int, Model> $models
     * @param array<int, string> $fields
     * @return array<int, array<string, mixed>>
     */
    private function relationRows(iterable $models, array $fields): array
    {
        return collect($models)
            ->map(fn (Model $model): array => Arr::only($model->getAttributes(), $fields))
            ->values()
            ->all();
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Model) {
            return $this->normalize($value->getAttributes());
        }

        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }

    private function formatChangeDetail(string $label, mixed $oldValue, mixed $newValue, ?string $type, string $currency): string
    {
        if (in_array($type, ['content', 'reference'], true) || is_array($oldValue) || is_array($newValue)) {
            return 'Updated '.strtolower($label);
        }

        $old = $this->displayValue($oldValue, $type, $currency);
        $new = $this->displayValue($newValue, $type, $currency);

        if (mb_strlen($old) > 70 || mb_strlen($new) > 70) {
            return 'Updated '.strtolower($label);
        }

        return 'Updated '.strtolower($label).' from '.$old.' to '.$new;
    }

    private function displayValue(mixed $value, ?string $type, string $currency): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        if ($type === 'boolean') {
            return (bool) $value ? 'Yes' : 'No';
        }

        if ($type === 'money' && is_numeric($value)) {
            return trim($currency).' '.number_format((float) $value, 2, '.', ',');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return trim((string) $value) !== '' ? trim((string) $value) : 'Not set';
    }
}
