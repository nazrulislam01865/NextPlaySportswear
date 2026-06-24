@props(['product'])
@php
    $builderConfig = [
        'title' => $product['title'],
        'currency' => $product['currency'] ?? 'USD',
        'base_price' => $product['base_price'],
        'minimum_quantity' => $product['minimum_quantity'] ?? 1,
        'maximum_quantity' => $product['maximum_quantity'] ?? 999,
        'gallery' => $product['gallery'],
        'product_profile' => $product['product_profile'] ?? 'standard',
        'option_groups' => $product['option_groups'] ?? [],
        'size_groups' => $product['size_groups'] ?? [],
        'artwork_upload' => $product['artwork_upload'] ?? ['enabled' => false],
        'production_speeds' => $product['production_speeds'] ?? [],
        'shipping_methods' => $product['shipping_methods'] ?? [],
        'jersey_roster' => $product['jersey_roster'] ?? ['enabled' => false, 'optional' => true, 'fields' => []],
        'price_tiers' => $product['price_tiers'] ?? [],
    ];

    $allGroups = collect($product['option_groups'] ?? []);
    $fixedGroups = $allGroups->where('display_mode', 'fixed');
    $customerOptionGroups = $allGroups->where('display_mode', 'customer');
    $sizeGroupsWithCharts = collect($product['size_groups'] ?? [])->filter(fn ($group) => (bool) data_get($group, 'chart.enabled'));
    $roster = $product['jersey_roster'] ?? [];
    $rosterEnabled = ($product['product_profile'] ?? 'standard') === 'jersey' && (bool) ($roster['enabled'] ?? false);
    $artworkUpload = $product['artwork_upload'] ?? ['enabled' => false];
    $stepNumber = 1;
@endphp

<section id="configure-product" class="section-padding bg-slate-100" aria-labelledby="configure-product-heading">
    <div class="site-container" x-data="productBuilder(@js($builderConfig))" x-init="init()" @keydown.escape.window="closeSizeChart()">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Product configuration</p>
            <h2 id="configure-product-heading" class="mt-1 font-display text-3xl font-bold uppercase leading-tight tracking-tight text-brand-ink sm:text-5xl">Configure This Product</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">The choices below are controlled separately for this product. Fixed details are shown for reference, while customer-customizable features can be selected before adding the item to cart.</p>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form method="POST" enctype="multipart/form-data" action="{{ route('cart.items.store') }}" class="space-y-5" @submit="if(!validate()) $event.preventDefault()">
                @csrf
                <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
                <input type="hidden" name="quantity" :value="totalQuantity()">
                <input type="hidden" name="design_option" :value="selectionSummary() || 'Configured product'">
                <input type="hidden" name="delivery_preference" :value="deliveryLabel()">
                <input type="hidden" name="size_summary" :value="sizeSummary()">
                <input type="hidden" name="artwork_status" :value="artworkLabel()">
                <input type="hidden" name="notes" :value="selectionSummary()">
                <input type="hidden" name="configuration_json" x-model="configurationJson">

                @if($fixedGroups->isNotEmpty())
                    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card" aria-labelledby="included-details-title">
                        <div class="border-b border-slate-200 bg-slate-50 p-5 sm:p-6">
                            <h3 id="included-details-title" class="text-xl font-black text-brand-ink">Included Product Details</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">These values are set by the administrator for this product and cannot be changed.</p>
                        </div>
                        <div class="grid gap-px bg-slate-200 sm:grid-cols-2">
                            @foreach($fixedGroups as $group)
                                @php
                                    $values = collect($group['values'] ?? []);
                                    if (($group['type'] ?? '') === 'checkbox') {
                                        $selectedValues = $values->where('default', true);
                                    } else {
                                        $fixedValue = $values->firstWhere('id', $group['fixed_value_code'] ?? null) ?? $values->firstWhere('default', true) ?? $values->first();
                                        $selectedValues = $fixedValue ? collect([$fixedValue]) : collect();
                                    }
                                @endphp
                                <div class="min-w-0 bg-white p-5">
                                    <span class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">{{ $group['label'] }}</span>
                                    @if($selectedValues->isNotEmpty())
                                        <div class="mt-3 space-y-3">
                                            @foreach($selectedValues as $value)
                                                <div class="flex min-w-0 items-center gap-3">
                                                    @if(!empty($value['image']))
                                                        <img src="{{ $value['image'] }}" alt="{{ $value['label'] }}" class="h-14 w-14 shrink-0 rounded-xl border border-slate-200 object-cover" loading="lazy" decoding="async">
                                                    @elseif(!empty($value['color']))
                                                        <span class="h-10 w-10 shrink-0 rounded-full border-4 border-white shadow ring-1 ring-slate-200" style="background-color: {{ $value['color'] }}"></span>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <strong class="block break-words text-sm text-brand-ink">{{ $value['label'] }}</strong>
                                                        @if(!empty($value['description']))<small class="mt-1 block text-xs leading-5 text-slate-500">{{ $value['description'] }}</small>@endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif(filled($group['fixed_text_value'] ?? null))
                                        <strong class="mt-2 block break-words text-sm text-brand-ink">{{ $group['fixed_text_value'] }}</strong>
                                    @else
                                        <strong class="mt-2 block text-sm text-brand-ink">Configured by admin</strong>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($product['is_customizable'] && $customerOptionGroups->isNotEmpty())
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="product-options">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber++ }}</span>
                            <div><h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">Choose Product Features</h3><p class="mt-1 text-sm leading-6 text-slate-500">Select only the colors, fabrics, collars, surcharges, and other options enabled for this product.</p></div>
                        </div>
                        <div class="space-y-6 p-5 sm:p-6">
                            @foreach($customerOptionGroups as $group)<x-storefront.product.option-group :group="$group" />@endforeach
                        </div>
                    </section>
                @endif

                @if(!empty($product['size_groups']))
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="size-quantity">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber++ }}</span>
                            <div><h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">Select Sizes & Quantities</h3><p class="mt-1 text-sm leading-6 text-slate-500">Choose from the Adult, Youth, Women, or other size groups configured for this product.</p></div>
                        </div>
                        <div class="p-5 sm:p-6">
                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($product['size_groups'] as $group)
                                        <button type="button" @click="activeSizeGroup=@js($group['id'])" :class="activeSizeGroup === @js($group['id']) ? 'bg-brand-dark text-white' : 'border border-slate-300 bg-white text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-black">{{ $group['label'] }}</button>
                                    @endforeach
                                </div>
                                <strong class="text-sm text-brand-blue">Total: <span x-text="totalQuantity()"></span> pcs</strong>
                            </div>

                            @foreach($product['size_groups'] as $group)
                                <div x-show="activeSizeGroup === @js($group['id'])" x-cloak class="overflow-hidden rounded-2xl border border-slate-200">
                                    @if(data_get($group, 'chart.enabled'))
                                        <div class="flex flex-col gap-3 border-b border-slate-200 bg-blue-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <p class="text-xs font-bold leading-5 text-slate-600">Use the administrator-provided {{ $group['label'] }} measurements before selecting quantities.</p>
                                            <button type="button" class="shrink-0 rounded-xl border border-brand-blue bg-white px-4 py-2 text-xs font-black text-brand-blue" @click="openSizeChart(@js($group['id']))">View Size Chart</button>
                                        </div>
                                    @endif

                                    <div class="grid gap-3 p-3 sm:hidden">
                                        @foreach($group['sizes'] as $size)
                                            @php $key = $group['id'].':'.$size['code']; @endphp
                                            <div class="rounded-2xl bg-slate-50 p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <strong class="text-base text-brand-ink">{{ $size['label'] }}</strong>
                                                    <div class="grid h-11 w-32 grid-cols-[40px_1fr_40px] overflow-hidden rounded-xl border border-slate-300 bg-white">
                                                        <button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)-1)" aria-label="Decrease {{ $size['label'] }} quantity">−</button>
                                                        <input class="min-w-0 border-0 text-center font-black" type="number" min="0" :max="config.maximum_quantity || 999" x-model.number="quantities[@js($key)]" @change="changeQuantity(@js($key), quantities[@js($key)])" aria-label="{{ $size['label'] }} quantity">
                                                        <button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)+1)" aria-label="Increase {{ $size['label'] }} quantity">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="touch-scroll-x hidden sm:block" tabindex="0" aria-label="Size quantity table">
                                        <table class="w-full min-w-[600px] text-sm">
                                            <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.12em] text-slate-500"><tr><th class="px-4 py-3">Size</th><th class="px-4 py-3">Quantity</th></tr></thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($group['sizes'] as $size)
                                                    @php $key = $group['id'].':'.$size['code']; @endphp
                                                    <tr>
                                                        <td class="px-4 py-3 font-black">{{ $size['label'] }}</td>
                                                        <td class="px-4 py-3"><div class="grid h-10 w-32 grid-cols-[36px_1fr_36px] overflow-hidden rounded-xl border border-slate-300"><button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)-1)">−</button><input class="min-w-0 border-0 text-center font-black" type="number" min="0" :max="config.maximum_quantity || 999" x-model.number="quantities[@js($key)]" @change="changeQuantity(@js($key), quantities[@js($key)])"><button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)+1)">+</button></div></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($rosterEnabled)
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="jersey-roster">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-blue-50 p-5 sm:p-6">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber++ }}</span>
                            <div class="min-w-0 flex-1"><h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">{{ $roster['title'] ?? 'Add Player Names and Numbers' }}</h3><p class="mt-1 text-sm leading-6 text-slate-500">A separate row is generated for every jersey selected above, with its size locked to the chosen size quantity.</p></div>
                        </div>
                        <div class="p-5 sm:p-6">
                            @if($roster['optional'] ?? true)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <input type="checkbox" class="mt-1" :checked="rosterEnabled" @change="toggleRoster($event.target.checked)">
                                    <span><strong class="block text-sm text-brand-ink">Add individual details for each jersey</strong><small class="mt-1 block text-xs leading-5 text-slate-500">Turn this on to enter names, numbers, front text, back text, or other fields offered by the administrator.</small></span>
                                </label>
                            @endif

                            <div x-show="rosterEnabled" x-cloak class="mt-5">
                                <div x-show="totalQuantity() === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Select jersey sizes and quantities first. The individual jersey list will then appear here.</div>
                                <div x-show="totalQuantity() > 250" class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm font-bold text-amber-900">Individual jersey details support up to 250 pieces in one configured cart line.</div>
                                <div x-show="rosterRows.length" class="space-y-3">
                                    <template x-for="(row, rowIndex) in rosterRows" :key="`${row.size_key}:${rowIndex}`">
                                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                                <strong class="text-sm text-brand-ink">Jersey <span x-text="rowIndex + 1"></span></strong>
                                                <span class="rounded-full bg-brand-dark px-3 py-1 text-xs font-black text-white"><span x-text="row.size_group_label"></span> · <span x-text="row.size_label"></span></span>
                                            </div>
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                @foreach(collect($roster['fields'] ?? [])->filter(fn ($field) => ($field['enabled'] ?? true)) as $field)
                                                    <label class="text-xs font-black uppercase tracking-[.08em] text-slate-500">
                                                        {{ $field['label'] }} @if($field['required'] ?? false)<span class="text-brand-red">*</span>@endif
                                                        <input class="mt-2 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold normal-case tracking-normal text-brand-ink" type="{{ ($field['type'] ?? 'text') === 'number' ? 'text' : 'text' }}" @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif maxlength="{{ min(120, max(1, (int) ($field['max_length'] ?? 60))) }}" x-model="row.values[@js($field['key'])]" @input="sync()" placeholder="{{ $field['label'] }}">
                                                    </label>
                                                @endforeach
                                            </div>
                                        </article>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if((bool) ($artworkUpload['enabled'] ?? false))
                    @php
                        $artworkTypes = collect($artworkUpload['accepted_types'] ?? ['pdf','svg','png','jpg','jpeg','webp'])
                            ->map(fn ($type) => strtolower(ltrim(trim((string) $type), '.')))->filter()->unique()->values();
                        $artworkAccept = $artworkTypes->map(fn ($type) => '.'.$type)->implode(',');
                        $artworkMaxFiles = max(1, min(12, (int) ($artworkUpload['max_files'] ?? 5)));
                        $artworkMaxSize = max(1, min(25, (int) ($artworkUpload['max_file_size_mb'] ?? 15)));
                    @endphp
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="artwork-upload">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber++ }}</span>
                            <div>
                                <h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">{{ $artworkUpload['title'] ?? 'Upload Custom Artwork' }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $artworkUpload['description'] ?? 'Upload one or more artwork files for the production team.' }}</p>
                            </div>
                        </div>
                        <div class="p-5 sm:p-6">
                            <label class="block rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 text-center transition hover:border-brand-blue hover:bg-blue-50/40 sm:p-8">
                                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white text-2xl font-black text-brand-blue shadow-sm">⇧</span>
                                <strong class="mt-4 block text-base text-brand-ink">Upload one or multiple artwork files @if($artworkUpload['required'] ?? false)<span class="text-brand-red">*</span>@endif</strong>
                                <small class="mt-2 block text-xs leading-5 text-slate-500">
                                    Up to {{ $artworkMaxFiles }} files · {{ $artworkMaxSize }} MB each · {{ $artworkTypes->map(fn ($type) => strtoupper($type))->implode(', ') }}
                                </small>
                                <input
                                    class="mt-4 w-full text-sm"
                                    type="file"
                                    name="artwork_files[]"
                                    multiple
                                    accept="{{ $artworkAccept }}"
                                    @change="handleArtworkFiles($event)"
                                    @if($artworkUpload['required'] ?? false) required @endif
                                >
                            </label>

                            <div x-show="artworkFiles.length" x-cloak class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <strong class="text-sm text-brand-ink">Selected artwork</strong>
                                    <span class="text-xs font-black text-brand-blue"><span x-text="artworkFiles.length"></span> / {{ $artworkMaxFiles }} files</span>
                                </div>
                                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <template x-for="file in artworkFiles" :key="file.name + file.size">
                                        <li class="min-w-0 rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                            <strong class="block truncate text-brand-ink" x-text="file.name"></strong>
                                            <span class="text-slate-500" x-text="file.sizeLabel"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </section>
                @endif

                @if(!empty($product['production_speeds']) || !empty($product['shipping_methods']))
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="delivery-options">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber++ }}</span><div><h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">Production & Shipping</h3><p class="mt-1 text-sm leading-6 text-slate-500">The administrator controls which production speeds and product-specific shipping methods are visible.</p></div></div>
                        <div class="space-y-5 p-5 sm:p-6">
                            @if(!empty($product['production_speeds']))
                                <label class="block text-sm font-black text-slate-700">Production speed<select class="mt-2 h-12 w-full rounded-xl border border-slate-300 bg-white px-4" x-model="productionSpeed" @change="sync()">@foreach($product['production_speeds'] as $speed)<option value="{{ $speed['id'] }}">{{ $speed['label'] }} · {{ $speed['minimum_days'] }}–{{ $speed['maximum_days'] }} days @if($speed['price_delta'] != 0)· {{ $speed['price_delta'] > 0 ? '+' : '−' }}${{ number_format(abs($speed['price_delta']),2) }}/piece @endif</option>@endforeach</select></label>
                            @endif

                            @if(!empty($product['shipping_methods']))
                                <div>
                                    <h4 class="text-sm font-black text-slate-700">Shipping method</h4>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach($product['shipping_methods'] as $method)
                                            <button type="button" @click="shippingMethod=@js($method['id']); sync()" :class="shippingMethod === @js($method['id']) ? 'border-brand-blue bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 bg-white'" class="rounded-2xl border-2 p-4 text-left">
                                                <span class="flex items-start justify-between gap-3"><strong class="text-sm text-brand-ink">{{ $method['label'] }}</strong><small class="shrink-0 font-black text-brand-red" x-text="shippingChargeLabel(@js($method))"></small></span>
                                                @if(!empty($method['description']))<small class="mt-2 block text-xs leading-5 text-slate-500">{{ $method['description'] }}</small>@endif
                                                <small class="mt-2 block text-xs font-bold text-brand-blue">Estimated transport: {{ $method['minimum_days'] }}–{{ $method['maximum_days'] }} working days</small>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                <div class="xl:hidden"><button type="submit" class="btn btn-red w-full py-4">Add Configured Product</button></div>
            </form>

            <aside class="h-fit overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-hero xl:sticky xl:top-36">
                <div class="bg-gradient-to-br from-brand-dark to-brand-navy p-5 text-white"><p class="text-[10px] font-black uppercase tracking-[.16em] text-blue-200">Live estimate</p><h3 class="mt-1 text-2xl font-black">Your Custom Order</h3></div>
                <div class="grid min-w-0 grid-cols-[72px_minmax(0,1fr)] gap-3 border-b border-slate-200 p-5"><img src="{{ $product['image'] }}" alt="" class="h-[72px] w-[72px] rounded-xl object-cover"><div><strong class="block text-sm leading-5">{{ $product['title'] }}</strong><small class="mt-1 block text-xs text-slate-500">SKU: {{ $product['sku'] }}</small><small class="mt-1 block text-xs text-slate-500"><span x-text="totalQuantity()"></span> total pieces</small></div></div>
                <div class="max-h-72 space-y-2 overflow-y-auto border-b border-slate-200 p-5 text-xs">
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Selections</span><strong class="min-w-0 break-words text-right" x-text="selectionSummary() || 'No options selected'"></strong></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Sizes</span><strong class="min-w-0 break-words text-right" x-text="sizeSummary() || 'No quantities selected'"></strong></div>
                    @if($rosterEnabled)<div class="flex justify-between gap-3"><span class="text-slate-500">Player details</span><strong class="text-right" x-text="rosterSummary()"></strong></div>@endif
                    @if((bool) ($artworkUpload['enabled'] ?? false))<div class="flex justify-between gap-3"><span class="text-slate-500">Artwork</span><strong class="text-right" x-text="artworkLabel()"></strong></div>@endif
                    @if(!empty($product['production_speeds']))<div class="flex justify-between gap-3"><span class="text-slate-500">Production</span><strong class="text-right" x-text="speedLabel()"></strong></div>@endif
                    @if(!empty($product['shipping_methods']))<div class="flex justify-between gap-3"><span class="text-slate-500">Shipping</span><strong class="text-right" x-text="shippingLabel()"></strong></div>@endif
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Tier base price</span><strong x-text="money(tierPrice())"></strong></div>
                    <div class="flex justify-between"><span class="text-slate-500">Options / estimated unit</span><strong x-text="money(optionSurcharge())"></strong></div>
                    <div x-show="fixedOrderSurcharge() !== 0" class="flex justify-between"><span class="text-slate-500">Fixed order charges</span><strong x-text="money(fixedOrderSurcharge())"></strong></div>
                    <div class="flex justify-between"><span class="text-slate-500">Estimated unit price</span><strong x-text="money(unitPrice())"></strong></div>
                    <div class="flex justify-between"><span class="text-slate-500">Quantity</span><strong x-text="totalQuantity()"></strong></div>
                    <div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-4"><span class="font-black">Estimated total</span><strong class="text-2xl font-black text-brand-red" x-text="money(totalPrice())"></strong></div>
                </div>
                <div class="px-5 pb-5"><button type="button" class="btn btn-red hidden w-full py-4 xl:flex" @click="$root.querySelector('form').requestSubmit()">Add Configured Product</button><p class="mt-3 text-center text-[10px] leading-4 text-slate-500">Final prices, selections, sizes, shipping, roster information, and files are recalculated and validated by the Laravel backend.</p></div>
            </aside>
        </div>

        @if($sizeGroupsWithCharts->isNotEmpty())
            <div x-show="sizeChartOpen" x-cloak class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/70 p-3 sm:p-6" role="dialog" aria-modal="true" aria-label="Product size chart" @click.self="closeSizeChart()">
                <div class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-[24px] bg-white shadow-2xl">
                    <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4"><div><p class="text-[10px] font-black uppercase tracking-[.14em] text-brand-red">Administrator provided</p><h3 class="text-xl font-black text-brand-ink" x-text="chartGroup()?.chart?.title || 'Size Chart'"></h3></div><button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-slate-300 text-xl font-black" @click="closeSizeChart()" aria-label="Close size chart">×</button></div>
                    @foreach($sizeGroupsWithCharts as $group)
                        <div x-show="activeChartGroup === @js($group['id'])" x-cloak class="p-5 sm:p-7">
                            @if(data_get($group, 'chart.note'))<p class="mb-5 rounded-xl bg-blue-50 p-4 text-sm leading-6 text-slate-600">{{ data_get($group, 'chart.note') }}</p>@endif
                            @if(data_get($group, 'chart.image'))<img src="{{ data_get($group, 'chart.image') }}" alt="{{ data_get($group, 'chart.title', $group['label'].' size chart') }}" class="mb-6 max-h-[520px] w-full rounded-2xl border border-slate-200 object-contain" loading="lazy" decoding="async">@endif
                            @if(!empty(data_get($group, 'chart.columns')) && !empty(data_get($group, 'chart.rows')))
                                <div class="touch-scroll-x rounded-2xl border border-slate-200" tabindex="0">
                                    <table class="w-full min-w-[620px] text-sm"><thead class="bg-brand-dark text-left text-xs font-black uppercase tracking-[.08em] text-white"><tr>@foreach(data_get($group, 'chart.columns', []) as $column)<th class="px-4 py-3">{{ $column }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@foreach(data_get($group, 'chart.rows', []) as $row)<tr class="odd:bg-slate-50">@foreach($row as $cell)<td class="px-4 py-3 font-semibold text-slate-700">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
