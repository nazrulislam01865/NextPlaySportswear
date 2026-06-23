@php
    $isEdit = $product->exists;
    $selectedCategoryIds = collect(old('category_assignments', $product->relationLoaded('categories') ? $product->categories->pluck('id')->all() : array_filter([$product->category_id, $product->subcategory_id])))->map(fn($id)=>(int)$id)->all();
    $primaryCategoryId = old('primary_category_id', $product->relationLoaded('categories') ? optional($product->categories->firstWhere('pivot.is_primary', true))->id : ($product->subcategory_id ?: $product->category_id));
    $selectedAttributeValueIds = collect(old('attribute_value_ids', $product->relationLoaded('attributeValues') ? $product->attributeValues->pluck('id')->all() : []))->map(fn($id)=>(int)$id)->all();
    $featureValues = old('features', $product->features ?? []);
    $specValues = old('specifications', collect($product->specifications ?? [])->map(fn($value, $name) => ['name' => $name, 'value' => $value])->values()->all());
    $imageValues = old('image_urls', $product->relationLoaded('images') ? $product->images->map(fn($image) => ['url' => $image->url ?: url($image->publicUrl()), 'alt' => $image->alt_text, 'is_primary' => $image->is_primary])->values()->all() : []);
    $tierValues = old('price_tiers', $product->relationLoaded('priceTiers') ? $product->priceTiers->map(fn($tier) => $tier->only(['label','minimum_quantity','maximum_quantity','unit_price','compare_at_price','savings_label']))->values()->all() : []);
    $optionValues = old('option_groups', $product->relationLoaded('optionGroups') ? $product->optionGroups->map(fn($group) => [
        'name' => $group->name, 'code' => $group->code, 'section' => $group->section, 'type' => $group->type,
        'description' => $group->description, 'placeholder' => $group->placeholder, 'is_required' => $group->is_required,
        'minimum_selections' => $group->minimum_selections, 'maximum_selections' => $group->maximum_selections,
        'accepted_file_types' => $group->accepted_file_types, 'maximum_file_size_mb' => $group->maximum_file_size_mb,
        'is_active' => $group->is_active,
        'values' => $group->values->map(fn($value) => [
            'existing_id' => $value->id, 'label' => $value->label, 'code' => $value->code, 'description' => $value->description,
            'color_hex' => $value->color_hex ?: '', 'image_url' => $value->image_url,
            'image_preview' => $value->publicImageUrl(),
            'price_adjustment' => $value->price_adjustment, 'stock_quantity' => $value->stock_quantity,
            'is_default' => $value->is_default, 'is_active' => $value->is_active,
        ])->values()->all(),
    ])->values()->all() : []);
    $sizeValues = old('size_groups', $product->relationLoaded('sizeGroups') ? $product->sizeGroups->map(fn($group) => [
        'name' => $group->name, 'code' => $group->code, 'is_active' => $group->is_active,
        'sizes_text' => $group->sizes->pluck('label')->implode(', '),
    ])->values()->all() : []);
    $artworkValues = old('artwork_methods', $product->relationLoaded('artworkMethods') ? $product->artworkMethods->map(fn($method) => $method->only(['name','code','icon','description','price_adjustment','requires_upload','is_active']))->values()->all() : []);
    $speedValues = old('production_speeds', $product->relationLoaded('productionSpeeds') ? $product->productionSpeeds->map(fn($speed) => $speed->only(['name','code','description','price_adjustment','minimum_days','maximum_days','is_active']))->values()->all() : []);
    $faqValues = old('faqs', $product->relationLoaded('faqs') ? $product->faqs->map(fn($faq) => $faq->only(['question','answer','is_active']))->values()->all() : []);
    $dimensions = $product->dimensions ?? [];
    $initial = [
        'productName' => old('name', $product->name),
        'slug' => old('slug', $product->slug),
        'features' => $featureValues,
        'specifications' => $specValues,
        'imageUrls' => $imageValues,
        'priceTiers' => $tierValues,
        'priceHeaders' => old('price_table_headers', $product->price_table_headers ?? []),
        'priceRows' => old('price_table_rows', $product->price_table_rows ?? []),
        'optionGroups' => $optionValues,
        'sizeGroups' => $sizeValues,
        'artworkMethods' => $artworkValues,
        'productionSpeeds' => $speedValues,
        'faqs' => $faqValues,
        'basePrice' => old('base_price', $product->base_price ?? 0),
    ];
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-6" x-data="adminProductForm(@js($initial))" x-init="init()">
    @csrf @if($isEdit) @method('PUT') @endif

    <nav class="sticky top-20 z-20 -mx-4 overflow-x-auto border-y border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="flex min-w-max gap-2 text-sm font-bold">
            @foreach ([['basic','Basic'],['catalog-attributes','Filters'],['media','Media'],['pricing','Pricing'],['options','Customization'],['fulfillment','Sizes & Production'],['details','Details'],['seo','SEO']] as [$anchor,$label])
                <a href="#{{ $anchor }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700 hover:bg-brand-dark hover:text-white">{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    <x-admin.section-card id="basic" title="Basic Product Information" description="Control identity, category hierarchy, publication, featured placement, and product behavior.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <label class="admin-label md:col-span-2">Product name<input class="admin-input" name="name" x-model="productName" @input="updateSlug()" required maxlength="220"></label>
            <label class="admin-label">SKU<input class="admin-input font-mono" name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="120"></label>
            <label class="admin-label md:col-span-2">Product URL slug<input class="admin-input font-mono" name="slug" x-model="slug" @change="touchSlug()" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"></label>
            <label class="admin-label">Status<select class="admin-input" name="status"><option value="draft" @selected(old('status',$product->status)==='draft')>Draft</option><option value="active" @selected(old('status',$product->status)==='active')>Active</option><option value="archived" @selected(old('status',$product->status)==='archived')>Archived</option></select></label>
            <label class="admin-label">Primary category<select class="admin-input" name="primary_category_id"><option value="">Select primary category</option>@foreach($categoryOptions as $category)<option value="{{ $category->id }}" @selected((int)$primaryCategoryId===(int)$category->id)>{{ $category->indented_name }}</option>@endforeach</select><small class="font-normal text-slate-500">Controls the main breadcrumb and canonical category placement.</small></label>
            <label class="admin-label md:col-span-2 xl:col-span-2">Additional category placements<select class="admin-input min-h-40" name="category_assignments[]" multiple>@foreach($categoryOptions as $category)<option value="{{ $category->id }}" @selected(in_array((int)$category->id,$selectedCategoryIds,true))>{{ $category->indented_name }}</option>@endforeach</select><small class="font-normal text-slate-500">Hold Ctrl/Cmd to select multiple categories. The primary category is added automatically.</small></label>
            <label class="admin-label">Product type<input class="admin-input" name="product_type" value="{{ old('product_type',$product->product_type) }}" placeholder="Jersey, hoodie, cap, bag..."></label>
            <label class="admin-label">Brand<input class="admin-input" name="brand" value="{{ old('brand',$product->brand) }}"></label>
            <label class="admin-label">Badge label<input class="admin-input" name="badge_label" value="{{ old('badge_label',$product->badge_label) }}" placeholder="New, Customizable, Best Seller"></label>
            <label class="admin-label">Badge color<input class="admin-input" name="badge_color" value="{{ old('badge_color',$product->badge_color) }}" placeholder="red, navy, green"></label>
            <label class="admin-label">Publish date<input class="admin-input" type="datetime-local" name="published_at" value="{{ old('published_at', optional($product->published_at)->format('Y-m-d\TH:i')) }}"></label>
            <label class="admin-label">Sort order<input class="admin-input" type="number" min="0" name="sort_order" value="{{ old('sort_order',$product->sort_order ?? 0) }}"></label>
        </div>

        <label class="admin-label mt-5">Short summary<textarea class="admin-textarea" name="short_description" maxlength="1500" placeholder="A concise customer-facing summary used near the product title and for SEO fallback.">{{ old('short_description',$product->short_description) }}</textarea></label>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['is_active','Storefront active','Customers can view and buy this product.',$product->is_active ?? true],
                ['is_featured','Featured product','Show in featured product sections.',$product->is_featured ?? false],
                ['is_customizable','Customizable product','Show the customization builder.',$product->is_customizable ?? true],
                ['allow_backorder','Allow backorders','Accept orders when tracked stock is unavailable.',$product->allow_backorder ?? false],
            ] as [$name,$label,$help,$default])
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name,$default)) class="mt-1"><span><strong class="block text-sm">{{ $label }}</strong><small class="mt-1 block text-xs leading-5 text-slate-500">{{ $help }}</small></span></label>
            @endforeach
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="catalog-attributes" title="Catalog Attributes & Storefront Filters" description="Assign reusable facts used by category facets. These are separate from order-time customization choices.">
        <div class="grid gap-5 lg:grid-cols-2">
            @forelse($catalogAttributes as $attribute)
                <fieldset class="rounded-2xl border border-slate-200 p-4">
                    <legend class="px-2 text-sm font-black text-brand-ink">{{ $attribute->name }}</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach($attribute->values as $value)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-sm">
                                <input type="checkbox" name="attribute_value_ids[]" value="{{ $value->id }}" @checked(in_array((int)$value->id,$selectedAttributeValueIds,true))>
                                @if($attribute->display_type==='color' && $value->color_hex)<span class="h-7 w-7 shrink-0 rounded-full border border-slate-300" style="background:{{ $value->color_hex }}"></span>@elseif($value->publicImageUrl())<img class="h-8 w-10 rounded object-cover" src="{{ $value->publicImageUrl() }}" alt="">@endif
                                <span>{{ $value->label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">No catalog attributes exist. <a class="font-bold text-brand-blue" href="{{ route('admin.attributes.create') }}">Create one</a>.</div>
            @endforelse
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="media" title="Product Media" description="Upload local product images or use secure remote image URLs. Select one primary image for listings, SEO, and structured data.">
        <div class="grid gap-5 lg:grid-cols-2">
            <label class="admin-label">Upload product images<input class="admin-input py-3" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/avif"><small class="mt-1 block font-normal text-slate-500">JPG, PNG, WebP or AVIF. Maximum 5 MB each.</small></label>
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-brand-blue">On update, the URL list below becomes the current gallery. Uploaded images are added after those URLs. Use descriptive alt text for accessibility and image SEO.</div>
        </div>
        <div class="mt-5 space-y-3">
            <template x-for="(image, index) in imageUrls" :key="index">
                <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto_auto] md:items-end">
                    <label class="admin-label">Image URL<input class="admin-input" type="url" :name="`image_urls[${index}][url]`" x-model="image.url"></label>
                    <label class="admin-label">Alt text<input class="admin-input" :name="`image_urls[${index}][alt]`" x-model="image.alt"></label>
                    <div><input type="hidden" :name="`image_urls[${index}][is_primary]`" :value="image.is_primary ? 1 : 0"><button type="button" class="btn btn-white" @click="setPrimaryImage(index)" x-text="image.is_primary ? 'Primary image' : 'Make primary'"></button></div>
                    <button type="button" class="btn btn-white text-red-700" @click="imageUrls.splice(index,1)">Remove</button>
                </div>
            </template>
            <button type="button" class="btn btn-white" @click="addImageUrl()">+ Add Image URL</button>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="pricing" title="Pricing, Inventory, Tax & Shipping" description="Control retail pricing, cost, quantity rules, inventory, product dimensions, tax, and shipping classification.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">Base price<input class="admin-input" type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price',$product->base_price ?? 0) }}" required></label>
            <label class="admin-label">Compare-at price<input class="admin-input" type="number" step="0.01" min="0" name="compare_at_price" value="{{ old('compare_at_price',$product->compare_at_price) }}"></label>
            <label class="admin-label">Cost price<input class="admin-input" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price',$product->cost_price) }}"></label>
            <label class="admin-label">Currency<select class="admin-input" name="currency">@foreach(['USD','CAD','GBP','EUR','BDT'] as $currency)<option value="{{ $currency }}" @selected(old('currency',$product->currency ?? 'USD')===$currency)>{{ $currency }}</option>@endforeach</select></label>
            <label class="admin-label">Minimum order quantity<input class="admin-input" type="number" min="1" name="minimum_quantity" value="{{ old('minimum_quantity',$product->minimum_quantity ?? 1) }}"></label>
            <label class="admin-label">Maximum order quantity<input class="admin-input" type="number" min="1" name="maximum_quantity" value="{{ old('maximum_quantity',$product->maximum_quantity) }}"></label>
            <label class="admin-label">Stock quantity<input class="admin-input" type="number" name="stock_quantity" value="{{ old('stock_quantity',$product->stock_quantity ?? 0) }}"></label>
            <label class="admin-label">Low-stock threshold<input class="admin-input" type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold',$product->low_stock_threshold ?? 5) }}"></label>
        </div>
        <label class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="track_inventory" value="0"><input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory',$product->track_inventory ?? false))><span><strong class="block text-sm">Track inventory for this product</strong><small class="text-xs text-slate-500">When disabled, availability is controlled only by status and active state.</small></span></label>
        <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">Weight<input class="admin-input" type="number" step="0.001" min="0" name="weight" value="{{ old('weight',$product->weight) }}"></label>
            <label class="admin-label">Length<input class="admin-input" type="number" step="0.001" min="0" name="length" value="{{ old('length',$dimensions['length'] ?? '') }}"></label>
            <label class="admin-label">Width<input class="admin-input" type="number" step="0.001" min="0" name="width" value="{{ old('width',$dimensions['width'] ?? '') }}"></label>
            <label class="admin-label">Height<input class="admin-input" type="number" step="0.001" min="0" name="height" value="{{ old('height',$dimensions['height'] ?? '') }}"></label>
            <label class="admin-label">Shipping class<input class="admin-input" name="shipping_class" value="{{ old('shipping_class',$product->shipping_class) }}" placeholder="standard, oversized, freight"></label>
            <label class="admin-label">Tax class<input class="admin-input" name="tax_class" value="{{ old('tax_class',$product->tax_class) }}" placeholder="standard, clothing, exempt"></label>
            <label class="admin-label sm:col-span-2">Tags, comma separated<input class="admin-input" name="tags_text" value="{{ old('tags_text',implode(', ',$product->tags ?? [])) }}" placeholder="jersey, basketball, team uniform"></label>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex items-center justify-between gap-4"><div><h3 class="font-black">Quantity pricing tiers</h3><p class="text-xs text-slate-500">These tiers power live unit-price calculations.</p></div><button type="button" class="btn btn-white" @click="addPriceTier()">+ Add Tier</button></div>
            <div class="mt-4 space-y-3"><template x-for="(tier,index) in priceTiers" :key="index"><div class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 xl:grid-cols-7"><label class="admin-label">Label<input class="admin-input" :name="`price_tiers[${index}][label]`" x-model="tier.label"></label><label class="admin-label">Min qty<input class="admin-input" type="number" min="1" :name="`price_tiers[${index}][minimum_quantity]`" x-model="tier.minimum_quantity"></label><label class="admin-label">Max qty<input class="admin-input" type="number" min="1" :name="`price_tiers[${index}][maximum_quantity]`" x-model="tier.maximum_quantity"></label><label class="admin-label">Unit price<input class="admin-input" type="number" step="0.01" min="0" :name="`price_tiers[${index}][unit_price]`" x-model="tier.unit_price"></label><label class="admin-label">Compare price<input class="admin-input" type="number" step="0.01" min="0" :name="`price_tiers[${index}][compare_at_price]`" x-model="tier.compare_at_price"></label><label class="admin-label">Savings label<input class="admin-input" :name="`price_tiers[${index}][savings_label]`" x-model="tier.savings_label"></label><div class="flex items-end"><button type="button" class="btn btn-white w-full text-red-700" @click="priceTiers.splice(index,1)">Remove</button></div></div></template></div>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-black">Flexible customer price table</h3><p class="text-xs text-slate-500">Every product can use different columns and rows.</p></div><div class="flex gap-2"><button type="button" class="btn btn-white" @click="addPriceHeader()">+ Column</button><button type="button" class="btn btn-white" @click="addPriceRow()">+ Row</button></div></div>
            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200"><table class="min-w-full border-collapse text-sm"><thead><tr><template x-for="(header,hIndex) in priceHeaders" :key="hIndex"><th class="min-w-[180px] border-b border-r border-slate-200 bg-slate-50 p-3"><input class="admin-input" :name="`price_table_headers[${hIndex}]`" x-model="priceHeaders[hIndex]"><button type="button" class="mt-2 text-xs font-bold text-red-700" @click="removePriceHeader(hIndex)">Remove column</button></th></template><th class="w-24 border-b bg-slate-50 p-3">Action</th></tr></thead><tbody><template x-for="(row,rIndex) in priceRows" :key="rIndex"><tr><template x-for="(cell,cIndex) in row" :key="cIndex"><td class="border-r border-t border-slate-200 p-3"><input class="admin-input" :name="`price_table_rows[${rIndex}][${cIndex}]`" x-model="priceRows[rIndex][cIndex]"></td></template><td class="border-t border-slate-200 p-3"><button type="button" class="text-xs font-black text-red-700" @click="priceRows.splice(rIndex,1)">Remove</button></td></tr></template></tbody></table></div>
            <div class="mt-4 grid gap-5 md:grid-cols-[220px_1fr]"><label class="admin-label">Highlight column index<input class="admin-input" type="number" min="0" name="price_table_highlight_column" value="{{ old('price_table_highlight_column',$product->price_table_highlight_column) }}"><small class="font-normal text-slate-500">0 = first column.</small></label><label class="admin-label">Price table note<textarea class="admin-textarea" name="price_table_note">{{ old('price_table_note',$product->price_table_note) }}</textarea></label></div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="options" title="Customer Customization Options" description="Create unlimited product-specific option groups. Each group can be shown as images, color swatches, buttons, select boxes, checkboxes, text, number, date, textarea, or file upload.">
        <div class="mb-5 flex flex-wrap gap-2"><button type="button" class="btn btn-red" @click="addOptionGroup('product')">+ Add Product Option</button><button type="button" class="btn btn-white" @click="addOptionGroup('decoration')">+ Add Decoration Field</button></div>
        <div class="space-y-5">
            <template x-for="(group,gIndex) in optionGroups" :key="gIndex">
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-black" x-text="group.name || `Customization Group ${gIndex+1}`"></h3><button type="button" class="text-sm font-black text-red-700" @click="optionGroups.splice(gIndex,1)">Remove group</button></div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="admin-label">Group name<input class="admin-input" :name="`option_groups[${gIndex}][name]`" x-model="group.name" @blur="updateGroupCode(group)"></label>
                        <label class="admin-label">Code<input class="admin-input font-mono" :name="`option_groups[${gIndex}][code]`" x-model="group.code"></label>
                        <label class="admin-label">Page section<select class="admin-input" :name="`option_groups[${gIndex}][section]`" x-model="group.section"><option value="product">Product options</option><option value="decoration">Decoration & print details</option></select></label>
                        <label class="admin-label">Display type<select class="admin-input" :name="`option_groups[${gIndex}][type]`" x-model="group.type">@foreach(['image','swatch','buttons','select','checkbox','text','textarea','number','file','date'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></label>
                        <label class="admin-label md:col-span-2">Description<input class="admin-input" :name="`option_groups[${gIndex}][description]`" x-model="group.description"></label>
                        <label class="admin-label">Placeholder<input class="admin-input" :name="`option_groups[${gIndex}][placeholder]`" x-model="group.placeholder"></label>
                        <label class="admin-label">Accepted file types<input class="admin-input" :name="`option_groups[${gIndex}][accepted_file_types]`" x-model="group.accepted_file_types" placeholder=".pdf,.svg,.png"></label>
                        <label class="admin-label">Minimum selections<input class="admin-input" type="number" min="0" :name="`option_groups[${gIndex}][minimum_selections]`" x-model="group.minimum_selections"></label>
                        <label class="admin-label">Maximum selections<input class="admin-input" type="number" min="1" :name="`option_groups[${gIndex}][maximum_selections]`" x-model="group.maximum_selections"></label>
                        <label class="admin-label">Max upload size MB<input class="admin-input" type="number" min="1" max="100" :name="`option_groups[${gIndex}][maximum_file_size_mb]`" x-model="group.maximum_file_size_mb"></label>
                        <div class="flex items-end gap-4 pb-3"><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" :name="`option_groups[${gIndex}][is_required]`" :value="group.is_required ? 1 : 0"><input type="checkbox" x-model="group.is_required"> Required</label><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" :name="`option_groups[${gIndex}][is_active]`" :value="group.is_active ? 1 : 0"><input type="checkbox" x-model="group.is_active"> Active</label></div>
                    </div>

                    <div x-show="['image','swatch','buttons','select','checkbox'].includes(group.type)" class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between"><div><h4 class="font-black">Customer choices</h4><p class="text-xs text-slate-500">Each value can include an image, color, price adjustment, stock, and default status.</p></div><button type="button" class="btn btn-white" @click="addOptionValue(group)">+ Add Value</button></div>
                        <div class="mt-4 space-y-4">
                            <template x-for="(value,vIndex) in group.values" :key="value.client_key || vIndex">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][existing_id]`" :value="value.existing_id || ''">

                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                        <label class="admin-label">Label<input class="admin-input" :name="`option_groups[${gIndex}][values][${vIndex}][label]`" x-model="value.label" @blur="updateValueCode(value)"></label>
                                        <label class="admin-label">Code<input class="admin-input font-mono" :name="`option_groups[${gIndex}][values][${vIndex}][code]`" x-model="value.code"></label>
                                        <label class="admin-label">Price adjustment<input class="admin-input" type="number" step="0.01" :name="`option_groups[${gIndex}][values][${vIndex}][price_adjustment]`" x-model="value.price_adjustment"></label>
                                        <label class="admin-label">Stock<input class="admin-input" type="number" min="0" :name="`option_groups[${gIndex}][values][${vIndex}][stock_quantity]`" x-model="value.stock_quantity"></label>

                                        <div x-show="group.type === 'swatch'" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            <span class="admin-label">Color preview</span>
                                            <div class="mt-2 flex items-center gap-3">
                                                <input class="h-12 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1" type="color" :value="validHex(value.color_hex) ? normalizedHex(value.color_hex) : '#E2E8F0'" @input="value.color_hex = $event.target.value.toUpperCase()" aria-label="Choose color">
                                                <span class="h-12 flex-1 rounded-lg border border-slate-300" :style="`background-color: ${validHex(value.color_hex) ? normalizedHex(value.color_hex) : '#E2E8F0'}`"></span>
                                            </div>
                                        </div>

                                        <label x-show="group.type === 'swatch'" class="admin-label md:col-span-2">HEX color code
                                            <input class="admin-input font-mono uppercase" :name="`option_groups[${gIndex}][values][${vIndex}][color_hex]`" x-model="value.color_hex" @blur="formatHex(value)" placeholder="#15345D" maxlength="7">
                                            <small class="font-normal text-slate-500">Type a three- or six-digit HEX code. The # sign is optional.</small>
                                        </label>

                                        <label x-show="group.type === 'image'" class="admin-label md:col-span-2">Fabric / option image URL
                                            <input class="admin-input" type="url" :name="`option_groups[${gIndex}][values][${vIndex}][image_url]`" x-model="value.image_url" @input="value.image_error = false; if (value.image_url) value.image_preview = value.image_url" placeholder="https://example.com/fabric.jpg">
                                            <small class="font-normal text-slate-500">Use an HTTPS image link, or upload an image below.</small>
                                        </label>

                                        <label x-show="group.type === 'image'" class="admin-label md:col-span-2">Upload fabric / option image
                                            <input class="admin-input py-3" type="file" :name="`option_groups[${gIndex}][values][${vIndex}][image_file]`" accept="image/jpeg,image/png,image/webp,image/avif" @change="previewOptionImage($event, value)">
                                            <small class="font-normal text-slate-500">JPG, PNG, WebP or AVIF. Maximum 5 MB. A new upload takes priority over the image URL.</small>
                                        </label>

                                        <div x-show="group.type === 'image'" class="md:col-span-2 xl:col-span-1">
                                            <span class="admin-label">Image preview</span>
                                            <div class="mt-2 grid aspect-[4/3] place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                                <img x-show="(value.image_preview || value.image_url) && !value.image_error" :src="value.image_preview || value.image_url" :alt="value.label || 'Option image preview'" class="h-full w-full object-cover" x-on:load="value.image_error = false" x-on:error="value.image_error = true">
                                                <span x-show="!(value.image_preview || value.image_url) || value.image_error" class="px-3 text-center text-xs font-bold text-slate-400" x-text="value.image_error ? 'Image could not be loaded' : 'No image selected'"></span>
                                            </div>
                                        </div>

                                        <label class="admin-label md:col-span-2 xl:col-span-4">Description<input class="admin-input" :name="`option_groups[${gIndex}][values][${vIndex}][description]`" x-model="value.description"></label>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                                        <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_default]`" :value="value.is_default ? 1 : 0">
                                        <button type="button" class="text-xs font-black text-brand-blue" @click="setDefaultValue(group,vIndex)" x-text="value.is_default ? 'Default choice' : 'Make default'"></button>
                                        <input type="hidden" :name="`option_groups[${gIndex}][values][${vIndex}][is_active]`" :value="value.is_active ? 1 : 0">
                                        <label class="flex items-center gap-1 text-xs font-bold"><input type="checkbox" x-model="value.is_active"> Active</label>
                                        <button type="button" class="ml-auto text-xs font-black text-red-700" @click="group.values.splice(vIndex,1)">Remove value</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </article>
            </template>
            <div x-show="optionGroups.length === 0" class="rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">No customization groups yet. Add only the groups needed for this product.</div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="fulfillment" title="Sizes, Artwork Methods & Production Speeds" description="Control the size matrix, artwork choices, and delivery/production service levels for each product.">
        <div class="grid gap-8 xl:grid-cols-2">
            <div><div class="flex items-center justify-between"><h3 class="font-black">Size groups</h3><button type="button" class="btn btn-white" @click="addSizeGroup()">+ Add</button></div><div class="mt-4 space-y-3"><template x-for="(group,index) in sizeGroups" :key="index"><div class="rounded-2xl border border-slate-200 p-4"><div class="grid gap-3 sm:grid-cols-2"><label class="admin-label">Group name<input class="admin-input" :name="`size_groups[${index}][name]`" x-model="group.name" @blur="if(!group.code) group.code = group.name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')"></label><label class="admin-label">Code<input class="admin-input" :name="`size_groups[${index}][code]`" x-model="group.code"></label></div><label class="admin-label mt-3">Sizes, comma or line separated<textarea class="admin-textarea" :name="`size_groups[${index}][sizes_text]`" x-model="group.sizes_text"></textarea></label><div class="mt-3 flex justify-between"><input type="hidden" :name="`size_groups[${index}][is_active]`" :value="group.is_active ? 1 : 0"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" x-model="group.is_active"> Active</label><button type="button" class="text-sm font-black text-red-700" @click="sizeGroups.splice(index,1)">Remove</button></div></div></template></div></div>

            <div><div class="flex items-center justify-between"><h3 class="font-black">Artwork methods</h3><button type="button" class="btn btn-white" @click="addArtworkMethod()">+ Add</button></div><div class="mt-4 space-y-3"><template x-for="(method,index) in artworkMethods" :key="index"><div class="rounded-2xl border border-slate-200 p-4"><div class="grid gap-3 sm:grid-cols-2"><label class="admin-label">Name<input class="admin-input" :name="`artwork_methods[${index}][name]`" x-model="method.name"></label><label class="admin-label">Code<input class="admin-input" :name="`artwork_methods[${index}][code]`" x-model="method.code"></label><label class="admin-label">Icon<input class="admin-input" :name="`artwork_methods[${index}][icon]`" x-model="method.icon"></label><label class="admin-label">Price adjustment<input class="admin-input" type="number" step="0.01" :name="`artwork_methods[${index}][price_adjustment]`" x-model="method.price_adjustment"></label></div><label class="admin-label mt-3">Description<input class="admin-input" :name="`artwork_methods[${index}][description]`" x-model="method.description"></label><div class="mt-3 flex flex-wrap justify-between gap-3"><div class="flex gap-4"><input type="hidden" :name="`artwork_methods[${index}][requires_upload]`" :value="method.requires_upload ? 1 : 0"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" x-model="method.requires_upload"> Requires upload</label><input type="hidden" :name="`artwork_methods[${index}][is_active]`" :value="method.is_active ? 1 : 0"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" x-model="method.is_active"> Active</label></div><button type="button" class="text-sm font-black text-red-700" @click="artworkMethods.splice(index,1)">Remove</button></div></div></template></div></div>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6"><div class="flex items-center justify-between"><div><h3 class="font-black">Production speeds</h3><p class="text-xs text-slate-500">Shown in the customer delivery estimate.</p></div><button type="button" class="btn btn-white" @click="addProductionSpeed()">+ Add Speed</button></div><div class="mt-4 space-y-3"><template x-for="(speed,index) in productionSpeeds" :key="index"><div class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2 xl:grid-cols-7"><label class="admin-label">Name<input class="admin-input" :name="`production_speeds[${index}][name]`" x-model="speed.name"></label><label class="admin-label">Code<input class="admin-input" :name="`production_speeds[${index}][code]`" x-model="speed.code"></label><label class="admin-label xl:col-span-2">Description<input class="admin-input" :name="`production_speeds[${index}][description]`" x-model="speed.description"></label><label class="admin-label">Price adjustment<input class="admin-input" type="number" step="0.01" :name="`production_speeds[${index}][price_adjustment]`" x-model="speed.price_adjustment"></label><label class="admin-label">Min days<input class="admin-input" type="number" min="0" :name="`production_speeds[${index}][minimum_days]`" x-model="speed.minimum_days"></label><label class="admin-label">Max days<input class="admin-input" type="number" min="0" :name="`production_speeds[${index}][maximum_days]`" x-model="speed.maximum_days"></label><div class="flex items-center justify-between sm:col-span-2 xl:col-span-7"><div><input type="hidden" :name="`production_speeds[${index}][is_active]`" :value="speed.is_active ? 1 : 0"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" x-model="speed.is_active"> Active</label></div><button type="button" class="text-sm font-black text-red-700" @click="productionSpeeds.splice(index,1)">Remove</button></div></div></template></div></div>
    </x-admin.section-card>

    <x-admin.section-card id="details" title="Product Details & Customer Content" description="Use the rich editor to format headings, emphasis, lists, links, tables, and detailed content. Add reusable features, specifications, and FAQs.">
        <x-admin.rich-editor name="description_html" :value="$product->description_html" label="Formatted product description" />

        <div class="mt-8 grid gap-8 xl:grid-cols-2">
            <div><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><h3 class="font-black">Feature highlights</h3><button type="button" class="btn btn-white" @click="addFeature()">+ Add</button></div><div class="mt-4 space-y-3"><template x-for="(feature,index) in features" :key="index"><div class="flex gap-2"><input class="admin-input" :name="`features[${index}]`" x-model="features[index]" placeholder="Free digital proof before production"><button type="button" class="btn btn-white text-red-700" @click="features.splice(index,1)">×</button></div></template></div></div>
            <div><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><h3 class="font-black">Specifications</h3><button type="button" class="btn btn-white" @click="addSpecification()">+ Add</button></div><div class="mt-4 space-y-3"><template x-for="(spec,index) in specifications" :key="index"><div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"><input class="admin-input" :name="`specifications[${index}][name]`" x-model="spec.name" placeholder="Fabric"><input class="admin-input" :name="`specifications[${index}][value]`" x-model="spec.value" placeholder="160gsm polyester"><button type="button" class="btn btn-white text-red-700" @click="specifications.splice(index,1)">×</button></div></template></div></div>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><h3 class="font-black">Product FAQs</h3><button type="button" class="btn btn-white" @click="addFaq()">+ Add FAQ</button></div><div class="mt-4 space-y-3"><template x-for="(faq,index) in faqs" :key="index"><div class="rounded-2xl border border-slate-200 p-4"><label class="admin-label">Question<input class="admin-input" :name="`faqs[${index}][question]`" x-model="faq.question"></label><label class="admin-label mt-3">Answer<textarea class="admin-textarea" :name="`faqs[${index}][answer]`" x-model="faq.answer"></textarea></label><div class="mt-3 flex justify-between"><input type="hidden" :name="`faqs[${index}][is_active]`" :value="faq.is_active ? 1 : 0"><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" x-model="faq.is_active"> Active</label><button type="button" class="text-sm font-black text-red-700" @click="faqs.splice(index,1)">Remove</button></div></div></template></div></div>
    </x-admin.section-card>

    <x-admin.section-card id="seo" title="SEO, Social Sharing & Structured Data" description="Control every important product SEO field without editing templates.">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="admin-label">Meta title<input class="admin-input" name="meta_title" value="{{ old('meta_title',$product->meta_title) }}" maxlength="255"><small class="font-normal text-slate-500">Recommended: about 50–60 characters.</small></label>
            <label class="admin-label">Canonical URL<input class="admin-input" type="url" name="canonical_url" value="{{ old('canonical_url',$product->canonical_url) }}"></label>
            <label class="admin-label md:col-span-2">Meta description<textarea class="admin-textarea" name="meta_description" maxlength="1000">{{ old('meta_description',$product->meta_description) }}</textarea></label>
            <label class="admin-label md:col-span-2">Meta keywords<input class="admin-input" name="meta_keywords" value="{{ old('meta_keywords',$product->meta_keywords) }}" placeholder="Optional legacy keyword phrases"></label>
            <label class="admin-label">Open Graph title<input class="admin-input" name="og_title" value="{{ old('og_title',$product->og_title) }}"></label>
            <label class="admin-label">Open Graph image URL<input class="admin-input" type="url" name="og_image_url" value="{{ old('og_image_url',$product->og_image_url) }}"></label>
            <label class="admin-label md:col-span-2">Open Graph description<textarea class="admin-textarea" name="og_description">{{ old('og_description',$product->og_description) }}</textarea></label>
            <label class="admin-label md:col-span-2">Custom Product schema JSON<textarea class="admin-textarea min-h-[220px] font-mono text-xs" name="schema_json_text" placeholder='{"@type":"Product", ...}'>{{ old('schema_json_text', $product->schema_json ? json_encode($product->schema_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '') }}</textarea><small class="font-normal text-slate-500">Optional. The storefront already generates safe Product schema; use this only for approved additional schema fields.</small></label>
        </div>
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="robots_index" value="0"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index',$product->robots_index ?? true))><span><strong class="block text-sm">Allow search indexing</strong><small class="text-xs text-slate-500">Disable for drafts, private products, or temporary campaigns.</small></span></label>
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="robots_follow" value="0"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow',$product->robots_follow ?? true))><span><strong class="block text-sm">Allow link following</strong><small class="text-xs text-slate-500">Controls the robots follow directive.</small></span></label>
        </div>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
        <a href="{{ route('admin.products.index') }}" class="btn btn-white">Cancel</a>
        @if($isEdit)<a href="{{ route('products.show',$product->slug) }}" target="_blank" class="btn btn-white">Preview Storefront</a>@endif
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Product' : 'Create Product' }}</button>
    </div>
</form>
