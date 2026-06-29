<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCatalogService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $hydratedProducts = null;

    public function all(): array
    {
        if ($this->hydratedProducts !== null) {
            return $this->hydratedProducts;
        }

        if (Schema::hasTable('products') && Product::query()->published()->exists()) {
            return $this->hydratedProducts = Product::query()
                ->published()
                ->with(['category', 'subcategory', 'categories', 'attributeValues.attribute', 'images', 'optionGroups.values', 'sizeGroups.sizes', 'priceTiers', 'artworkMethods', 'productionSpeeds', 'shippingMethods', 'faqs'])
                ->orderBy('sort_order')
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (Product $product): array => $this->fromModel($product))
                ->values()
                ->all();
        }

        return $this->hydratedProducts = collect($this->products())
            ->map(fn (array $product): array => $this->hydrateProduct($product))
            ->values()
            ->all();
    }

    public function search(?string $query = null): array
    {
        $products = collect($this->all());

        if (filled($query)) {
            $needle = Str::lower((string) $query);

            $products = $products->filter(function (array $product) use ($needle): bool {
                return Str::contains(Str::lower($product['title']), $needle)
                    || Str::contains(Str::lower($product['category']), $needle)
                    || Str::contains(Str::lower(implode(' ', $product['tags'])), $needle)
                    || Str::contains(Str::lower($product['sport']), $needle)
                    || Str::contains(Str::lower($product['sku']), $needle);
            });
        }

        return $products->values()->all();
    }

    public function findBySlug(string $slug): ?array
    {
        if (Schema::hasTable('products')) {
            $query = Product::query()->with(['category', 'subcategory', 'categories', 'attributeValues.attribute', 'images', 'optionGroups.values', 'sizeGroups.sizes', 'priceTiers', 'artworkMethods', 'productionSpeeds', 'shippingMethods', 'faqs']);
            $isAdminPreview = auth('admin')->check() || (auth()->user()?->isAdmin() ?? false);

            if (! $isAdminPreview) {
                $query->published();
            }

            if ($product = $query->where('slug', $slug)->first()) {
                return $this->fromModel($product);
            }
        }

        return collect($this->all())->firstWhere('slug', $slug);
    }

    public function relatedFor(array $product, int $limit = 4): array
    {
        return collect($this->all())
            ->reject(fn (array $item): bool => $item['slug'] === $product['slug'])
            ->sortByDesc(function (array $item) use ($product): int {
                $score = 0;

                if ($item['sport'] === $product['sport']) {
                    $score += 3;
                }

                if ($item['category'] === $product['category']) {
                    $score += 2;
                }

                if (! empty(array_intersect($item['tags'], $product['tags']))) {
                    $score += 1;
                }

                return $score;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function featured(): array
    {
        $featured = collect($this->all())->where('is_featured', true)->take(8)->values();

        return ($featured->isNotEmpty() ? $featured : collect($this->all())->take(8))->values()->all();
    }


    private function normalizeColorHex(?string $color): ?string
    {
        $hex = strtoupper(ltrim(trim((string) $color), '#'));

        if (preg_match('/^[0-9A-F]{3}$/', $hex) === 1) {
            $hex = implode('', array_map(
                static fn (string $character): string => $character.$character,
                str_split($hex)
            ));
        }

        return preg_match('/^[0-9A-F]{6}$/', $hex) === 1 ? '#'.$hex : null;
    }

    private function contrastColor(?string $color): string
    {
        $hex = $this->normalizeColorHex($color);

        if ($hex === null) {
            return '#0F172A';
        }

        $red = hexdec(substr($hex, 1, 2));
        $green = hexdec(substr($hex, 3, 2));
        $blue = hexdec(substr($hex, 5, 2));
        $luminance = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $luminance > 160 ? '#0F172A' : '#FFFFFF';
    }

    public function fromModel(Product $product): array
    {
        $gallery = $product->images->map(fn ($image) => [
            'url' => $image->publicUrl(),
            'alt' => $image->alt_text ?: $product->name,
        ])->values()->all();

        if ($gallery === []) {
            $gallery[] = ['url' => asset('images/product-placeholder.svg'), 'alt' => $product->name];
        }

        $optionGroups = $product->optionGroups
            ->where('is_active', true)
            ->filter(fn ($group) => ($group->display_mode ?: 'customer') !== 'hidden')
            ->map(fn ($group) => [
                'id' => $group->code,
                'label' => $group->name,
                'description' => $group->description,
                'placeholder' => $group->placeholder,
                'section' => $group->section,
                'type' => $group->type,
                'display_mode' => $group->display_mode ?: 'customer',
                'fixed_value_code' => $group->fixed_value_code,
                'fixed_text_value' => $group->fixed_text_value,
                'show_in_summary' => $group->show_in_summary,
                'required' => $group->is_required,
                'minimum_selections' => $group->minimum_selections,
                'maximum_selections' => $group->maximum_selections,
                'accepted_file_types' => $group->accepted_file_types,
                'maximum_file_size_mb' => $group->maximum_file_size_mb,
                'values' => $group->values->where('is_active', true)->map(fn ($value) => [
                    'id' => $value->code,
                    'label' => $value->label,
                    'description' => $value->description,
                    'color' => $this->normalizeColorHex($value->color_hex),
                    'contrast' => $this->contrastColor($value->color_hex),
                    'image' => $value->publicImageUrl(),
                    'images' => $value->publicImages(),
                    'price_delta' => (float) $value->price_adjustment,
                    'charge_type' => $value->charge_type ?: 'per_unit',
                    'stock_quantity' => $value->stock_quantity,
                    'default' => $value->is_default,
                ])->values()->all(),
            ])->values()->all();

        $priceTiers = $product->priceTiers->map(fn ($tier) => [
            'label' => $tier->label ?: $tier->minimum_quantity . ($tier->maximum_quantity ? '–'.$tier->maximum_quantity : '+'),
            'min' => $tier->minimum_quantity,
            'max' => $tier->maximum_quantity,
            'unit' => (float) $tier->unit_price,
            'compare_at' => $tier->compare_at_price ? (float) $tier->compare_at_price : null,
            'savings_label' => $tier->savings_label,
        ])->values()->all();

        if ($priceTiers === []) {
            $priceTiers[] = ['label' => $product->minimum_quantity.'+', 'min' => $product->minimum_quantity, 'max' => null, 'unit' => (float) $product->base_price, 'compare_at' => null, 'savings_label' => null];
        }

        $visiblePriceRows = collect($product->price_table_rows ?: collect($priceTiers)->map(fn ($tier) => [
            (string) $tier['min'],
            '$'.number_format($tier['unit'], 2),
            $tier['savings_label'] ?: '—',
        ])->all())->map(function ($row, int $index) use ($priceTiers): array {
            $row = array_values((array) $row);
            if (isset($priceTiers[$index]['min'])) {
                $row[0] = (string) $priceTiers[$index]['min'];
            }

            return $row;
        })->values()->all();

        $primaryCategory = $product->relationLoaded('categories')
            ? ($product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first())
            : null;
        $primaryCategory ??= $product->subcategory ?: $product->category;

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'title' => $product->name,
            'short_title' => $product->name,
            'summary' => $product->short_description ?: strip_tags((string) $product->description_html),
            'description' => strip_tags((string) $product->description_html),
            'description_html' => $product->description_html,
            'detail_information_html' => $product->detail_information_html,
            'price' => 'From $'.number_format((float) $priceTiers[0]['unit'], 2),
            'base_price' => (float) $product->base_price,
            'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
            'currency' => $product->currency,
            'minimum_quantity' => $product->minimum_quantity,
            'maximum_quantity' => $product->maximum_quantity,
            'tag' => $product->badge_label ?: ($product->is_customizable ? 'Customizable' : null),
            'tag_color' => $product->badge_color ?: 'red',
            'sport' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category' => $primaryCategory?->name ?: 'Custom Sportswear',
            'category_slug' => $primaryCategory?->slug,
            'subcategory' => $product->subcategory?->name,
            'subcategory_slug' => $product->subcategory?->slug,
            'categories' => $product->relationLoaded('categories') ? $product->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug, 'primary' => (bool) $category->pivot->is_primary])->values()->all() : [],
            'attributes' => $product->relationLoaded('attributeValues') ? $product->attributeValues->groupBy('attribute.slug')->map(fn ($values) => $values->pluck('label')->values()->all())->all() : [],
            'sku' => $product->sku,
            'rating' => 0,
            'reviews_count' => 0,
            'image' => $gallery[0]['url'],
            'alt' => $gallery[0]['alt'],
            'gallery' => $gallery,
            'tags' => $product->tags ?? [],
            'features' => $product->features ?? [],
            'detail_information' => $product->specifications ?? [],
            'details' => $product->specifications ?? [],
            'brand' => $product->brand ?: config('storefront.name'),
            'product_type' => $product->product_type,
            'product_profile' => $product->product_profile ?: 'standard',
            'jersey_roster' => [
                'enabled' => (bool) $product->jersey_roster_enabled && ($product->product_profile === 'jersey'),
                'optional' => (bool) $product->jersey_roster_optional,
                'title' => $product->jersey_roster_title ?: 'Add player names and numbers',
                'fields' => collect($product->jersey_roster_fields ?? [])->filter(fn ($field) => (bool) ($field['enabled'] ?? true))->values()->all(),
            ],
            'is_featured' => $product->is_featured,
            'is_customizable' => $product->is_customizable,
            'track_inventory' => $product->track_inventory,
            'stock_quantity' => $product->stock_quantity,
            'allow_backorder' => $product->allow_backorder,
            'option_groups' => $optionGroups,
            'size_groups' => $product->sizeGroups->where('is_active', true)->map(fn ($group) => [
                'id' => $group->code,
                'label' => $group->name,
                'description_html' => $group->description_html,
                'sizes' => $group->sizes->where('is_active', true)->map(fn ($size) => [
                    'code' => $size->code,
                    'label' => $size->label,
                    // Sizes select quantity only. Total quantity chooses the price tier.
                    'price_delta' => 0.0,
                ])->values()->all(),
                'chart' => [
                    'enabled' => (bool) $group->chart_enabled && (filled($group->chart_html) || filled($group->chartImageUrl()) || (! empty($group->chart_columns) && ! empty($group->chart_rows))),
                    'html' => $group->chart_html,
                    'title' => $group->chart_title ?: $group->name.' Size Chart',
                    'note' => $group->chart_note,
                    'columns' => $group->chart_columns ?? [],
                    'rows' => $group->chart_rows ?? [],
                    'image' => $group->chartImageUrl(),
                ],
            ])->values()->all(),
            'artwork_upload' => [
                'enabled' => (bool) $product->artwork_upload_enabled,
                'required' => (bool) $product->artwork_upload_required,
                'title' => $product->artwork_upload_title ?: 'Upload Custom Artwork',
                'description' => $product->artwork_upload_description ?: 'Upload one or more artwork files for the production team.',
                'max_files' => max(1, min(12, (int) ($product->artwork_upload_max_files ?: 5))),
                'max_file_size_mb' => max(1, min(25, (int) ($product->artwork_upload_max_file_size_mb ?: 15))),
                'accepted_types' => collect(explode(',', (string) ($product->artwork_upload_accepted_types ?: 'pdf,svg,png,jpg,jpeg,webp')))
                    ->map(fn ($type) => Str::lower(ltrim(trim((string) $type), '.')))
                    ->filter()->unique()->values()->all(),
            ],
            'artwork_methods' => [],
            'production_speeds' => $product->productionSpeeds->where('is_active', true)->map(fn ($speed) => [
                'id' => $speed->code,
                'label' => $speed->name,
                'description' => $speed->description,
                'price_delta' => (float) $speed->price_adjustment,
                'minimum_quantity' => (int) ($speed->minimum_quantity ?: 1),
                'maximum_quantity' => $speed->maximum_quantity === null ? null : (int) $speed->maximum_quantity,
                'minimum_days' => $speed->minimum_days,
                'maximum_days' => $speed->maximum_days,
            ])->values()->all(),
            'shipping_methods_enabled' => (bool) $product->shipping_methods_enabled,
            'shipping_methods' => $product->shipping_methods_enabled ? $product->shippingMethods->where('is_active', true)->map(fn ($method) => [
                'id' => $method->code,
                'label' => $method->name,
                'description' => $method->description,
                'price_delta' => (float) $method->price_adjustment,
                'charge_type' => $method->charge_type ?: 'per_unit',
                'minimum_days' => $method->minimum_days,
                'maximum_days' => $method->maximum_days,
                'default' => (bool) $method->is_default,
            ])->values()->all() : [],
            'price_tiers' => $priceTiers,
            'price_table' => [
                'headers' => $product->price_table_headers ?: ['Quantity', 'Unit Price', 'Savings'],
                'rows' => $visiblePriceRows,
                'highlight_column' => $product->price_table_highlight_column,
                'note' => $product->price_table_note,
            ],
            'faqs' => $product->faqs->where('is_active', true)->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer])->values()->all(),
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'canonical_url' => $product->canonical_url,
            'og_title' => $product->og_title,
            'og_description' => $product->og_description,
            'og_image' => $product->og_image_url ?: $gallery[0]['url'],
            'robots' => ($product->robots_index ? 'index' : 'noindex').', '.($product->robots_follow ? 'follow' : 'nofollow'),
            'custom_schema' => $product->schema_json,
            'url' => route('products.show', $product->slug),
        ];
    }

    private function hydrateProduct(array $product): array
    {
        $product['url'] = route('products.show', $product['slug']);
        $product['gallery'] = $product['gallery'] ?? [$product['image']];
        $product['customizable_options'] = $product['customizable_options'] ?? [$this->defaultDesignOption($product)];
        $product['size_quantity_groups'] = $product['size_quantity_groups'] ?? $this->defaultSizeQuantityGroups();
        $product['size_selector'] = $product['size_selector'] ?? $this->defaultSizeSelector($product);
        $product['size_chart'] = $product['size_chart'] ?? $this->defaultSizeChart($product);
        $product['price_tiers'] = $product['price_tiers'] ?? $this->defaultPriceTiers((float) ($product['base_price'] ?? 39));
        $product['detail_information'] = $product['detail_information'] ?? $this->defaultDetailInformation($product);
        $product['details'] = $product['details'] ?? $this->legacyDetails($product);
        $product['option_steps'] = $product['option_steps'] ?? $this->defaultOptionSteps();
        $product['faqs'] = $product['faqs'] ?? $this->defaultFaqs();
        $product['is_featured'] = $product['is_featured'] ?? false;
        $product['is_customizable'] = $product['is_customizable'] ?? true;
        $product['currency'] = $product['currency'] ?? 'USD';
        $product['minimum_quantity'] = $product['minimum_quantity'] ?? 1;
        $product['maximum_quantity'] = $product['maximum_quantity'] ?? null;
        $product['description_html'] = $product['description_html'] ?? '<p>'.e($product['description']).'</p>';
        $product['option_groups'] = $product['option_groups'] ?? [];
        $product['size_groups'] = $product['size_groups'] ?? collect($product['size_quantity_groups'])->map(fn ($group) => [
            'id' => $group['key'],
            'label' => $group['label'],
            'sizes' => collect($group['sizes'])->map(fn ($size) => ['code' => Str::slug($size), 'label' => $size, 'price_delta' => 0])->all(),
        ])->all();
        $product['artwork_upload'] = $product['artwork_upload'] ?? [
            'enabled' => true,
            'required' => false,
            'title' => 'Upload Custom Artwork',
            'description' => 'Upload one or more artwork files for the production team.',
            'max_files' => 5,
            'max_file_size_mb' => 15,
            'accepted_types' => ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'],
        ];
        $product['artwork_methods'] = [];
        $product['shipping_methods'] = $product['shipping_methods'] ?? [];
        $product['shipping_methods_enabled'] = $product['shipping_methods_enabled'] ?? false;
        $product['product_profile'] = $product['product_profile'] ?? 'standard';
        $product['jersey_roster'] = $product['jersey_roster'] ?? ['enabled' => false, 'optional' => true, 'title' => 'Add player names and numbers', 'fields' => []];
        $product['production_speeds'] = $product['production_speeds'] ?? [
            ['id' => 'standard', 'label' => 'Standard Production', 'description' => 'Standard schedule', 'price_delta' => 0, 'minimum_quantity' => 1, 'maximum_quantity' => null, 'minimum_days' => 14, 'maximum_days' => 18],
        ];
        $product['price_table'] = $product['price_table'] ?? [
            'headers' => ['Quantity', 'Product Price', 'Shipping', 'Estimated Each', 'Order Total'],
            'rows' => collect($product['price_tiers'])->map(fn ($tier) => [$tier['quantity'], $tier['product_price'], $tier['shipping'], $tier['estimated_each'], $tier['estimated_order_total']])->all(),
            'highlight_column' => 3,
            'note' => 'Final pricing is confirmed after customization and artwork review.',
        ];
        $product['robots'] = $product['robots'] ?? 'index, follow';
        $product['meta_title'] = $product['meta_title'] ?? null;
        $product['meta_description'] = $product['meta_description'] ?? null;
        $product['canonical_url'] = $product['canonical_url'] ?? $product['url'];
        $product['og_title'] = $product['og_title'] ?? null;
        $product['og_description'] = $product['og_description'] ?? null;
        $product['og_image'] = $product['og_image'] ?? $product['image'];
        $product['gallery'] = collect($product['gallery'])->map(fn ($image) => is_array($image) ? $image : ['url' => $image, 'alt' => $product['alt']])->all();

        return $product;
    }

    private function products(): array
    {
        return [
            [
                'slug' => 'custom-cool-shapes-adult-youth-unisex-football-jersey',
                'title' => 'Custom Cool Shapes Adult Youth Unisex Football Jersey',
                'short_title' => 'Cool Shapes Football Jersey',
                'summary' => 'A fully customizable football jersey for adult and youth teams with design proof support, names, numbers, logos, and team colors.',
                'description' => 'Build a football jersey from a design template, then customize colors, logo placement, player names, roster numbers, fit notes, and artwork instructions before production.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-AYU-001',
                'rating' => 5,
                'reviews_count' => 34,
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom football jersey with name and number',
                'gallery' => [
                    'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=900&q=80',
                ],
                'tags' => ['football jersey', 'cool shapes', 'custom football uniform', 'adult youth unisex', 'sublimation football jersey', 'team football uniform'],
                'features' => [
                    'Adult, women, youth, and toddler quantity entry',
                    'Name, number, logo, and roster options',
                    'Digital proof before production',
                    'Moisture-wicking polyester performance fabric',
                    'No minimum quantity for selected custom products',
                ],
                'detail_information' => [
                    'SKU' => 'NPS-FBL-JER-AYU-001',
                    'Product Type' => 'Football Jersey',
                    'Fabric' => '220gsm Pro-Wick Polyester Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'V-Neck / Football Collar',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Football',
            ],
            [
                'slug' => 'custom-football-jersey-name-number',
                'title' => 'Custom Football Jersey with Name & Number',
                'short_title' => 'Football Jersey',
                'summary' => 'Personalized football jersey with player name, number, team colors, and optional logo placement.',
                'description' => 'A clean football jersey product for teams, fans, school events, and player uniforms.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-NN-002',
                'rating' => 5,
                'reviews_count' => 28,
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom football jersey',
                'tags' => ['football jersey', 'custom name number', 'team jersey', 'sportswear'],
                'features' => ['Player name and number', 'Team color direction', 'Logo placement notes', 'Team roster support'],
                'brand' => 'Football',
            ],
            [
                'slug' => 'baseball-uniform-set-for-teams',
                'title' => 'Baseball Uniform Set for Teams',
                'short_title' => 'Baseball Uniform Set',
                'summary' => 'Coordinated baseball jersey and uniform set for school, club, and league team orders.',
                'description' => 'Team-ready baseball uniform set with quote-based sizing, roster, and design review.',
                'price' => 'Request Quote',
                'base_price' => 49,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Baseball',
                'category' => 'Baseball Uniforms',
                'sku' => 'NPS-BSB-UNI-SET-003',
                'rating' => 5,
                'reviews_count' => 21,
                'image' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Baseball uniform set',
                'tags' => ['baseball uniform', 'team set', 'jersey pants', 'club uniform'],
                'features' => ['Team roster sizing', 'Jersey and pants direction', 'Logo and number support', 'Bulk quote ready'],
                'brand' => 'Baseball',
            ],
            [
                'slug' => 'custom-basketball-jersey',
                'title' => 'Custom Basketball Jersey',
                'short_title' => 'Basketball Jersey',
                'summary' => 'Breathable basketball jersey with team color, name, number, and logo customization.',
                'description' => 'A lightweight basketball jersey suitable for clubs, tournaments, training, and fan gear.',
                'price' => 'From $34',
                'base_price' => 34,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Basketball',
                'category' => 'Basketball Jerseys',
                'sku' => 'NPS-BSK-JER-UR-001',
                'rating' => 5,
                'reviews_count' => 17,
                'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom basketball jersey',
                'tags' => ['basketball club uniform', 'birdseye mesh jersey', 'breathable basketball jersey', 'Custom Basketball Jersey', 'custom sportswear', 'custom team jersey', 'elite basketball jersey', 'round neck basketball jersey', 'sublimation basketball jersey', 'team basketball uniform', 'unisex basketball jersey', 'youth basketball jersey'],
                'features' => ['Sleeveless jersey style', 'Name and number support', 'Team color direction', 'Bulk team order ready'],
                'detail_information' => [
                    'SKU' => 'NPS-BSK-JER-UR-001',
                    'Product Type' => 'Basketball Jersey',
                    'Fabric' => '160gsm Birdseye Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'Round Neck',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Basketball',
                'size_chart' => [
                    'title' => 'Basketball Jersey Size Chart',
                    'note' => 'Measurements are garment guidance. Choose one size up for a looser game fit.',
                    'groups' => [
                        [
                            'label' => 'Adult Unisex',
                            'columns' => ['Size', 'Chest', 'Length', 'Shoulder'],
                            'rows' => [
                                ['XS', '32-34"', '27"', '15.5"'],
                                ['S', '34-37"', '28"', '16.5"'],
                                ['M', '37-40"', '29"', '17.5"'],
                                ['L', '40-43"', '30"', '18.5"'],
                                ['XL', '43-46"', '31"', '19.5"'],
                                ['2XL', '46-49"', '32"', '20.5"'],
                                ['3XL', '49-52"', '33"', '21.5"'],
                                ['4XL', '52-55"', '34"', '22.5"'],
                            ],
                        ],
                        [
                            'label' => 'Youth Unisex',
                            'columns' => ['Size', 'Age', 'Chest', 'Length'],
                            'rows' => [
                                ['YXS', '4-5', '23-24"', '20"'],
                                ['YS', '6-7', '25-26"', '21"'],
                                ['YM', '8-9', '27-28"', '23"'],
                                ['YL', '10-11', '29-31"', '25"'],
                                ['YXL', '12-14', '32-34"', '27"'],
                            ],
                        ],
                    ],
                    'tips' => ['For team orders, collect sizes before submitting the roster.', 'Custom sizing can be requested in the notes field.'],
                ],
            ],
            [
                'slug' => 'sublimated-soccer-kit-for-teams',
                'title' => 'Sublimated Soccer Kit',
                'short_title' => 'Soccer Kit',
                'summary' => 'Custom soccer kit for clubs, school teams, and fan groups with jersey and team details.',
                'description' => 'Soccer kit page with custom logo, player list, and quote-oriented production support.',
                'price' => 'Request Quote',
                'base_price' => 42,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Soccer',
                'category' => 'Soccer Kits',
                'sku' => 'NPS-SCR-KIT-TM-005',
                'rating' => 5,
                'reviews_count' => 24,
                'image' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Sublimated soccer kit',
                'tags' => ['soccer kit', 'uniform', 'club team', 'sublimated'],
                'features' => ['Club and school team ready', 'Logo and sponsor notes', 'Player roster support', 'Bulk sizing table support'],
                'brand' => 'Soccer',
            ],
            [
                'slug' => 'custom-team-hoodie',
                'title' => 'Custom Team Hoodie',
                'short_title' => 'Team Hoodie',
                'summary' => 'Team hoodie for travel, sideline, spirit wear, coaches, staff, and fan apparel.',
                'description' => 'Custom hoodie with logo, embroidery/print method, and bulk ordering support.',
                'price' => 'From $45',
                'base_price' => 45,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Training',
                'category' => 'Apparel',
                'sku' => 'NPS-HOD-APP-TM-006',
                'rating' => 5,
                'reviews_count' => 18,
                'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom team hoodie',
                'tags' => ['hoodie', 'apparel', 'team', 'spirit wear'],
                'features' => ['Team logo support', 'Front/back print notes', 'Bulk apparel pricing', 'Coach and fan gear ready'],
                'brand' => 'Team Apparel',
            ],
            [
                'slug' => 'custom-embroidered-cap',
                'title' => 'Custom Embroidered Cap',
                'short_title' => 'Embroidered Cap',
                'summary' => 'Custom cap with embroidered logo direction for teams, businesses, events, and fan shops.',
                'description' => 'Cap customization with logo file upload, embroidery position notes, and bulk pricing support.',
                'price' => 'From $18',
                'base_price' => 18,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Accessories',
                'category' => 'Caps & Hats',
                'sku' => 'NPS-CAP-EMB-007',
                'rating' => 5,
                'reviews_count' => 14,
                'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom embroidered cap',
                'tags' => ['cap', 'hat', 'embroidery', 'team logo'],
                'features' => ['Front logo placement', 'Embroidery quote support', 'Event and team order ready', 'Bulk available'],
                'brand' => 'Accessories',
            ],
            [
                'slug' => 'personalized-sports-duffel-bag',
                'title' => 'Personalized Sports Duffel Bag',
                'short_title' => 'Sports Duffel Bag',
                'summary' => 'Personalized sports bag for team travel, gym use, events, and promotional programs.',
                'description' => 'Duffel bag product page with logo, name, and bulk promotional order notes.',
                'price' => 'From $32',
                'base_price' => 32,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Accessories',
                'category' => 'Bags',
                'sku' => 'NPS-BAG-DUF-008',
                'rating' => 5,
                'reviews_count' => 12,
                'image' => 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Personalized sports duffel bag',
                'tags' => ['bag', 'duffel', 'team', 'travel'],
                'features' => ['Team logo direction', 'Player name notes', 'Travel-friendly product', 'Promotional order support'],
                'brand' => 'Bags',
            ],
            [
                'slug' => 'custom-fan-jersey',
                'title' => 'Custom Fan Jersey',
                'short_title' => 'Fan Jersey',
                'summary' => 'Personalized fan jersey for supporters, school events, tournaments, and spirit wear.',
                'description' => 'Custom fan jersey with name, number, color, and event-specific design support.',
                'price' => 'From $36',
                'base_price' => 36,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Fan Gear',
                'category' => 'Custom Jerseys',
                'sku' => 'NPS-FAN-JER-009',
                'rating' => 5,
                'reviews_count' => 16,
                'image' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom fan jersey',
                'tags' => ['fan jersey', 'customizable', 'spirit wear', 'event'],
                'features' => ['Fan name and number', 'Event color direction', 'Spirit wear ready', 'Small and bulk order support'],
                'brand' => 'Fan Gear',
            ],
        ];
    }

    private function defaultDesignOption(array $product = []): array
    {
        return [
            'id' => 'design-style',
            'step' => '01',
            'title' => 'Choose Design Style',
            'subtitle' => 'Pick a starting point',
            'description' => 'One admin-defined customizable option is shown now. The same reusable component can render more options later.',
            'type' => 'visual-radio',
            'required' => true,
            'help' => 'Final colors, logos, names, numbers, and artwork can be adjusted after proof review.',
            'values' => [
                [
                    'label' => 'Default Team Style',
                    'value' => 'default-team-style',
                    'price_delta' => '$0',
                    'badge' => 'Default',
                    'preview' => 'linear-gradient(135deg,#15345d 0%,#2467b7 50%,#e91d33 50%,#e91d33 100%)',
                    'description' => 'Clean team look using the product default template.',
                ],
                [
                    'label' => 'Modern Graphic',
                    'value' => 'modern-graphic',
                    'price_delta' => '+$3',
                    'badge' => 'Popular',
                    'preview' => 'radial-gradient(circle at 30% 30%,#e91d33,#2467b7 45%,#0d2545 100%)',
                    'description' => 'Bolder modern design with high-impact color movement.',
                ],
                [
                    'label' => 'Upload Custom Artwork',
                    'value' => 'upload-custom-artwork',
                    'price_delta' => 'Quote',
                    'badge' => 'Custom',
                    'preview' => 'linear-gradient(45deg,#f8fafc 25%,#e2e8f0 25%,#e2e8f0 50%,#f8fafc 50%,#f8fafc 75%,#e2e8f0 75%)',
                    'description' => 'Upload your own artwork or ask the design team to recreate it.',
                ],
            ],
        ];
    }

    private function defaultSizeSelector(array $product): array
    {
        return [
            'step' => '07',
            'title' => 'Choose Your Sizes',
            'note' => $product['sport'] === 'Football'
                ? 'We suggest going up one size if wearing on top of shoulder or elbow pads.'
                : 'Choose the best fit for each player. Add custom size notes if needed.',
            'base_price' => (float) ($product['base_price'] ?? 39),
        ];
    }

    private function defaultSizeQuantityGroups(): array
    {
        return [
            [
                'key' => 'men',
                'label' => 'Men',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', 'CUSTOM'],
            ],
            [
                'key' => 'women',
                'label' => 'Women',
                'sizes' => ['WS', 'WM', 'WL', 'WXL', 'W2XL', 'W3XL', 'W4XL', 'CUSTOM'],
            ],
            [
                'key' => 'youth',
                'label' => 'Youth',
                'sizes' => ['YXS', 'YS', 'YM', 'YL', 'YXL', 'CUSTOM'],
            ],
            [
                'key' => 'toddler',
                'label' => 'Toddler',
                'sizes' => ['2T', '3T', '4T', 'CUSTOM'],
            ],
        ];
    }

    private function defaultOptionSteps(): array
    {
        return [
            ['title' => 'Choose Apparel', 'description' => 'Select the jersey, uniform set, hoodie, cap, bag, or accessory variation.'],
            ['title' => 'Choose Design', 'description' => 'Pick a template, modern graphic, or custom artwork direction.'],
            ['title' => 'Upload Artwork', 'description' => 'Attach logo, roster, design sample, or old order reference.'],
            ['title' => 'Choose Sizes', 'description' => 'Enter size quantities for men, women, youth, toddler, or custom sizes.'],
            ['title' => 'Review Proof', 'description' => 'Approve spelling, artwork placement, colors, and final layout before production.'],
            ['title' => 'Confirm Order', 'description' => 'Production and delivery timeline is confirmed after proof approval.'],
        ];
    }

    private function defaultDetailInformation(array $product): array
    {
        $sport = $product['sport'] ?? 'Sportswear';
        $type = Str::contains(Str::lower($product['title'] ?? ''), 'hoodie') ? 'Team Hoodie' : ($sport . ' Product');

        if (Str::contains(Str::lower($product['title'] ?? ''), 'jersey')) {
            $type = $sport . ' Jersey';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'kit')) {
            $type = $sport . ' Kit';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'cap')) {
            $type = 'Embroidered Cap';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'bag')) {
            $type = 'Duffel Bag';
        }

        return [
            'Product Type' => $type,
            'Fabric' => $this->defaultFabricFor($product),
            'Collection Tier' => 'Elite',
            'Neckline' => Str::contains(Str::lower($type), 'hoodie') ? 'Hooded' : 'Sport-specific neckline',
            'Customization' => 'Full Sublimation / Print / Embroidery Quote',
            'MOQ' => '1 Piece',
            'Lead Time' => '18–22 business days + shipping',
        ];
    }

    private function legacyDetails(array $product): array
    {
        return [
            'Category' => $product['category'] ?? 'Custom Sportswear',
            'Order Type' => 'Custom / bulk / quote-ready',
            'Proof' => 'Digital proof before production',
            'Artwork' => 'PNG, JPG, PDF, AI, or design notes',
            'Production' => 'Timeline confirmed after proof approval',
        ];
    }

    private function defaultFabricFor(array $product): string
    {
        $sport = Str::lower($product['sport'] ?? '');
        $title = Str::lower($product['title'] ?? '');

        return match (true) {
            Str::contains($sport, 'basketball') => '160gsm Birdseye Mesh',
            Str::contains($sport, 'football') => '220gsm Pro-Wick Polyester Mesh',
            Str::contains($sport, 'soccer') => '180gsm Dry-Fit Polyester',
            Str::contains($title, 'hoodie') => 'Fleece Cotton-Poly Blend',
            Str::contains($title, 'cap') => 'Structured Cotton Twill',
            Str::contains($title, 'bag') => 'Heavy-Duty Polyester',
            default => 'Performance Polyester',
        };
    }

    private function defaultPriceTiers(float $basePrice): array
    {
        $tiers = [
            ['qty' => '1-5', 'discount' => 0, 'shipping' => 8, 'note' => 'Small custom order'],
            ['qty' => '6-24', 'discount' => 0.08, 'shipping' => 6, 'note' => 'Small team order'],
            ['qty' => '25-99', 'discount' => 0.15, 'shipping' => 5, 'note' => 'Team pricing'],
            ['qty' => '100+', 'discount' => 0.22, 'shipping' => 4, 'note' => 'Bulk quote recommended'],
        ];

        return array_map(function (array $tier) use ($basePrice): array {
            $productPrice = round($basePrice * (1 - $tier['discount']), 2);

            return [
                'quantity' => $tier['qty'],
                'product_price' => '$' . number_format($productPrice, 2),
                'shipping' => '$' . number_format($tier['shipping'], 2),
                'estimated_each' => '$' . number_format($productPrice + $tier['shipping'], 2),
                'estimated_order_total' => $tier['qty'] === '100+'
                    ? 'Quote'
                    : '$' . number_format(($productPrice + $tier['shipping']) * (int) explode('-', $tier['qty'])[0], 2) . '+',
                'note' => $tier['note'],
            ];
        }, $tiers);
    }

    private function defaultSizeChart(array $product): array
    {
        return [
            'title' => ($product['sport'] ?? 'Product') . ' Size Chart',
            'note' => 'Measurements are garment guidance. Allow small production tolerance for custom apparel.',
            'groups' => [
                [
                    'label' => 'Adult Unisex',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['XS', '32-34"', '27"', '16.5"', '8.5"'],
                        ['S', '34-37"', '28"', '17.5"', '9"'],
                        ['M', '37-40"', '29"', '18.5"', '9.5"'],
                        ['L', '40-43"', '30"', '19.5"', '10"'],
                        ['XL', '43-46"', '31"', '20.5"', '10.5"'],
                        ['2XL', '46-49"', '32"', '21.5"', '11"'],
                        ['3XL', '49-52"', '33"', '22.5"', '11.5"'],
                        ['4XL', '52-55"', '34"', '23.5"', '12"'],
                    ],
                ],
                [
                    'label' => 'Women',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['WS', '30-32"', '25"', '14.5"', '7.5"'],
                        ['WM', '32-35"', '26"', '15.5"', '8"'],
                        ['WL', '35-38"', '27"', '16.5"', '8.5"'],
                        ['WXL', '38-41"', '28"', '17.5"', '9"'],
                        ['W2XL', '41-44"', '29"', '18.5"', '9.5"'],
                        ['W3XL', '44-47"', '30"', '19.5"', '10"'],
                    ],
                ],
                [
                    'label' => 'Youth Unisex',
                    'columns' => ['Size', 'Age', 'Chest', 'Length', 'Height'],
                    'rows' => [
                        ['YXS', '4-5', '23-24"', '20"', '100-110 cm'],
                        ['YS', '6-7', '25-26"', '21"', '110-122 cm'],
                        ['YM', '8-9', '27-28"', '23"', '122-135 cm'],
                        ['YL', '10-11', '29-31"', '25"', '135-150 cm'],
                        ['YXL', '12-14', '32-34"', '27"', '150-163 cm'],
                    ],
                ],
                [
                    'label' => 'Toddler',
                    'columns' => ['Size', 'Age', 'Chest', 'Length'],
                    'rows' => [
                        ['2T', '2 years', '20-21"', '15"'],
                        ['3T', '3 years', '21-22"', '16"'],
                        ['4T', '4 years', '22-23"', '17"'],
                    ],
                ],
            ],
            'tips' => [
                'For a looser fit, choose one size up.',
                'For team orders, collect sizes before submitting the roster.',
                'Custom sizing can be requested in the order notes.',
            ],
        ];
    }

    private function defaultFaqs(): array
    {
        return [
            [
                'question' => 'Can the admin add more customizable options later?',
                'answer' => 'Yes. The product page loops through customizable option groups, so the admin can later add fabric, print method, collar, sleeve fit, belt, delivery, or accessories using the same reusable component.',
            ],
            [
                'question' => 'Can customers upload artwork?',
                'answer' => 'Yes. The product page includes an artwork upload field and order notes field. Backend upload processing can be connected when the cart/order module is ready.',
            ],
            [
                'question' => 'Will customers see a proof before production?',
                'answer' => 'The page is designed around proof review. Production should start only after the customer approves the final design proof.',
            ],
        ];
    }
}
