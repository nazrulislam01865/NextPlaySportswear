@php
    $isEdit = $product->exists;
    $primaryCategoryId = old('primary_category_id', $product->relationLoaded('categories') ? optional($product->categories->firstWhere('pivot.is_primary', true))->id : ($product->subcategory_id ?: $product->category_id));
    $dynamicCatalogAttributeIds = $product->relationLoaded('optionGroups')
        ? $product->optionGroups->where('use_as_filter', true)->pluck('catalog_attribute_id')->filter()->map(fn($id)=>(int)$id)
        : collect();
    $persistedManualAttributeValueIds = $product->relationLoaded('attributeValues')
        ? $product->attributeValues->reject(fn($value) => $dynamicCatalogAttributeIds->contains((int) $value->attribute_id))->pluck('id')->all()
        : [];
    $selectedAttributeValueIds = collect(old('attribute_value_ids', $persistedManualAttributeValueIds))->map(fn($id)=>(int)$id)->all();
    $legacyDetailInformationHtml = collect($product->specifications ?? [])
        ->filter(fn ($value, $name) => filled($name) && filled($value))
        ->map(fn ($value, $name) => '<tr><th>'.e((string) $name).'</th><td>'.e((string) $value).'</td></tr>')
        ->implode('');
    $legacyDetailInformationHtml = $legacyDetailInformationHtml !== ''
        ? '<table><thead><tr><th>Detail</th><th>Information</th></tr></thead><tbody>'.$legacyDetailInformationHtml.'</tbody></table>'
        : null;
    $detailInformationHtml = old('detail_information_html', $product->detail_information_html ?: $legacyDetailInformationHtml);
    $existingProductImages = $product->relationLoaded('images') ? $product->images->keyBy('id') : collect();
    $submittedImageValues = old('image_urls');
    $imageValues = $submittedImageValues !== null
        ? collect($submittedImageValues)->map(function ($image, $index) use ($existingProductImages) {
            $existing = $existingProductImages->get((int) ($image['existing_id'] ?? 0));

            return [
                'client_key' => filled($image['existing_id'] ?? null) ? 'existing-'.$image['existing_id'] : 'old-'.$index,
                'existing_id' => $image['existing_id'] ?? '',
                'url' => $image['url'] ?? '',
                'preview' => $existing?->publicUrl() ?: ($image['url'] ?? ''),
                'name' => $image['name'] ?? ($image['alt'] ?? ''),
                'is_primary' => filter_var($image['is_primary'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        })->values()->all()
        : $existingProductImages->values()->map(fn ($image) => [
            'client_key' => 'existing-'.$image->id,
            'existing_id' => $image->id,
            'url' => \App\Support\PublicMedia::storedPathFromUrl($image->url) ? '' : ($image->url ?: ''),
            'preview' => $image->publicUrl(),
            'name' => $image->alt_text,
            'is_primary' => $image->is_primary,
        ])->all();
    $storedPriceHeaders = collect(old('price_table_headers', $product->price_table_headers ?? []))->values();
    if ($storedPriceHeaders->isEmpty()) {
        $storedPriceHeaders = collect(['Quantity', 'Unit Price', 'Savings']);
    }
    $priceHeaderValues = $storedPriceHeaders->slice(1)->values()->all();
    $storedPriceRows = collect(old('price_table_rows', $product->price_table_rows ?? []))->values();
    $storedPriceRanges = collect(old('price_table_ranges', []))->values();
    $storedPriceTiers = $product->relationLoaded('priceTiers') ? $product->priceTiers->values() : collect();
    if ($storedPriceRows->isEmpty() && $storedPriceTiers->isNotEmpty()) {
        $storedPriceRows = $storedPriceTiers->map(fn ($tier) => [
            (string) $tier->minimum_quantity,
            '$'.number_format((float) $tier->unit_price, 2),
            $tier->savings_label ?: '—',
        ])->values();
    }
    $parseLegacyQuantityRange = static function ($value): array {
        preg_match_all('/\d+/', str_replace(',', '', (string) $value), $matches);
        $numbers = collect($matches[0] ?? [])->map(fn ($number) => (int) $number)->values();

        return [
            'minimum_quantity' => $numbers->get(0) ?: 1,
            'maximum_quantity' => str_contains((string) $value, '+') ? null : $numbers->get(1),
        ];
    };
    $priceRowValues = $storedPriceRows->map(function ($row, $index) use ($storedPriceRanges, $storedPriceTiers, $parseLegacyQuantityRange, $priceHeaderValues) {
        $range = $storedPriceRanges->get($index);
        $tier = $storedPriceTiers->get($index);
        $legacyRange = $parseLegacyQuantityRange($row[0] ?? '');
        $minimum = data_get($range, 'minimum_quantity', $tier?->minimum_quantity ?? $legacyRange['minimum_quantity']);
        $maximum = data_get($range, 'maximum_quantity', $tier?->maximum_quantity ?? $legacyRange['maximum_quantity']);
        $cells = collect($row)->slice(1)->values()->take(count($priceHeaderValues))->all();

        return [
            'minimum_quantity' => $minimum,
            'maximum_quantity' => $maximum,
            'cells' => array_pad($cells, count($priceHeaderValues), ''),
        ];
    })->values()->all();
    if ($priceRowValues === []) {
        $priceRowValues = [[
            'minimum_quantity' => 1,
            'maximum_quantity' => null,
            'cells' => array_pad([], count($priceHeaderValues), ''),
        ]];
    }
    $optionValues = old('option_groups', $product->relationLoaded('optionGroups') ? $product->optionGroups->where('is_active', true)->filter(fn($group) => ($group->display_mode ?: 'customer') !== 'hidden')->map(fn($group) => [
        'name' => $group->name, 'code' => $group->code, 'section' => $group->section, 'type' => $group->type,
        'jersey_customization_type' => $group->jersey_customization_type,
        'display_mode' => $group->display_mode ?: 'customer', 'fixed_value_code' => $group->fixed_value_code,
        'fixed_text_value' => $group->fixed_text_value, 'show_in_summary' => $group->show_in_summary,
        'use_as_filter' => $group->use_as_filter, 'catalog_attribute_id' => $group->catalog_attribute_id, 'description' => $group->description,
        'placeholder' => $group->placeholder, 'is_required' => $group->is_required,
        'minimum_selections' => $group->minimum_selections, 'maximum_selections' => $group->maximum_selections,
        'accepted_file_types' => $group->accepted_file_types, 'maximum_file_size_mb' => $group->maximum_file_size_mb,
        'is_active' => true,
        'values' => $group->values->where('is_active', true)->map(fn($value) => [
            'existing_id' => $value->id, 'jersey_customization_option_id' => $value->jersey_customization_option_id,
            'label' => $value->label, 'code' => $value->code, 'description' => $value->description,
            'color_hex' => $value->color_hex ?: '', 'image_url' => $value->image_url,
            'image_previews' => collect($value->publicImages())->pluck('url')->values()->all(),
            'price_adjustment' => $value->price_adjustment, 'charge_type' => $value->charge_type ?: 'per_unit',
            'stock_quantity' => $value->stock_quantity, 'clear_images' => false,
            'is_default' => $value->is_default, 'is_active' => true,
        ])->values()->all(),
    ])->values()->all() : []);
    $submittedSizeValues = old('size_groups');
    $sizeValues = is_array($submittedSizeValues)
        ? collect($submittedSizeValues)->map(fn ($group) => array_merge([
            'existing_id' => '', 'size_option_group_id' => '', 'name' => '', 'code' => '',
            'audience_label' => '', 'description_html' => '', 'sizes' => [], 'sizes_text' => '',
            'chart_enabled' => false, 'chart_html' => '', 'chart_title' => '', 'chart_note' => '',
            'chart_columns' => [], 'chart_rows' => [], 'chart_columns_text' => '', 'chart_rows_text' => '',
            'chart_image_url' => '', 'chart_image_preview' => '', 'is_active' => true,
        ], is_array($group) ? $group : []))->values()->all()
        : ($product->relationLoaded('sizeGroups') ? $product->sizeGroups->where('is_active', true)->map(fn($group) => [
            'existing_id' => $group->id,
            'size_option_group_id' => $group->size_option_group_id,
            'name' => $group->name,
            'code' => $group->code,
            'audience_label' => $group->masterGroup?->audience?->label() ?? 'Legacy',
            'description_html' => $group->description_html,
            'chart_html' => $group->chart_html,
            'sizes' => $group->sizes->pluck('label')->values()->all(),
            'sizes_text' => $group->sizes->pluck('label')->implode(', '),
            'chart_enabled' => $group->chart_enabled,
            'chart_title' => $group->chart_title,
            'chart_note' => $group->chart_note,
            'chart_columns' => $group->chart_columns ?? [],
            'chart_rows' => $group->chart_rows ?? [],
            'chart_columns_text' => collect($group->chart_columns ?? [])->implode(', '),
            'chart_rows_text' => collect($group->chart_rows ?? [])->map(fn($row) => collect($row)->implode(' | '))->implode("\n"),
            'chart_image_url' => $group->chart_image_url,
            'chart_image_preview' => $group->chartImageUrl(),
            'is_active' => true,
        ])->values()->all() : []);
    $storedSpeedValues = collect($product->relationLoaded('productionSpeeds')
        ? $product->productionSpeeds->where('is_active', true)->map(fn($speed) => $speed->only([
            'name', 'code', 'description', 'price_adjustment', 'minimum_quantity', 'maximum_quantity',
            'minimum_days', 'maximum_days', 'is_active',
        ]))->values()->all()
        : [])->values();

    $productionHeaderValues = collect(old('production_table_headers', $product->production_table_headers ?? []))
        ->map(fn ($header) => trim((string) $header))
        ->filter()
        ->values();
    $productionRowValues = collect(old('production_table_rows', $product->production_table_rows ?? []))->values();

    // Preserve existing products created by the previous quantity-linked editor.
    // This fallback only reads this product's saved production options; it never
    // copies ranges from the price table or any master data.
    if ($productionHeaderValues->isEmpty() && $storedSpeedValues->isNotEmpty()) {
        $productionHeaderValues = $storedSpeedValues->pluck('name')->filter()->unique()->values();
        $productionRowValues = $storedSpeedValues
            ->groupBy(fn ($speed) => (int) data_get($speed, 'minimum_quantity', 1).'|'.(filled(data_get($speed, 'maximum_quantity')) ? (int) data_get($speed, 'maximum_quantity') : ''))
            ->map(function ($speeds) use ($productionHeaderValues) {
                $first = $speeds->first();
                $minimum = max(1, (int) data_get($first, 'minimum_quantity', 1));
                $maximum = filled(data_get($first, 'maximum_quantity')) ? (int) data_get($first, 'maximum_quantity') : null;
                $range = $maximum === null ? $minimum.'+' : $minimum.'-'.$maximum;

                return [
                    'range' => $range,
                    'cells' => $productionHeaderValues->map(function ($header) use ($speeds) {
                        $speed = $speeds->firstWhere('name', $header);

                        return [
                            'enabled' => (bool) $speed,
                            'description' => data_get($speed, 'description', ''),
                            'price_adjustment' => data_get($speed, 'price_adjustment', 0),
                            'production_time' => \App\Support\ProductionTime::format(
                                data_get($speed, 'minimum_days', 1),
                                data_get($speed, 'maximum_days', data_get($speed, 'minimum_days', 1))
                            ),
                            'minimum_days' => data_get($speed, 'minimum_days', 1),
                            'maximum_days' => data_get($speed, 'maximum_days', data_get($speed, 'minimum_days', 1)),
                        ];
                    })->values()->all(),
                ];
            })->values();
    }

    if ($productionHeaderValues->isEmpty()) {
        $productionHeaderValues = collect(['Standard Production']);
    }
    if ($productionRowValues->isEmpty()) {
        $productionRowValues = collect([[
            'range' => '1+',
            'cells' => [[
                'enabled' => false,
                'description' => '',
                'price_adjustment' => 0,
                'production_time' => '1 day',
                'minimum_days' => 1,
                'maximum_days' => 1,
            ]],
        ]]);
    }
    $productionRowValues = $productionRowValues->map(function ($row) use ($productionHeaderValues) {
        $row = is_array($row) ? $row : [];
        $cells = collect($row['cells'] ?? [])->values();

        return [
            'range' => (string) ($row['range'] ?? ''),
            'cells' => $productionHeaderValues->map(function ($header, $index) use ($cells) {
                $cell = (array) $cells->get($index, []);

                return [
                    'enabled' => (bool) ($cell['enabled'] ?? false),
                    'description' => (string) ($cell['description'] ?? ''),
                    'price_adjustment' => $cell['price_adjustment'] ?? 0,
                    'production_time' => $cell['production_time'] ?? \App\Support\ProductionTime::format(
                        $cell['minimum_days'] ?? 1,
                        $cell['maximum_days'] ?? ($cell['minimum_days'] ?? 1)
                    ),
                    'minimum_days' => $cell['minimum_days'] ?? 1,
                    'maximum_days' => $cell['maximum_days'] ?? ($cell['minimum_days'] ?? 1),
                ];
            })->values()->all(),
        ];
    })->values();
    $shippingValues = old('shipping_methods', $product->relationLoaded('shippingMethods') ? $product->shippingMethods->where('is_active', true)->map(fn($method) => $method->only(['name','code','description','price_adjustment','charge_type','minimum_days','maximum_days','is_default','is_active']))->values()->all() : []);
    $defaultRosterFields = [
        ['key' => 'name', 'label' => 'Player name', 'type' => 'text', 'max_length' => 60, 'required' => false, 'enabled' => true],
        ['key' => 'number', 'label' => 'Player number', 'type' => 'number', 'max_length' => 4, 'required' => false, 'enabled' => true],
    ];
    $rosterFieldValues = old('jersey_roster_fields', $product->jersey_roster_fields ?: $defaultRosterFields);
    $faqValues = old('faqs', $product->relationLoaded('faqs') ? $product->faqs->where('is_active', true)->map(fn($faq) => $faq->only(['question','answer','is_active']))->values()->all() : []);
    $optionGroupValidationErrors = collect($errors->getMessages())
        ->reduce(function (array $grouped, array $messages, string $key): array {
            if (preg_match('/^option_groups\.(\d+)\./', $key, $matches) !== 1) {
                return $grouped;
            }

            $index = (int) $matches[1];
            $grouped[$index] = array_values(array_unique(array_merge($grouped[$index] ?? [], $messages)));

            return $grouped;
        }, []);
    $initial = [
        'productName' => old('name', $product->name),
        'slug' => old('slug', $product->slug),
        'imageUrls' => $imageValues,
        'priceHeaders' => $priceHeaderValues,
        'priceRows' => $priceRowValues,
        'priceHighlightColumn' => (int) old('price_table_highlight_column', $product->price_table_highlight_column ?? 1),
        'optionGroups' => $optionValues,
        'optionGroupErrors' => $optionGroupValidationErrors,
        'jerseyCustomizationTypes' => $jerseyCustomizationTypes,
        'jerseyCustomizationOptions' => $jerseyCustomizationOptions,
        'sizeOptionGroups' => $sizeOptionGroups,
        'sizeGroups' => $sizeValues,
        'productionHeaders' => $productionHeaderValues->all(),
        'productionRows' => $productionRowValues->all(),
        'shippingMethods' => $shippingValues,
        'rosterFields' => $rosterFieldValues,
        'productProfile' => old('product_profile', $product->product_profile ?: 'standard'),
        'shippingMethodsEnabled' => (bool) old('shipping_methods_enabled', $product->shipping_methods_enabled ?? false),
        'jerseyRosterEnabled' => (bool) old('jersey_roster_enabled', $product->jersey_roster_enabled ?? false),
        'jerseyRosterOptional' => (bool) old('jersey_roster_optional', $product->jersey_roster_optional ?? true),
        'artworkUploadEnabled' => (bool) old('artwork_upload_enabled', $product->artwork_upload_enabled ?? false),
        'artworkUploadRequired' => (bool) old('artwork_upload_required', $product->artwork_upload_required ?? false),
        'artworkUploadTitle' => old('artwork_upload_title', $product->artwork_upload_title ?: 'Upload Custom Artwork'),
        'artworkUploadDescription' => old('artwork_upload_description', $product->artwork_upload_description ?: 'Upload one or more artwork files for the production team.'),
        'artworkUploadMaxFiles' => (int) old('artwork_upload_max_files', $product->artwork_upload_max_files ?: 5),
        'artworkUploadMaxFileSizeMb' => (int) old('artwork_upload_max_file_size_mb', $product->artwork_upload_max_file_size_mb ?: 15),
        'artworkUploadAcceptedTypes' => old('artwork_upload_accepted_types', $product->artwork_upload_accepted_types ?: 'pdf,svg,png,jpg,jpeg,webp'),
        'faqs' => $faqValues,
    ];
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-6" x-data="adminProductForm(@js($initial))" x-init="init()">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Technical values are generated or preserved without adding unrelated controls to the visible editor. --}}
    <input type="hidden" name="slug" x-model="slug">
    <input type="hidden" name="currency" value="{{ old('currency', $product->currency ?: 'USD') }}">
    <input type="hidden" name="product_profile" :value="jerseyRosterEnabled ? 'jersey' : 'standard'">
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="is_customizable" value="1">
    <input type="hidden" name="is_featured" value="{{ old('is_featured', (int) ($product->is_featured ?? false)) }}">
    <input type="hidden" name="track_inventory" value="{{ old('track_inventory', (int) ($product->track_inventory ?? false)) }}">
    <input type="hidden" name="allow_backorder" value="{{ old('allow_backorder', (int) ($product->allow_backorder ?? false)) }}">
    <input type="hidden" name="robots_index" value="{{ old('robots_index', (int) ($product->robots_index ?? true)) }}">
    <input type="hidden" name="robots_follow" value="{{ old('robots_follow', (int) ($product->robots_follow ?? true)) }}">
    @foreach($selectedAttributeValueIds as $attributeValueId)
        <input type="hidden" name="attribute_value_ids[]" value="{{ $attributeValueId }}">
    @endforeach

    <nav class="sticky top-20 z-20 -mx-4 overflow-x-auto border-y border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="flex min-w-max gap-2 text-sm font-bold">
            @foreach ([
                ['header','Header & Gallery'],
                ['information','Information'],
                ['pricing','Price Table'],
                ['options','Product Features'],
                ['sizes','Sizes'],
                ['roster','Jersey Roster'],
                ['artwork','Artwork'],
                ['delivery','Production & Shipping'],
                ['content','Description & FAQ'],
            ] as [$anchor,$label])
                <a href="#{{ $anchor }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700 hover:bg-brand-dark hover:text-white">{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    <x-admin.section-card id="header" title="1. Product Header & Gallery" description="This matches the first area of the storefront product page: product title, summary, badge, and image gallery.">
        <div class="space-y-5">
            <label class="admin-label">Product title<input class="admin-input" name="name" x-model="productName" @input="updateSlug()" required maxlength="220"></label>
            <label class="admin-label">Short product summary<textarea class="admin-textarea" name="short_description" maxlength="1500" placeholder="Shown directly below the product title.">{{ old('short_description',$product->short_description) }}</textarea></label>
            <label class="admin-label">Gallery badge label<input class="admin-input" name="badge_label" value="{{ old('badge_label',$product->badge_label) }}" maxlength="80" placeholder="Customizable, New, Best Seller"></label>

            <input type="hidden" name="new_image_primary_index" :value="newImagePrimaryIndex()">
            <label class="admin-label">Upload product images
                <input
                    x-ref="productImageInput"
                    class="admin-input py-3"
                    type="file"
                    name="images[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp,image/avif"
                    @change="previewProductImages($event)"
                >
                <small class="mt-1 block font-normal text-slate-500">Select multiple JPG, PNG, WebP or AVIF images. Maximum 5 MB each.</small>
            </label>

            <div x-show="newImagePreviews.length" x-cloak>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <strong class="text-sm text-brand-ink">New image previews</strong>
                    <span class="text-xs font-black text-brand-blue"><span x-text="newImagePreviews.length"></span> selected</span>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <template x-for="(image, index) in newImagePreviews" :key="image.client_key">
                        <article class="overflow-hidden rounded-2xl border bg-white" :class="isNewImagePrimary(index) ? 'border-brand-blue ring-2 ring-blue-100' : 'border-slate-200'">
                            <img :src="image.url" :alt="image.name" class="aspect-square w-full object-cover">
                            <div class="p-3">
                                <strong class="block truncate text-xs text-brand-ink" x-text="image.name"></strong>
                                <span class="mt-1 block text-[11px] text-slate-500" x-text="image.sizeLabel"></span>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" class="text-xs font-black" :class="isNewImagePrimary(index) ? 'text-brand-blue' : 'text-slate-600'" @click="setPrimaryUploadedImage(index)" x-text="isNewImagePrimary(index) ? 'Primary image' : 'Make primary'"></button>
                                    <button type="button" class="text-xs font-black text-red-700" @click="removeProductImage(index)">Remove</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-brand-blue">Every selected image is shown before saving. Choose one uploaded image or image link as the primary storefront image.</div>
        </div>

        <div class="mt-6 border-t border-slate-100 pt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-black text-brand-ink">Image links</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Use this only when an image is already hosted at a secure public URL.</p>
                </div>
                <button type="button" class="btn btn-white" @click="addImageUrl()">+ Add Image Link</button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(image, index) in imageUrls" :key="image.client_key || index">
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" :name="`image_urls[${index}][existing_id]`" x-model="image.existing_id">
                        <div class="grid gap-4 md:grid-cols-[112px_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                <img x-show="image.url || image.preview" :src="image.url || image.preview" :alt="image.name || productName" class="aspect-square w-full object-cover" x-on:error="$el.classList.add('hidden')" x-on:load="$el.classList.remove('hidden')">
                                <div x-show="!image.url && !image.preview" class="grid aspect-square place-items-center px-2 text-center text-xs font-bold text-slate-400">No preview</div>
                            </div>

                            <div class="space-y-3">
                                <label class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                                    <span class="text-sm font-black text-slate-700">Name</span>
                                    <input class="admin-input" :name="`image_urls[${index}][name]`" x-model="image.name" placeholder="Front view, Back view, Collar detail...">
                                </label>
                                <label class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                                    <span class="text-sm font-black text-slate-700">Image Link</span>
                                    <input class="admin-input" type="url" :name="`image_urls[${index}][url]`" x-model="image.url" placeholder="https://example.com/product-image.jpg">
                                </label>
                                <input type="hidden" :name="`image_urls[${index}][is_primary]`" :value="image.is_primary ? 1 : 0">
                                <div class="flex flex-wrap gap-2 sm:pl-[158px]">
                                    <button type="button" class="btn btn-white" @click="setPrimaryImage(index)" x-text="image.is_primary ? 'Primary image' : 'Make primary'"></button>
                                    <button type="button" class="btn btn-white text-red-700" @click="removeImageUrl(index)">Remove</button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <div x-show="imageUrls.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No image link has been added.</div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="information" title="2. Product Information" description="Build the formatted Detail / Information block first, then choose the category and tags shown beneath it on the product page.">
        <x-admin.rich-editor name="detail_information_html" :value="$detailInformationHtml" label="Detail / Information" />

        <div class="mt-8 space-y-4 border-t border-slate-100 pt-6">
            <label class="grid gap-2 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center">
                <span class="text-sm font-black text-slate-700">Category</span>
                <select class="admin-input mt-0" name="primary_category_id">
                    <option value="">Select primary category</option>
                    @foreach($categoryOptions as $category)
                        <option value="{{ $category->id }}" @selected((int)$primaryCategoryId===(int)$category->id)>{{ $category->indented_name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-2 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-center">
                <span class="text-sm font-black text-slate-700">Tags</span>
                <input class="admin-input mt-0" name="tags_text" value="{{ old('tags_text',implode(', ',$product->tags ?? [])) }}" placeholder="jersey, basketball, team uniform">
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="pricing" title="3. Quantity Price Table" description="Upload any XLSX or CSV pricing sheet, map its columns, and generate the visible storefront table. No fixed spreadsheet template is required; manual entry remains available.">
        <input type="hidden" name="price_table_headers[0]" value="Quantity">

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-black">Visible storefront table</h3>
                <p class="text-xs text-slate-500">The spreadsheet may use any headings or column order. After upload, choose the header row, quantity column(s), storefront columns, and primary live-price column.</p>
                <p x-show="priceImportStatus" x-cloak class="mt-2 text-xs font-bold text-emerald-700" x-text="priceImportStatus"></p>
                <p x-show="priceImportError" x-cloak class="mt-2 text-xs font-bold text-red-700" x-text="priceImportError"></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <label class="btn btn-white cursor-pointer">
                    <input x-ref="priceTableImportInput" class="hidden" type="file" accept=".xlsx,.csv" @change="importPriceTable($event)">
                    <span x-text="priceImportBusy ? 'Importing…' : 'Import Excel'"></span>
                </label>
                <button type="button" class="btn btn-white" @click="addPriceHeader()">+ Column</button>
                <button type="button" class="btn btn-white" @click="addPriceRow()">+ Row</button>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="min-w-[280px] border-b border-r border-slate-200 bg-slate-50 p-3 text-left align-top">
                            <span class="block font-black text-slate-700">Quantity range</span>
                            <small class="mt-1 block font-normal leading-5 text-slate-500">Enter only the starting quantity. The maximum is generated from the next row's starting quantity minus one.</small>
                        </th>
                        <template x-for="(header,hIndex) in priceHeaders" :key="hIndex">
                            <th class="min-w-[180px] border-b border-r border-slate-200 bg-slate-50 p-3">
                                <input class="admin-input" :name="`price_table_headers[${hIndex + 1}]`" x-model="priceHeaders[hIndex]">
                                <button type="button" class="mt-2 text-xs font-bold text-red-700" @click="removePriceHeader(hIndex)">Remove column</button>
                            </th>
                        </template>
                        <th class="w-24 border-b bg-slate-50 p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row,rIndex) in priceRows" :key="rIndex">
                        <tr>
                            <td class="border-r border-t border-slate-200 p-3 align-top">
                                <input type="hidden" :name="`price_table_rows[${rIndex}][0]`" :value="row.minimum_quantity || ''">
                                <input type="hidden" :name="`price_table_ranges[${rIndex}][maximum_quantity]`" :value="row.maximum_quantity === '' ? '' : row.maximum_quantity">
                                <label class="admin-label">Quantity starts at
                                    <input class="admin-input" type="number" min="1" :name="`price_table_ranges[${rIndex}][minimum_quantity]`" x-model.number="row.minimum_quantity" @input="recalculatePriceMaximums()" required>
                                </label>
                                <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <span class="block text-[10px] font-black uppercase tracking-[.12em] text-slate-400">Generated range</span>
                                    <span class="mt-1 block text-sm font-black text-brand-ink" x-text="quantityRangeLabel(row)"></span>
                                </div>
                            </td>
                            <template x-for="(cell,cIndex) in row.cells" :key="cIndex">
                                <td class="border-r border-t border-slate-200 p-3"><input class="admin-input" :name="`price_table_rows[${rIndex}][${cIndex + 1}]`" x-model="row.cells[cIndex]"></td>
                            </template>
                            <td class="border-t border-slate-200 p-3"><button type="button" class="text-xs font-black text-red-700" @click="removePriceRow(rIndex)">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-4 grid gap-5 md:grid-cols-[220px_1fr]">
            <label class="admin-label">Highlight column index<input class="admin-input" type="number" min="1" name="price_table_highlight_column" x-model.number="priceHighlightColumn"><small class="font-normal text-slate-500">1 = first price column after Quantity. This highlighted column also supplies the live unit price.</small></label>
            <label class="admin-label">Price table note<textarea class="admin-textarea" name="price_table_note">{{ old('price_table_note',$product->price_table_note) }}</textarea></label>
        </div>
    </x-admin.section-card>

    <div x-cloak x-show="priceImportMappingOpen" x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/65 p-3 sm:p-5" role="dialog" aria-modal="true" aria-labelledby="price-import-mapping-title" @keydown.escape.window="closePriceImportMapping()">
        <div x-show="priceImportMappingOpen" x-transition class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl" @click.outside="closePriceImportMapping()">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-7">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue">Flexible spreadsheet import</p>
                    <h2 id="price-import-mapping-title" class="mt-1 text-2xl font-black text-brand-ink">Map the uploaded price table</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">No predefined Excel structure is required. Select how this spreadsheet should become the customer-visible price table.</p>
                    <p class="mt-1 text-xs font-bold text-slate-400" x-text="priceImportFileName"></p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-slate-200 text-xl text-slate-500 hover:bg-slate-50" @click="closePriceImportMapping()" aria-label="Close">×</button>
            </div>

            <div class="overflow-y-auto px-5 py-5 sm:px-7">
                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <h3 class="font-black text-brand-ink">1. Locate the table</h3>
                        <label class="admin-label mt-4">Header row
                            <select class="admin-input" x-model.number="priceImportHeaderRowIndex" @change="setupPriceImportMapping(priceImportHeaderRowIndex)">
                                <template x-for="rowIndex in priceImportHeaderRowChoices()" :key="rowIndex">
                                    <option :value="rowIndex" x-text="`Row ${rowIndex + 1}`"></option>
                                </template>
                            </select>
                            <small class="font-normal text-slate-500">The selected row becomes the storefront column headings.</small>
                        </label>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        <h3 class="font-black text-brand-ink">2. Map quantity ranges</h3>
                        <label class="admin-label mt-4">Quantity layout
                            <select class="admin-input" x-model="priceImportQuantityMode" @change="refreshPriceImportQuantityMapping()">
                                <option value="range">One quantity or range column</option>
                                <option value="split">Separate minimum and maximum columns</option>
                            </select>
                        </label>

                        <div x-show="priceImportQuantityMode === 'range'" class="mt-4">
                            <label class="admin-label">Quantity / range column
                                <select class="admin-input" x-model="priceImportQuantityColumn" @change="refreshPriceImportQuantityMapping()">
                                    <option value="">Select column</option>
                                    <template x-for="column in priceImportColumnOptions()" :key="column.index">
                                        <option :value="column.index" x-text="column.label"></option>
                                    </template>
                                </select>
                                <small class="font-normal text-slate-500">A cell may contain a starting value such as 1, 5, or 12, or an explicit range such as 1-4 or 5-11. Single values use the next row's starting quantity minus one as their maximum.</small>
                            </label>
                        </div>

                        <div x-show="priceImportQuantityMode === 'split'" class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="admin-label">Minimum quantity column
                                <select class="admin-input" x-model="priceImportMinColumn" @change="refreshPriceImportQuantityMapping()">
                                    <option value="">Select column</option>
                                    <template x-for="column in priceImportColumnOptions()" :key="`min-${column.index}`">
                                        <option :value="column.index" x-text="column.label"></option>
                                    </template>
                                </select>
                            </label>
                            <label class="admin-label">Maximum quantity column
                                <select class="admin-input" x-model="priceImportMaxColumn" @change="refreshPriceImportQuantityMapping()">
                                    <option value="">No maximum column</option>
                                    <template x-for="column in priceImportColumnOptions()" :key="`max-${column.index}`">
                                        <option :value="column.index" x-text="column.label"></option>
                                    </template>
                                </select>
                                <small class="font-normal text-slate-500">When maximums leave gaps or overlaps, they are normalized from the next row's minimum quantity. A blank final maximum means no upper limit.</small>
                            </label>
                        </div>
                    </section>
                </div>

                <section class="mt-5 rounded-2xl border border-slate-200 p-4 sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-black text-brand-ink">3. Choose storefront columns</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Checked columns are generated exactly with their spreadsheet headings. Uncheck internal cost, supplier, notes, or any column customers should not see.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-brand-blue" x-text="`${priceImportIncludedColumns.length} selected`"></span>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="column in priceImportColumnOptions()" :key="`visible-${column.index}`">
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm font-bold" :class="isMappedQuantityColumn(column.index) ? 'bg-slate-100 text-slate-400' : 'bg-white text-slate-700'">
                                <input type="checkbox" :checked="priceImportIncludedColumns.includes(column.index)" :disabled="isMappedQuantityColumn(column.index)" @change="togglePriceImportColumn(column.index, $event.target.checked)">
                                <span class="min-w-0 truncate" x-text="column.label"></span>
                            </label>
                        </template>
                    </div>

                    <label class="admin-label mt-5 max-w-xl">Primary live-price column
                        <select class="admin-input" x-model="priceImportPrimaryPriceColumn">
                            <option value="">Select the price used for live calculations</option>
                            <template x-for="column in priceImportSelectedColumnOptions()" :key="`primary-${column.index}`">
                                <option :value="column.index" x-text="column.label"></option>
                            </template>
                        </select>
                        <small class="font-normal text-slate-500">This column is highlighted and supplies the product unit price for cart calculations.</small>
                    </label>
                </section>

                <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h3 class="font-black text-brand-ink">Spreadsheet preview</h3>
                        <p class="mt-1 text-xs text-slate-500">The first rows below help confirm the mapping before the current table is replaced.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-xs">
                            <thead>
                                <tr>
                                    <template x-for="(header,index) in priceImportHeaders" :key="`preview-header-${index}`">
                                        <th class="min-w-[150px] border-b border-r border-slate-200 bg-white p-3 text-left font-black text-slate-700">
                                            <span x-text="header"></span>
                                            <small class="mt-1 block font-normal text-slate-400" x-text="`Column ${spreadsheetColumnLetter(index)}`"></small>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row,rowIndex) in priceImportPreviewRows" :key="`preview-row-${rowIndex}`">
                                    <tr>
                                        <template x-for="(cell,cellIndex) in row" :key="`preview-cell-${rowIndex}-${cellIndex}`">
                                            <td class="max-w-[260px] border-r border-t border-slate-200 p-3 text-slate-600"><span class="block truncate" x-text="cell || '—'"></span></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>

                <p x-show="priceImportMappingError" x-text="priceImportMappingError" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700"></p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
                <button type="button" class="btn btn-white" @click="closePriceImportMapping()">Cancel</button>
                <button type="button" class="btn btn-red" @click="applyPriceImportMapping()">Generate Price Table</button>
            </div>
        </div>
    </div>

    <x-admin.section-card id="options" title="4. Customizable Product Features" description="Choose a jersey customization type, then add only the reusable master-data items offered by this product.">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-black text-brand-ink">Product feature fields</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Feature names come from Jersey Customization Options. Each feature shows only matching master-data items.</p>
            </div>
            <button type="button" class="btn btn-red shrink-0" @click="openNewFeatureDialog()">+ Add New Feature</button>
        </div>

        <div class="mt-5 space-y-5">
            <template x-for="(group,gIndex) in optionGroups" :key="group.client_key || gIndex">
                <article
                    class="rounded-3xl border p-4 sm:p-5"
                    :class="optionGroupHasError(gIndex) ? 'border-red-400 bg-red-50/60 ring-2 ring-red-100' : 'border-slate-200 bg-slate-50'"
                    :data-option-group-key="group.client_key"
                    :data-option-group-error="optionGroupHasError(gIndex) ? 'true' : 'false'"
                >
                    <input type="hidden" :name="`option_groups[${gIndex}][name]`" :value="group.name">
                    <input type="hidden" :name="`option_groups[${gIndex}][code]`" :value="group.code">
                    <input type="hidden" :name="`option_groups[${gIndex}][jersey_customization_type]`" :value="group.jersey_customization_type || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][section]`" value="product">
                    <input type="hidden" :name="`option_groups[${gIndex}][display_mode]`" value="customer">
                    <input type="hidden" :name="`option_groups[${gIndex}][fixed_value_code]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][fixed_text_value]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][show_in_summary]`" value="1">
                    <input type="hidden" :name="`option_groups[${gIndex}][use_as_filter]`" value="0">
                    <input type="hidden" :name="`option_groups[${gIndex}][description]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][placeholder]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][is_required]`" value="0">
                    <input type="hidden" :name="`option_groups[${gIndex}][minimum_selections]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][maximum_selections]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][accepted_file_types]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][maximum_file_size_mb]`" value="15">
                    <input type="hidden" :name="`option_groups[${gIndex}][is_active]`" value="1">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue">Feature <span x-text="gIndex + 1"></span></p>
                            <h3 class="mt-1 text-lg font-black text-brand-ink" x-text="group.name"></h3>
                            <p class="mt-1 text-xs text-slate-500">Items are filtered from Master Data by this feature type.</p>
                        </div>
                        <button type="button" class="text-sm font-black text-red-700" @click="removeOptionGroup(gIndex)">Remove feature</button>
                    </div>

                    <div
                        x-show="optionGroupHasError(gIndex)"
                        x-cloak
                        class="mt-4 rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm text-red-800"
                        role="alert"
                    >
                        <strong class="block font-black">Fix this customization feature</strong>
                        <template x-for="message in optionGroupErrorMessages(gIndex)" :key="message">
                            <p class="mt-1" x-text="message"></p>
                        </template>
                    </div>

                    <div class="mt-5 max-w-xl">
                        <label class="admin-label">Customer input style
                            <select class="admin-input" :name="`option_groups[${gIndex}][type]`" x-model="group.type">
                                <option value="image">Image choices</option>
                                <option value="swatch">Color swatches</option>
                                <option value="buttons">Buttons</option>
                                <option value="select">Dropdown</option>
                                <option value="checkbox">Checkboxes</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="font-black">Selected items</h4>
                                <p class="text-xs text-slate-500">Add reusable items available under <strong x-text="group.name"></strong>.</p>
                            </div>
                            <button type="button" class="btn btn-white" @click="openMasterItemPicker(gIndex)">+ Add Item</button>
                        </div>

                        <div class="mt-4 space-y-4">
                            <template x-for="(value,vIndex) in group.values" :key="value.client_key || vIndex">
                                @include('admin.products.partials._selected-jersey-option-item')
                            </template>

                            <div x-show="group.values.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-7 text-center text-sm text-slate-500">
                                No item selected. Choose <strong>+ Add Item</strong> to view available <span x-text="group.name"></span> items.
                            </div>
                        </div>
                    </div>
                </article>
            </template>

            <div x-show="optionGroups.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center">
                <p class="font-black text-brand-ink">No customizable feature has been added.</p>
                <p class="mt-2 text-sm text-slate-500">Choose a feature type, then add its available items from master data.</p>
                <button type="button" class="btn btn-red mt-5" @click="openNewFeatureDialog()">+ Add New Feature</button>
            </div>
        </div>
    </x-admin.section-card>

    <div x-cloak x-show="newFeatureDialogOpen" x-transition.opacity class="fixed inset-0 z-[110] grid place-items-center bg-slate-950/65 p-4" role="dialog" aria-modal="true" aria-labelledby="new-feature-dialog-title" @click.self="closeNewFeatureDialog()" @keydown.escape.window="closeNewFeatureDialog()">
        <div x-show="newFeatureDialogOpen" x-transition class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-red">New product feature</p>
                    <h2 id="new-feature-dialog-title" class="mt-2 text-2xl font-black text-brand-ink">Choose the feature name</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">The selected name determines which Jersey Customization Options are available.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl text-slate-500 hover:bg-slate-50" @click="closeNewFeatureDialog()" aria-label="Close">×</button>
            </div>

            <label class="admin-label mt-6">Feature name
                <select x-ref="newFeatureNameInput" class="admin-input" x-model="newFeatureType" @change="newFeatureNameError = ''">
                    <option value="">Select a feature</option>
                    <template x-for="(label,type) in jerseyCustomizationTypes" :key="type">
                        <option :value="type" x-text="label"></option>
                    </template>
                </select>
            </label>
            <p x-show="newFeatureNameError" x-text="newFeatureNameError" class="mt-2 text-sm font-bold text-red-700"></p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-white" @click="closeNewFeatureDialog()">Cancel</button>
                <button type="button" class="btn btn-red" @click="confirmNewFeature()">Add Feature</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="masterItemPickerOpen" x-transition.opacity class="fixed inset-0 z-[115] grid place-items-center bg-slate-950/65 p-4" role="dialog" aria-modal="true" aria-labelledby="master-item-picker-title" @click.self="closeMasterItemPicker()" @keydown.escape.window="closeMasterItemPicker()">
        <div x-show="masterItemPickerOpen" x-transition class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 sm:p-7">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue">Jersey customization master data</p>
                    <h2 id="master-item-picker-title" class="mt-2 text-2xl font-black text-brand-ink">Add <span x-text="activeMasterItemGroup()?.name || 'item'"></span></h2>
                    <p class="mt-2 text-sm text-slate-500">Only active items matching this feature type are shown. Already selected items cannot be added twice.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl text-slate-500 hover:bg-slate-50" @click="closeMasterItemPicker()" aria-label="Close">×</button>
            </div>

            <div class="overflow-y-auto p-5 sm:p-7">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="item in availableMasterItems()" :key="item.id">
                        <article class="rounded-2xl border border-slate-200 p-4" :class="isMasterItemSelected(item.id) ? 'bg-slate-50 opacity-60' : 'bg-white'">
                            <div class="flex items-start gap-3">
                                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    <img x-show="masterItemPrimaryImage(item)" :src="masterItemPrimaryImage(item)" :alt="item.name" class="h-full w-full object-cover">
                                    <span x-show="!masterItemPrimaryImage(item) && item.color_hex" class="h-10 w-10 rounded-full border border-slate-300" :style="`background:${item.color_hex}`"></span>
                                    <span x-show="!masterItemPrimaryImage(item) && !item.color_hex" class="text-[9px] font-black uppercase text-slate-400">No image</span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate font-black text-brand-ink" x-text="item.name"></h3>
                                    <p x-show="item.description" class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500" x-text="item.description"></p>
                                    <span x-show="item.color_hex" class="mt-2 inline-flex rounded-full border border-slate-200 px-2 py-1 font-mono text-[10px]" x-text="item.color_hex"></span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-white mt-4 w-full" :disabled="isMasterItemSelected(item.id)" @click="selectMasterItem(item)" x-text="isMasterItemSelected(item.id) ? 'Already selected' : 'Add item'"></button>
                        </article>
                    </template>
                </div>

                <div x-show="availableMasterItems().length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-10 text-center">
                    <p class="font-black text-brand-ink">No master-data item is available for this feature.</p>
                    <p class="mt-2 text-sm text-slate-500">Create an item under Master Data → Jersey Customization Options first.</p>
                    <a href="{{ route('admin.jersey-customization-options.index') }}" class="btn btn-white mt-5">Open Jersey Customization Options</a>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-200 bg-slate-50 p-4 sm:px-7">
                <button type="button" class="btn btn-white" @click="closeMasterItemPicker()">Done</button>
            </div>
        </div>
    </div>

    <x-admin.section-card id="sizes" title="5. Sizes & Quantities" description="Add reusable size groups from Master Data. Customers enter quantities only for the sizes included in the selected groups.">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-black">Selected size options</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Male, Female, Youth, Kids, Unisex, and custom groups come from Master Data → Size Options.</p>
            </div>
            <button type="button" class="btn btn-white" @click="openSizeGroupPicker()">+ Add Size Option</button>
        </div>

        <div class="mt-4 space-y-4">
            <template x-for="(group,index) in sizeGroups" :key="group.client_key || index">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <input type="hidden" :name="`size_groups[${index}][existing_id]`" :value="group.existing_id || ''">
                    <input type="hidden" :name="`size_groups[${index}][size_option_group_id]`" :value="group.size_option_group_id || ''">
                    <input type="hidden" :name="`size_groups[${index}][name]`" :value="group.name || ''">
                    <input type="hidden" :name="`size_groups[${index}][code]`" :value="group.code || ''">
                    <input type="hidden" :name="`size_groups[${index}][description_html]`" :value="group.description_html || ''">
                    <input type="hidden" :name="`size_groups[${index}][sizes_text]`" :value="(group.sizes || []).join(', ')">
                    <input type="hidden" :name="`size_groups[${index}][is_active]`" value="1">
                    <input type="hidden" :name="`size_groups[${index}][chart_enabled]`" :value="group.chart_enabled ? 1 : 0">
                    <input type="hidden" :name="`size_groups[${index}][chart_html]`" :value="group.chart_html || ''">
                    <input type="hidden" :name="`size_groups[${index}][chart_title]`" :value="group.chart_title || ''">
                    <input type="hidden" :name="`size_groups[${index}][chart_note]`" :value="group.chart_note || ''">
                    <input type="hidden" :name="`size_groups[${index}][chart_columns_text]`" :value="(group.chart_columns || []).join(', ')">
                    <input type="hidden" :name="`size_groups[${index}][chart_rows_text]`" :value="(group.chart_rows || []).map(row => row.join(' | ')).join('\n')">
                    <input type="hidden" :name="`size_groups[${index}][chart_image_url]`" :value="group.size_option_group_id ? '' : (group.chart_image_url || '')">
                    <input type="hidden" :name="`size_groups[${index}][clear_chart_image]`" value="0">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-lg font-black text-brand-ink" x-text="group.name"></h4>
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-brand-blue" x-text="group.audience_label || (group.size_option_group_id ? 'Size group' : 'Legacy group')"></span>
                                <span x-show="group.chart_enabled" class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">Size chart</span>
                            </div>
                            <div x-show="group.description_html" class="admin-rich-editor mt-3 max-w-3xl text-sm leading-6 text-slate-600" x-html="group.description_html"></div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <template x-for="size in (group.sizes || [])" :key="size">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-700" x-text="size"></span>
                                </template>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-start gap-3">
                            <img x-show="group.chart_image_preview" :src="group.chart_image_preview" :alt="`${group.name} size chart`" class="h-20 w-20 rounded-xl border border-slate-200 bg-slate-50 object-contain">
                            <button type="button" class="btn btn-white text-red-700" @click="sizeGroups.splice(index,1)">Remove</button>
                        </div>
                    </div>
                </article>
            </template>

            <div x-show="sizeGroups.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center">
                <p class="font-black text-brand-ink">No size option has been added.</p>
                <p class="mt-2 text-sm text-slate-500">The Sizes & Quantities step will remain hidden on the storefront.</p>
                <button type="button" class="btn btn-white mt-5" @click="openSizeGroupPicker()">Add Size Option</button>
            </div>
        </div>

        <div x-cloak x-show="sizeGroupPickerOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="closeSizeGroupPicker()">
            <div class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl" @click.outside="closeSizeGroupPicker()">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-7">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue">Master Data</p>
                        <h3 class="mt-1 text-xl font-black text-brand-ink">Add Size Option</h3>
                        <p class="mt-2 text-sm text-slate-500">Choose one or more reusable size groups. A group cannot be selected twice.</p>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-xl text-slate-500" @click="closeSizeGroupPicker()" aria-label="Close">×</button>
                </div>

                <div class="border-b border-slate-200 p-4 sm:px-7">
                    <input class="admin-input mt-0" x-model="sizeGroupPickerSearch" placeholder="Search by group, type, or size">
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-7">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="master in filteredSizeOptionGroups()" :key="master.id">
                            <article class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate font-black text-brand-ink" x-text="master.name"></h4>
                                        <p class="mt-1 text-xs font-bold text-brand-blue" x-text="master.audience_label"></p>
                                    </div>
                                    <span x-show="master.chart_enabled" class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">Chart</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <template x-for="size in master.sizes" :key="size"><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600" x-text="size"></span></template>
                                </div>
                                <button type="button" class="btn btn-white mt-4 w-full" :disabled="isSizeGroupSelected(master.id)" @click="selectSizeGroup(master)" x-text="isSizeGroupSelected(master.id) ? 'Already selected' : 'Add size option'"></button>
                            </article>
                        </template>
                    </div>

                    <div x-show="filteredSizeOptionGroups().length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-10 text-center">
                        <p class="font-black text-brand-ink">No size option is available.</p>
                        <p class="mt-2 text-sm text-slate-500">Create one under Master Data → Size Options first.</p>
                        <a href="{{ route('admin.size-option-groups.index') }}" class="btn btn-white mt-5">Open Size Options</a>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-200 bg-slate-50 p-4 sm:px-7">
                    <button type="button" class="btn btn-white" @click="closeSizeGroupPicker()">Done</button>
                </div>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="roster" title="6. Jersey Roster" description="Enable this only when the product page should generate one player-information row for every jersey selected in the size step.">
        <div class="flex flex-wrap items-start justify-between gap-4"><div><h3 class="font-black">Player names and numbers</h3><p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">When disabled, the Jersey Roster step is not shown on the storefront.</p></div><label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold"><input type="hidden" name="jersey_roster_enabled" :value="jerseyRosterEnabled ? 1 : 0"><input type="checkbox" x-model="jerseyRosterEnabled"> Show jersey roster step</label></div>
        <div x-show="jerseyRosterEnabled" class="mt-4 rounded-2xl border border-slate-200 p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="admin-label">Customer heading<input class="admin-input" name="jersey_roster_title" value="{{ old('jersey_roster_title', $product->jersey_roster_title ?: 'Add player names and numbers') }}"></label>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="jersey_roster_optional" :value="jerseyRosterOptional ? 1 : 0"><input type="checkbox" x-model="jerseyRosterOptional"><span><strong class="block text-sm">Customer may skip roster details</strong><small class="text-xs text-slate-500">When disabled, every enabled roster field marked required must be completed.</small></span></label>
            </div>
            <div class="mt-5 flex items-center justify-between gap-3"><div><h4 class="font-black">Fields shown for each jersey</h4><p class="text-xs text-slate-500">The size is generated automatically from the selected size quantities.</p></div><button type="button" class="btn btn-white" @click="addRosterField()">+ Add Field</button></div>
            <div class="mt-4 space-y-3">
                <template x-for="(field,index) in rosterFields" :key="index">
                    <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-[1.5fr_150px_130px_auto] lg:items-end">
                        <input type="hidden" :name="`jersey_roster_fields[${index}][key]`" x-model="field.key">
                        <input type="hidden" :name="`jersey_roster_fields[${index}][enabled]`" value="1">
                        <label class="admin-label">Customer label<input class="admin-input" :name="`jersey_roster_fields[${index}][label]`" x-model="field.label" @blur="if(!field.key) field.key = field.label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'')" placeholder="Player name"></label>
                        <label class="admin-label">Input type<select class="admin-input" :name="`jersey_roster_fields[${index}][type]`" x-model="field.type"><option value="text">Text</option><option value="number">Number</option></select></label>
                        <label class="admin-label">Max length<input class="admin-input" type="number" min="1" max="120" :name="`jersey_roster_fields[${index}][max_length]`" x-model="field.max_length"></label>
                        <div class="flex flex-wrap gap-3 pb-3"><input type="hidden" :name="`jersey_roster_fields[${index}][required]`" :value="field.required ? 1 : 0"><label class="text-xs font-bold"><input type="checkbox" x-model="field.required"> Required</label><button type="button" class="text-xs font-black text-red-700" @click="rosterFields.splice(index,1)">Remove</button></div>
                    </div>
                </template>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="artwork" title="7. Custom Artwork Upload" description="This is the single multi-file artwork upload step shown after sizes and jersey details. It never changes the product price.">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,.8fr)]">
            <div class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="artwork_upload_enabled" :value="artworkUploadEnabled ? 1 : 0"><input type="checkbox" x-model="artworkUploadEnabled"><span><strong class="block text-sm">Show artwork upload step</strong><small class="text-xs text-slate-500">The section disappears when disabled.</small></span></label>
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4" :class="!artworkUploadEnabled && 'opacity-50'"><input type="hidden" name="artwork_upload_required" :value="artworkUploadRequired ? 1 : 0"><input type="checkbox" x-model="artworkUploadRequired" :disabled="!artworkUploadEnabled"><span><strong class="block text-sm">Artwork is required</strong><small class="text-xs text-slate-500">At least one file must be selected.</small></span></label>
                </div>
                <div x-show="artworkUploadEnabled" class="grid gap-4 sm:grid-cols-2">
                    <label class="admin-label sm:col-span-2">Section title<input class="admin-input" name="artwork_upload_title" x-model="artworkUploadTitle" maxlength="180"></label>
                    <label class="admin-label sm:col-span-2">Customer instructions<textarea class="admin-textarea" name="artwork_upload_description" x-model="artworkUploadDescription" maxlength="3000"></textarea></label>
                    <label class="admin-label">Maximum files<input class="admin-input" type="number" min="1" max="12" name="artwork_upload_max_files" x-model.number="artworkUploadMaxFiles"></label>
                    <label class="admin-label">Maximum size per file (MB)<input class="admin-input" type="number" min="1" max="25" name="artwork_upload_max_file_size_mb" x-model.number="artworkUploadMaxFileSizeMb"></label>
                    <label class="admin-label sm:col-span-2">Accepted extensions<input class="admin-input font-mono" name="artwork_upload_accepted_types" x-model="artworkUploadAcceptedTypes" placeholder="pdf,svg,png,jpg,jpeg,webp"></label>
                </div>
            </div>
            <div x-show="artworkUploadEnabled" class="rounded-[24px] border border-slate-200 bg-slate-50 p-4 sm:p-5"><p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-red">Customer preview</p><h3 class="mt-2 text-xl font-black text-brand-ink" x-text="artworkUploadTitle || 'Upload Custom Artwork'"></h3><p class="mt-2 text-sm leading-6 text-slate-500" x-text="artworkUploadDescription || 'Upload one or more artwork files for the production team.'"></p><div class="mt-5 rounded-2xl border-2 border-dashed border-slate-300 bg-white p-6 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-xl font-black text-brand-blue">⇧</span><strong class="mt-3 block text-sm">Upload one or multiple artwork files</strong><small class="mt-2 block text-xs leading-5 text-slate-500"><span x-text="Math.max(1, Math.min(12, Number(artworkUploadMaxFiles || 5)))"></span> files maximum · <span x-text="Math.max(1, Math.min(25, Number(artworkUploadMaxFileSizeMb || 15)))"></span> MB each</small><small class="mt-1 block break-words text-xs font-bold uppercase text-brand-blue" x-text="artworkUploadAcceptedTypes"></small></div></div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="delivery" title="8. Production & Shipping" description="These choices appear together in the final configuration step before the live order summary.">
        <div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="font-black">Custom production table</h3>
                    <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">Build this table independently. Add any quantity ranges and production-option columns needed for this product. Nothing is copied from the price table or master data.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-white" @click="addProductionHeader()" :disabled="productionHeaders.length >= 12">+ Column</button>
                    <button type="button" class="btn btn-white" @click="addProductionRow()" :disabled="productionRows.length >= 100">+ Row</button>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="min-w-[210px] border-b border-r border-slate-200 bg-slate-50 p-3 text-left align-top">
                                <span class="block font-black text-slate-700">Quantity range</span>
                                <small class="mt-1 block font-normal leading-5 text-slate-500">Examples: 1-40, 41-99, 100+</small>
                            </th>
                            <template x-for="(header,columnIndex) in productionHeaders" :key="columnIndex">
                                <th class="min-w-[290px] border-b border-r border-slate-200 bg-slate-50 p-3 align-top">
                                    <label class="admin-label">Production option name
                                        <input class="admin-input" :name="`production_table_headers[${columnIndex}]`" x-model="productionHeaders[columnIndex]" maxlength="160" placeholder="Standard Production">
                                    </label>
                                    <button type="button" class="mt-2 text-xs font-black text-red-700" @click="removeProductionHeader(columnIndex)" x-show="productionHeaders.length > 1">Remove column</button>
                                </th>
                            </template>
                            <th class="w-24 border-b bg-slate-50 p-3 text-left font-black text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row,rowIndex) in productionRows" :key="row.client_key || rowIndex">
                            <tr>
                                <td class="border-r border-t border-slate-200 p-3 align-top">
                                    <label class="admin-label">Range
                                        <input class="admin-input font-black" :name="`production_table_rows[${rowIndex}][range]`" x-model="row.range" maxlength="50" placeholder="1-40" required>
                                    </label>
                                    <p class="mt-2 text-xs leading-5 text-slate-500">Entered manually for this production table.</p>
                                </td>

                                <template x-for="(cell,columnIndex) in row.cells" :key="columnIndex">
                                    <td class="border-r border-t border-slate-200 p-3 align-top">
                                        <input type="hidden" :name="`production_table_rows[${rowIndex}][cells][${columnIndex}][enabled]`" :value="cell.enabled ? 1 : 0">
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700">
                                            <input type="checkbox" x-model="cell.enabled">
                                            Offer this option
                                        </label>

                                        <div class="mt-3 space-y-3" :class="!cell.enabled && 'pointer-events-none opacity-45'">
                                            <label class="admin-label">Additional charge / piece
                                                <input class="admin-input" type="number" min="0" step="0.01" :name="`production_table_rows[${rowIndex}][cells][${columnIndex}][price_adjustment]`" x-model.number="cell.price_adjustment">
                                            </label>
                                            <label class="admin-label">Production time
                                                <input class="admin-input" type="text" :name="`production_table_rows[${rowIndex}][cells][${columnIndex}][production_time]`" x-model="cell.production_time" maxlength="60" placeholder="5-15 days">
                                                <span class="mt-1 block text-[11px] font-normal leading-4 text-slate-500">Enter one value or a range, such as 7 days or 5-15 days.</span>
                                            </label>
                                            <label class="admin-label">Description
                                                <textarea class="admin-textarea min-h-[88px]" :name="`production_table_rows[${rowIndex}][cells][${columnIndex}][description]`" x-model="cell.description" maxlength="2000" placeholder="Optional customer-facing details"></textarea>
                                            </label>
                                            <p class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-brand-blue" x-text="productionCellSummary(cell)"></p>
                                        </div>
                                    </td>
                                </template>

                                <td class="border-t border-slate-200 p-3 align-top">
                                    <button type="button" class="text-xs font-black text-red-700" @click="removeProductionRow(rowIndex)">Remove</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="productionRows.length === 0" class="mt-4 rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No production row has been added.</div>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><h3 class="font-black">Product shipping methods</h3><p class="mt-1 text-xs leading-5 text-slate-500">Only enabled methods appear as customer choices.</p></div><div class="flex flex-wrap gap-2"><label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold"><input type="hidden" name="shipping_methods_enabled" :value="shippingMethodsEnabled ? 1 : 0"><input type="checkbox" x-model="shippingMethodsEnabled"> Show shipping methods</label><button type="button" class="btn btn-white" @click="addShippingMethod()">+ Add Method</button></div></div>
            <div x-show="shippingMethodsEnabled" class="mt-4 space-y-3">
                <template x-for="(method,index) in shippingMethods" :key="index">
                    <article class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 xl:grid-cols-7">
                        <input type="hidden" :name="`shipping_methods[${index}][code]`" x-model="method.code">
                        <input type="hidden" :name="`shipping_methods[${index}][is_active]`" value="1">
                        <label class="admin-label xl:col-span-2">Name<input class="admin-input" :name="`shipping_methods[${index}][name]`" x-model="method.name" @blur="if(!method.code) method.code = method.name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')"></label>
                        <label class="admin-label">Charge<input class="admin-input" type="number" min="0" step="0.01" :name="`shipping_methods[${index}][price_adjustment]`" x-model="method.price_adjustment"></label>
                        <label class="admin-label">Charge basis<select class="admin-input" :name="`shipping_methods[${index}][charge_type]`" x-model="method.charge_type"><option value="included">Included</option><option value="per_unit">Per piece</option><option value="fixed_order">Fixed per order</option></select></label>
                        <label class="admin-label">Min days<input class="admin-input" type="number" min="0" :name="`shipping_methods[${index}][minimum_days]`" x-model="method.minimum_days"></label>
                        <label class="admin-label">Max days<input class="admin-input" type="number" min="0" :name="`shipping_methods[${index}][maximum_days]`" x-model="method.maximum_days"></label>
                        <div class="flex items-end"><button type="button" class="btn btn-white w-full text-red-700" @click="shippingMethods.splice(index,1)">Remove</button></div>
                        <label class="admin-label sm:col-span-2 xl:col-span-5">Description<input class="admin-input" :name="`shipping_methods[${index}][description]`" x-model="method.description"></label>
                        <div class="flex items-center xl:col-span-2"><input type="hidden" :name="`shipping_methods[${index}][is_default]`" :value="method.is_default ? 1 : 0"><button type="button" class="text-xs font-black text-brand-blue" @click="setDefaultShipping(index)" x-text="method.is_default ? 'Default customer choice' : 'Make default'"></button></div>
                    </article>
                </template>
                <div x-show="shippingMethods.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Shipping methods are enabled but no method has been added.</div>
            </div>
        </div>

    </x-admin.section-card>

    <x-admin.section-card id="content" title="9. Description & FAQ" description="These fields populate the final Description and FAQ tabs. Specifications are edited earlier because they also appear beside the gallery.">
        <x-admin.rich-editor name="description_html" :value="$product->description_html" label="Formatted product description" />
        <div class="mt-8 border-t border-slate-100 pt-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-black">Product FAQs</h3><p class="mt-1 text-xs text-slate-500">Each saved question appears in the FAQ tab.</p></div><button type="button" class="btn btn-white" @click="addFaq()">+ Add FAQ</button></div><div class="mt-4 space-y-3"><template x-for="(faq,index) in faqs" :key="index"><div class="rounded-2xl border border-slate-200 p-4"><input type="hidden" :name="`faqs[${index}][is_active]`" value="1"><label class="admin-label">Question<input class="admin-input" :name="`faqs[${index}][question]`" x-model="faq.question"></label><label class="admin-label mt-3">Answer<textarea class="admin-textarea" :name="`faqs[${index}][answer]`" x-model="faq.answer"></textarea></label><div class="mt-3 text-right"><button type="button" class="text-sm font-black text-red-700" @click="faqs.splice(index,1)">Remove</button></div></div></template></div></div>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur sm:bottom-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-end">
        <label class="admin-label min-w-[190px]">Product status<select class="admin-input" name="status"><option value="draft" @selected(old('status',$product->status ?: 'draft')==='draft')>Draft</option><option value="active" @selected(old('status',$product->status)==='active')>Active</option><option value="archived" @selected(old('status',$product->status)==='archived')>Archived</option></select></label>
        <a href="{{ route('admin.products.index') }}" class="btn btn-white">Cancel</a>
        @if($isEdit)<a href="{{ route('products.show',$product->slug) }}" target="_blank" class="btn btn-white">Preview Storefront</a>@endif
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Product' : 'Create Product' }}</button>
    </div>
</form>
