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
    $specValues = old('specifications', collect($product->specifications ?? [])->map(fn($value, $name) => ['name' => $name, 'value' => $value])->values()->all());
    $imageValues = old('image_urls', $product->relationLoaded('images') ? $product->images->map(fn($image) => ['url' => $image->url ?: url($image->publicUrl()), 'alt' => $image->alt_text, 'is_primary' => $image->is_primary])->values()->all() : []);
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
        'display_mode' => $group->display_mode ?: 'customer', 'fixed_value_code' => $group->fixed_value_code,
        'fixed_text_value' => $group->fixed_text_value, 'show_in_summary' => $group->show_in_summary,
        'use_as_filter' => $group->use_as_filter, 'catalog_attribute_id' => $group->catalog_attribute_id, 'description' => $group->description,
        'placeholder' => $group->placeholder, 'is_required' => $group->is_required,
        'minimum_selections' => $group->minimum_selections, 'maximum_selections' => $group->maximum_selections,
        'accepted_file_types' => $group->accepted_file_types, 'maximum_file_size_mb' => $group->maximum_file_size_mb,
        'is_active' => true,
        'values' => $group->values->where('is_active', true)->map(fn($value) => [
            'existing_id' => $value->id, 'label' => $value->label, 'code' => $value->code, 'description' => $value->description,
            'color_hex' => $value->color_hex ?: '', 'image_url' => $value->image_url,
            'image_previews' => collect($value->publicImages())->pluck('url')->values()->all(),
            'price_adjustment' => $value->price_adjustment, 'charge_type' => $value->charge_type ?: 'per_unit',
            'stock_quantity' => $value->stock_quantity, 'clear_images' => false,
            'is_default' => $value->is_default, 'is_active' => true,
        ])->values()->all(),
    ])->values()->all() : []);
    $sizeValues = old('size_groups', $product->relationLoaded('sizeGroups') ? $product->sizeGroups->where('is_active', true)->map(fn($group) => [
        'existing_id' => $group->id, 'name' => $group->name, 'code' => $group->code, 'is_active' => true,
        'sizes_text' => $group->sizes->pluck('label')->implode(', '), 'chart_enabled' => $group->chart_enabled,
        'chart_title' => $group->chart_title, 'chart_note' => $group->chart_note,
        'chart_columns_text' => collect($group->chart_columns ?? [])->implode(', '),
        'chart_rows_text' => collect($group->chart_rows ?? [])->map(fn($row) => collect($row)->implode(' | '))->implode("\n"),
        'chart_image_url' => $group->chart_image_url, 'chart_image_preview' => $group->chartImageUrl(),
        'clear_chart_image' => false,
    ])->values()->all() : []);
    $speedValues = old('production_speeds', $product->relationLoaded('productionSpeeds') ? $product->productionSpeeds->where('is_active', true)->map(fn($speed) => $speed->only(['name','code','description','price_adjustment','minimum_days','maximum_days','is_active']))->values()->all() : []);
    $shippingValues = old('shipping_methods', $product->relationLoaded('shippingMethods') ? $product->shippingMethods->where('is_active', true)->map(fn($method) => $method->only(['name','code','description','price_adjustment','charge_type','minimum_days','maximum_days','is_default','is_active']))->values()->all() : []);
    $defaultRosterFields = [
        ['key' => 'name', 'label' => 'Player name', 'type' => 'text', 'max_length' => 60, 'required' => false, 'enabled' => true],
        ['key' => 'number', 'label' => 'Player number', 'type' => 'number', 'max_length' => 4, 'required' => false, 'enabled' => true],
    ];
    $rosterFieldValues = old('jersey_roster_fields', $product->jersey_roster_fields ?: $defaultRosterFields);
    $faqValues = old('faqs', $product->relationLoaded('faqs') ? $product->faqs->where('is_active', true)->map(fn($faq) => $faq->only(['question','answer','is_active']))->values()->all() : []);
    $initial = [
        'productName' => old('name', $product->name),
        'slug' => old('slug', $product->slug),
        'specifications' => $specValues,
        'imageUrls' => $imageValues,
        'priceHeaders' => $priceHeaderValues,
        'priceRows' => $priceRowValues,
        'optionGroups' => $optionValues,
        'sizeGroups' => $sizeValues,
        'productionSpeeds' => $speedValues,
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
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="space-y-5">
                <label class="admin-label">Product title<input class="admin-input" name="name" x-model="productName" @input="updateSlug()" required maxlength="220"></label>
                <label class="admin-label">Short product summary<textarea class="admin-textarea" name="short_description" maxlength="1500" placeholder="Shown directly below the product title.">{{ old('short_description',$product->short_description) }}</textarea></label>
                <label class="admin-label">Gallery badge label<input class="admin-input" name="badge_label" value="{{ old('badge_label',$product->badge_label) }}" maxlength="80" placeholder="Customizable, New, Best Seller"></label>
            </div>
            <div class="space-y-5">
                <label class="admin-label">Upload product images<input class="admin-input py-3" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/avif"><small class="mt-1 block font-normal text-slate-500">JPG, PNG, WebP or AVIF. Maximum 5 MB each.</small></label>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-brand-blue">The primary image appears first in the large gallery. Alt text is used for accessibility and image SEO.</div>
            </div>
        </div>

        <div class="mt-5 space-y-3">
            <template x-for="(image, index) in imageUrls" :key="index">
                <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto_auto] md:items-end">
                    <label class="admin-label">Existing or remote image URL<input class="admin-input" type="url" :name="`image_urls[${index}][url]`" x-model="image.url"></label>
                    <label class="admin-label">Alt text<input class="admin-input" :name="`image_urls[${index}][alt]`" x-model="image.alt"></label>
                    <div><input type="hidden" :name="`image_urls[${index}][is_primary]`" :value="image.is_primary ? 1 : 0"><button type="button" class="btn btn-white" @click="setPrimaryImage(index)" x-text="image.is_primary ? 'Primary image' : 'Make primary'"></button></div>
                    <button type="button" class="btn btn-white text-red-700" @click="imageUrls.splice(index,1)">Remove</button>
                </div>
            </template>
            <button type="button" class="btn btn-white" @click="addImageUrl()">+ Add Image URL</button>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="information" title="2. Product Information" description="These values populate the Detail / Information table and the SKU, category, tags, and brand rows beside the gallery.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <label class="admin-label">SKU<input class="admin-input font-mono" name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="120"></label>
            <label class="admin-label">Brand<input class="admin-input" name="brand" value="{{ old('brand',$product->brand) }}" maxlength="120"></label>
            <label class="admin-label">Tags, comma separated<input class="admin-input" name="tags_text" value="{{ old('tags_text',implode(', ',$product->tags ?? [])) }}" placeholder="jersey, basketball, team uniform"></label>
            <label class="admin-label">Primary category<select class="admin-input" name="primary_category_id"><option value="">Select primary category</option>@foreach($categoryOptions as $category)<option value="{{ $category->id }}" @selected((int)$primaryCategoryId===(int)$category->id)>{{ $category->indented_name }}</option>@endforeach</select><small class="font-normal text-slate-500">Used for the main breadcrumb and first category link.</small></label>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-black">Detail / Information rows</h3><p class="mt-1 text-xs text-slate-500">The first rows appear beside the gallery and the full list appears again in the Specifications tab.</p></div>
                <button type="button" class="btn btn-white" @click="addSpecification()">+ Add Information Row</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(spec,index) in specifications" :key="index">
                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto]">
                        <input class="admin-input" :name="`specifications[${index}][name]`" x-model="spec.name" placeholder="Product Type, Fabric, MOQ, Lead Time...">
                        <input class="admin-input" :name="`specifications[${index}][value]`" x-model="spec.value" placeholder="Customer-visible information">
                        <button type="button" class="btn btn-white text-red-700" @click="specifications.splice(index,1)">×</button>
                    </div>
                </template>
            </div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="pricing" title="3. Quantity Price Table" description="The visible storefront table is now the single source of truth for quantity pricing. Minimum and maximum quantities choose the active row, while the highlighted price column drives live storefront and cart calculations.">
        <input type="hidden" name="price_table_headers[0]" value="Quantity">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-black">Visible storefront table</h3>
                <p class="text-xs text-slate-500">Customers see the minimum quantity from each row. Maximum quantity is used only to determine where that price row stops.</p>
            </div>
            <div class="flex gap-2">
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
                            <small class="mt-1 block font-normal leading-5 text-slate-500">Min Qty is shown in the storefront. Max Qty controls the pricing boundary.</small>
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
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="admin-label">Min Qty<input class="admin-input" type="number" min="1" :name="`price_table_ranges[${rIndex}][minimum_quantity]`" x-model.number="row.minimum_quantity" required></label>
                                    <label class="admin-label">Max Qty<input class="admin-input" type="number" min="1" :name="`price_table_ranges[${rIndex}][maximum_quantity]`" x-model.number="row.maximum_quantity" placeholder="No limit"></label>
                                </div>
                            </td>
                            <template x-for="(cell,cIndex) in row.cells" :key="cIndex">
                                <td class="border-r border-t border-slate-200 p-3"><input class="admin-input" :name="`price_table_rows[${rIndex}][${cIndex + 1}]`" x-model="row.cells[cIndex]"></td>
                            </template>
                            <td class="border-t border-slate-200 p-3"><button type="button" class="text-xs font-black text-red-700" @click="priceRows.splice(rIndex,1)">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-4 grid gap-5 md:grid-cols-[220px_1fr]">
            <label class="admin-label">Highlight column index<input class="admin-input" type="number" min="1" name="price_table_highlight_column" value="{{ old('price_table_highlight_column', $product->price_table_highlight_column ?? 1) }}"><small class="font-normal text-slate-500">1 = first price column after Quantity. This highlighted column also supplies the live unit price.</small></label>
            <label class="admin-label">Price table note<textarea class="admin-textarea" name="price_table_note">{{ old('price_table_note',$product->price_table_note) }}</textarea></label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="options" title="4. Customizable Product Features" description="Add only the customer choices this product needs. A saved feature automatically appears on the product page.">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-black text-brand-ink">Product feature fields</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Create the feature name first, then choose how customers will interact with it and add its available values.</p>
            </div>
            <button type="button" class="btn btn-red shrink-0" @click="openNewFeatureDialog()">+ Add New Feature</button>
        </div>

        <div class="mt-5 space-y-5">
            <template x-for="(group,gIndex) in optionGroups" :key="group.client_key || gIndex">
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4 sm:p-5" :data-option-group-key="group.client_key">
                    {{-- The streamlined editor intentionally keeps these legacy values hidden so existing products remain compatible. --}}
                    <input type="hidden" :name="`option_groups[${gIndex}][name]`" :value="group.name">
                    <input type="hidden" :name="`option_groups[${gIndex}][code]`" :value="group.code">
                    <input type="hidden" :name="`option_groups[${gIndex}][section]`" value="product">
                    <input type="hidden" :name="`option_groups[${gIndex}][display_mode]`" value="customer">
                    <input type="hidden" :name="`option_groups[${gIndex}][fixed_value_code]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][fixed_text_value]`" value="">
                    <input type="hidden" :name="`option_groups[${gIndex}][show_in_summary]`" :value="group.show_in_summary ? 1 : 0">
                    <input type="hidden" :name="`option_groups[${gIndex}][use_as_filter]`" :value="group.use_as_filter ? 1 : 0">
                    <input type="hidden" :name="`option_groups[${gIndex}][description]`" :value="group.description || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][placeholder]`" :value="group.placeholder || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][is_required]`" :value="group.is_required ? 1 : 0">
                    <input type="hidden" :name="`option_groups[${gIndex}][minimum_selections]`" :value="group.minimum_selections || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][maximum_selections]`" :value="group.maximum_selections || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][accepted_file_types]`" :value="group.accepted_file_types || ''">
                    <input type="hidden" :name="`option_groups[${gIndex}][maximum_file_size_mb]`" :value="group.maximum_file_size_mb || 15">
                    <input type="hidden" :name="`option_groups[${gIndex}][is_active]`" value="1">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-blue">Feature <span x-text="gIndex + 1"></span></p>
                            <h3 class="mt-1 text-lg font-black text-brand-ink" x-text="group.name"></h3>
                            <p class="mt-1 text-xs text-slate-500">Shown in Choose Product Features on the product page.</p>
                        </div>
                        <button type="button" class="text-sm font-black text-red-700" @click="removeOptionGroup(gIndex)">Remove feature</button>
                    </div>

                    <div class="mt-5 max-w-xl">
                        <label class="admin-label">Customer input style
                            <select class="admin-input" :name="`option_groups[${gIndex}][type]`" x-model="group.type" @change="normalizeOptionGroupType(group)">
                                <option value="image">Image choices</option>
                                <option value="swatch">Color swatches</option>
                                <option value="buttons">Buttons</option>
                                <option value="select">Dropdown</option>
                                <option value="checkbox">Checkboxes</option>
                                <option value="text">Text</option>
                                <option value="textarea">Long text</option>
                                <option value="number">Number</option>
                                <option value="file">File upload</option>
                                <option value="date">Date</option>
                            </select>
                        </label>
                    </div>

                    <div x-show="choiceInputTypes().includes(group.type)" x-cloak class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="font-black">Customer-visible values</h4>
                                <p class="text-xs text-slate-500">Add every value the customer can choose for this feature.</p>
                            </div>
                            <button type="button" class="btn btn-white" @click="addOptionValue(group)">+ Add Value</button>
                        </div>

                        <div class="mt-4 space-y-4">
                            <template x-for="(value,vIndex) in group.values" :key="value.client_key || vIndex">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][existing_id]`" :value="value.existing_id || ''">
                                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][clear_images]`" :value="value.clear_images ? 1 : 0">
                                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][code]`" x-model="value.code">
                                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_active]`" value="1">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <label class="admin-label">Label<input class="admin-input" :name="`option_groups[${gIndex}][values][${vIndex}][label]`" x-model="value.label" @blur="updateValueCode(value)"></label>
                                        <label class="admin-label">Additional charge<input class="admin-input" type="number" step="0.01" :name="`option_groups[${gIndex}][values][${vIndex}][price_adjustment]`" x-model="value.price_adjustment"></label>
                                        <label class="admin-label">Charge basis<select class="admin-input" :name="`option_groups[${gIndex}][values][${vIndex}][charge_type]`" x-model="value.charge_type"><option value="included">Included / no charge</option><option value="per_unit">Per piece</option><option value="fixed_order">Fixed per order</option></select></label>
                                        <div class="flex items-end"><input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_default]`" :value="value.is_default ? 1 : 0"><button type="button" class="btn btn-white w-full" @click="setDefaultValue(group,vIndex)" x-text="value.is_default ? 'Default choice' : 'Make default'"></button></div>

                                        <div x-show="group.type === 'swatch'" class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="admin-label">Color preview</span><div class="mt-2 flex items-center gap-3"><input class="h-12 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1" type="color" :value="validHex(value.color_hex) ? normalizedHex(value.color_hex) : '#E2E8F0'" @input="value.color_hex = $event.target.value.toUpperCase()"><span class="h-12 flex-1 rounded-lg border border-slate-300" :style="`background-color: ${validHex(value.color_hex) ? normalizedHex(value.color_hex) : '#E2E8F0'}`"></span></div></div>
                                        <label x-show="group.type === 'swatch'" class="admin-label">HEX color<input class="admin-input font-mono uppercase" :name="`option_groups[${gIndex}][values][${vIndex}][color_hex]`" x-model="value.color_hex" @blur="formatHex(value)" placeholder="#15345D" maxlength="7"></label>
                                        <label x-show="group.type === 'image'" class="admin-label md:col-span-2">Optional image URL<input class="admin-input" type="url" :name="`option_groups[${gIndex}][values][${vIndex}][image_url]`" x-model="value.image_url" @input="if(value.image_url && !(value.image_previews || []).includes(value.image_url)) value.image_previews = [...(value.image_previews || []), value.image_url]"></label>
                                        <label x-show="group.type === 'image'" class="admin-label md:col-span-2">Upload one or more images<input class="admin-input py-3" type="file" multiple :name="`option_groups[${gIndex}][values][${vIndex}][image_files][]`" accept="image/jpeg,image/png,image/webp,image/avif" @change="previewOptionImages($event, value)"><small class="font-normal text-slate-500">Up to 12 images, maximum 5 MB each.</small></label>
                                        <div x-show="group.type === 'image' && (value.image_previews || []).length" class="md:col-span-2 xl:col-span-4"><div class="mb-2 flex items-center justify-between"><span class="admin-label">Current images</span><button type="button" class="text-xs font-black text-red-700" @click="clearOptionImages(value)">Clear all images</button></div><div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"><template x-for="(preview,pIndex) in value.image_previews" :key="`${preview}-${pIndex}`"><div class="aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100"><img :src="preview" :alt="value.label || 'Option image'" class="h-full w-full object-cover"></div></template></div></div>
                                        <label class="admin-label md:col-span-2 xl:col-span-4">Description<input class="admin-input" :name="`option_groups[${gIndex}][values][${vIndex}][description]`" x-model="value.description"></label>
                                    </div>
                                    <div class="mt-4 flex justify-end border-t border-slate-100 pt-4"><button type="button" class="text-xs font-black text-red-700" @click="removeOptionValue(group,vIndex)">Remove value</button></div>
                                </div>
                            </template>

                            <div x-show="group.values.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-7 text-center text-sm text-slate-500">
                                No values added yet. Select <strong>+ Add Value</strong> to create the first customer choice.
                            </div>
                        </div>
                    </div>
                </article>
            </template>

            <div x-show="optionGroups.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center">
                <p class="font-black text-brand-ink">No customizable feature has been added.</p>
                <p class="mt-2 text-sm text-slate-500">Create a feature name, select its customer input style, and add the values offered by this product.</p>
                <button type="button" class="btn btn-red mt-5" @click="openNewFeatureDialog()">+ Add New Feature</button>
            </div>
        </div>
    </x-admin.section-card>

    <div x-cloak x-show="newFeatureDialogOpen" x-transition.opacity class="fixed inset-0 z-[110] grid place-items-center bg-slate-950/65 p-4" role="dialog" aria-modal="true" aria-labelledby="new-feature-dialog-title" @click.self="closeNewFeatureDialog()" @keydown.escape.window="closeNewFeatureDialog()">
        <div x-show="newFeatureDialogOpen" x-transition class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-7" @keydown.enter.prevent="confirmNewFeature()">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-red">New product feature</p>
                    <h2 id="new-feature-dialog-title" class="mt-2 text-2xl font-black text-brand-ink">Name the feature</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Examples: Fabric, Collar Style, Primary Color, Sleeve Type, or Print Location.</p>
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl text-slate-500 hover:bg-slate-50" @click="closeNewFeatureDialog()" aria-label="Close">×</button>
            </div>

            <label class="admin-label mt-6">Feature name
                <input x-ref="newFeatureNameInput" class="admin-input" type="text" maxlength="160" x-model="newFeatureName" @input="newFeatureNameError = ''" placeholder="For example: Collar Style" autocomplete="off">
            </label>
            <p x-show="newFeatureNameError" x-text="newFeatureNameError" class="mt-2 text-sm font-bold text-red-700"></p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-white" @click="closeNewFeatureDialog()">Cancel</button>
                <button type="button" class="btn btn-red" @click="confirmNewFeature()">Add Feature</button>
            </div>
        </div>
    </div>

    <x-admin.section-card id="sizes" title="5. Sizes & Quantities" description="These groups and sizes appear in Select Sizes & Quantities. Size quantities determine the total order quantity; sizes do not have separate prices.">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-black">Size groups and size charts</h3><p class="text-xs text-slate-500">Add Adult, Youth, Women, or any custom group offered for this product.</p></div><button type="button" class="btn btn-white" @click="addSizeGroup()">+ Add Size Group</button></div>
        <div class="mt-4 space-y-4">
            <template x-for="(group,index) in sizeGroups" :key="index">
                <article class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                    <input type="hidden" :name="`size_groups[${index}][existing_id]`" :value="group.existing_id || ''">
                    <input type="hidden" :name="`size_groups[${index}][clear_chart_image]`" :value="group.clear_chart_image ? 1 : 0">
                    <input type="hidden" :name="`size_groups[${index}][code]`" x-model="group.code">
                    <input type="hidden" :name="`size_groups[${index}][is_active]`" value="1">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="admin-label">Group name<input class="admin-input" :name="`size_groups[${index}][name]`" x-model="group.name" @blur="if(!group.code) group.code = group.name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')" placeholder="Adult, Youth, Women"></label>
                        <label class="admin-label">Available sizes<input class="admin-input" :name="`size_groups[${index}][sizes_text]`" x-model="group.sizes_text" placeholder="XS, S, M, L, XL, 2XL"></label>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-5 rounded-2xl bg-slate-50 p-4"><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" :name="`size_groups[${index}][chart_enabled]`" :value="group.chart_enabled ? 1 : 0"><input type="checkbox" x-model="group.chart_enabled"> Show size chart</label><button type="button" class="ml-auto text-sm font-black text-red-700" @click="sizeGroups.splice(index,1)">Remove group</button></div>
                    <div x-show="group.chart_enabled" class="mt-4 grid gap-4 rounded-2xl border border-blue-100 bg-blue-50/50 p-4 md:grid-cols-2">
                        <label class="admin-label">Chart title<input class="admin-input" :name="`size_groups[${index}][chart_title]`" x-model="group.chart_title" placeholder="Adult Size Chart"></label>
                        <label class="admin-label">Chart image URL<input class="admin-input" type="url" :name="`size_groups[${index}][chart_image_url]`" x-model="group.chart_image_url" @input="group.chart_image_preview = group.chart_image_url"></label>
                        <label class="admin-label md:col-span-2">Chart note<input class="admin-input" :name="`size_groups[${index}][chart_note]`" x-model="group.chart_note"></label>
                        <label class="admin-label">Columns, comma separated<input class="admin-input" :name="`size_groups[${index}][chart_columns_text]`" x-model="group.chart_columns_text" placeholder="Size, Chest, Length, Sleeve"></label>
                        <label class="admin-label">Rows, one per line<textarea class="admin-textarea min-h-36 font-mono text-xs" :name="`size_groups[${index}][chart_rows_text]`" x-model="group.chart_rows_text" placeholder="XS | 32-34 | 27 | 15.5&#10;S | 34-37 | 28 | 16.5"></textarea></label>
                        <label class="admin-label">Upload size chart image<input class="admin-input py-3" type="file" :name="`size_groups[${index}][chart_image]`" accept="image/jpeg,image/png,image/webp,image/avif" @change="previewSizeChartImage($event, group)"></label>
                        <div x-show="group.chart_image_preview" class="rounded-2xl border border-slate-200 bg-white p-3"><div class="mb-2 flex items-center justify-between"><span class="text-xs font-black uppercase tracking-wide text-slate-500">Chart preview</span><button type="button" class="text-xs font-black text-red-700" @click="clearSizeChartImage(group)">Remove</button></div><img :src="group.chart_image_preview" alt="Size chart preview" class="max-h-48 w-full rounded-xl object-contain"></div>
                    </div>
                </article>
            </template>
            <div x-show="sizeGroups.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No sizes are configured. The Select Sizes & Quantities step will not be shown.</div>
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
            <div class="flex items-center justify-between"><div><h3 class="font-black">Production speeds</h3><p class="text-xs text-slate-500">Remove every row when no production-speed selector should appear.</p></div><button type="button" class="btn btn-white" @click="addProductionSpeed()">+ Add</button></div>
            <div class="mt-4 grid gap-3 xl:grid-cols-2">
                <template x-for="(speed,index) in productionSpeeds" :key="index">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <input type="hidden" :name="`production_speeds[${index}][code]`" x-model="speed.code">
                        <input type="hidden" :name="`production_speeds[${index}][is_active]`" value="1">
                        <div class="grid gap-3 sm:grid-cols-2"><label class="admin-label">Name<input class="admin-input" :name="`production_speeds[${index}][name]`" x-model="speed.name" @blur="if(!speed.code) speed.code = speed.name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')"></label><label class="admin-label">Per-unit charge<input class="admin-input" type="number" step="0.01" :name="`production_speeds[${index}][price_adjustment]`" x-model="speed.price_adjustment"></label><label class="admin-label">Minimum days<input class="admin-input" type="number" min="0" :name="`production_speeds[${index}][minimum_days]`" x-model="speed.minimum_days"></label><label class="admin-label">Maximum days<input class="admin-input" type="number" min="0" :name="`production_speeds[${index}][maximum_days]`" x-model="speed.maximum_days"></label></div>
                        <label class="admin-label mt-3">Description<input class="admin-input" :name="`production_speeds[${index}][description]`" x-model="speed.description"></label>
                        <div class="mt-3 text-right"><button type="button" class="text-sm font-black text-red-700" @click="productionSpeeds.splice(index,1)">Remove</button></div>
                    </div>
                </template>
            </div>
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
