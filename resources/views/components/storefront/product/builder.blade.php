@props(['product'])
@php
    $builderConfig = [
        'title' => $product['title'],
        'currency' => $product['currency'] ?? 'USD',
        'base_price' => $product['base_price'],
        'minimum_quantity' => $product['minimum_quantity'] ?? 1,
        'maximum_quantity' => $product['maximum_quantity'] ?? null,
        'gallery' => $product['gallery'],
        'option_groups' => $product['option_groups'] ?? [],
        'size_groups' => $product['size_groups'] ?? [],
        'artwork_methods' => $product['artwork_methods'] ?? [],
        'production_speeds' => $product['production_speeds'] ?? [],
        'price_tiers' => $product['price_tiers'] ?? [],
    ];
    $productOptionGroups = collect($product['option_groups'] ?? [])->where('section', 'product');
    $decorationGroups = collect($product['option_groups'] ?? [])->where('section', 'decoration');
@endphp
<section id="configure-product" class="section-padding bg-slate-100" aria-labelledby="configure-product-heading">
    <div class="site-container" x-data="productBuilder(@js($builderConfig))" x-init="init()">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Flexible product builder</p>
            <h2 id="configure-product-heading" class="mt-1 font-display text-4xl font-bold uppercase tracking-tight text-brand-ink sm:text-5xl">Configure This Product Your Way</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">Only the customization choices enabled for this product by the administrator are shown below. Pricing updates from the configured quantity tier, selected options, artwork method, and production speed.</p>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form method="POST" enctype="multipart/form-data" action="{{ route('cart.items.store') }}" class="space-y-5" @submit="if(!validate()) $event.preventDefault()">
                @csrf
                <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
                <input type="hidden" name="quantity" :value="totalQuantity()">
                <input type="hidden" name="design_option" :value="selectionSummary() || 'Configured product'">
                <input type="hidden" name="delivery_preference" :value="speedLabel()">
                <input type="hidden" name="size_summary" :value="sizeSummary()">
                <input type="hidden" name="artwork_status" :value="artworkLabel()">
                <input type="hidden" name="notes" :value="selectionSummary()">
                <input type="hidden" name="configuration_json" x-model="configurationJson">

                @if($product['is_customizable'] && $productOptionGroups->isNotEmpty())
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="product-options">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">1</span><div><h3 class="text-2xl font-black text-brand-ink">Choose Product Options</h3><p class="mt-1 text-sm leading-6 text-slate-500">Colors, materials, finishes, and other product-specific choices.</p></div></div>
                        <div class="space-y-6 p-5 sm:p-6">@foreach($productOptionGroups as $group)<x-storefront.product.option-group :group="$group" />@endforeach</div>
                    </section>
                @endif

                @if(!empty($product['size_groups']))
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="size-quantity">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">2</span><div><h3 class="text-2xl font-black text-brand-ink">Select Sizes & Quantities</h3><p class="mt-1 text-sm leading-6 text-slate-500">Enter quantities across any size groups configured by the admin.</p></div></div>
                        <div class="p-5 sm:p-6">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div class="flex flex-wrap gap-2">@foreach($product['size_groups'] as $group)<button type="button" @click="activeSizeGroup=@js($group['id'])" :class="activeSizeGroup === @js($group['id']) ? 'bg-brand-dark text-white' : 'border border-slate-300 bg-white text-slate-700'" class="rounded-xl px-4 py-2 text-sm font-black">{{ $group['label'] }}</button>@endforeach</div><strong class="text-sm text-brand-blue">Total: <span x-text="totalQuantity()"></span> pcs</strong></div>
                            @foreach($product['size_groups'] as $group)
                                <div x-show="activeSizeGroup === @js($group['id'])" class="overflow-x-auto rounded-2xl border border-slate-200">
                                    <table class="min-w-[620px] w-full text-sm"><thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.12em] text-slate-500"><tr><th class="px-4 py-3">Size</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3">Unit estimate</th><th class="px-4 py-3">Line estimate</th></tr></thead><tbody class="divide-y divide-slate-100">
                                        @foreach($group['sizes'] as $size)
                                            @php($key = $group['id'].':'.$size['code'])
                                            <tr><td class="px-4 py-3 font-black">{{ $size['label'] }}</td><td class="px-4 py-3"><div class="grid h-10 w-32 grid-cols-[36px_1fr_36px] overflow-hidden rounded-xl border border-slate-300"><button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)-1)">−</button><input class="min-w-0 border-0 text-center font-black" type="number" min="0" max="9999" x-model.number="quantities[@js($key)]" @change="changeQuantity(@js($key), quantities[@js($key)])"><button type="button" class="bg-slate-100 font-black" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)+1)">+</button></div></td><td class="px-4 py-3 font-bold" x-text="money(unitPrice() + {{ (float)$size['price_delta'] }})"></td><td class="px-4 py-3 font-bold" x-text="money((unitPrice() + {{ (float)$size['price_delta'] }}) * Number(quantities[@js($key)] || 0))"></td></tr>
                                        @endforeach
                                    </tbody></table>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(!empty($product['artwork_methods']))
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="artwork-method">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">3</span><div><h3 class="text-2xl font-black text-brand-ink">Choose Artwork Method</h3><p class="mt-1 text-sm leading-6 text-slate-500">The admin decides which methods are available for each product.</p></div></div>
                        <div class="p-5 sm:p-6"><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach($product['artwork_methods'] as $method)<button type="button" @click="artworkMethod=@js($method['id']); sync()" :class="artworkMethod === @js($method['id']) ? 'border-brand-red ring-2 ring-red-100' : 'border-slate-200'" class="min-h-[150px] rounded-2xl border-2 bg-white p-4 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-2xl font-black text-brand-blue">{{ $method['icon'] ?: '✦' }}</span><strong class="mt-3 block text-sm">{{ $method['label'] }}</strong><small class="mt-2 block text-xs leading-5 text-slate-500">{{ $method['description'] }}</small>@if($method['price_delta'] != 0)<small class="mt-2 block font-black text-brand-red">{{ $method['price_delta'] > 0 ? '+' : '−' }}${{ number_format(abs($method['price_delta']),2) }}</small>@endif</button>@endforeach</div>
                            @foreach($product['artwork_methods'] as $method)@if($method['requires_upload'])<label x-show="artworkMethod === @js($method['id'])" class="mt-5 block rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-7 text-center"><strong class="block text-sm">Drop artwork here or click to browse</strong><small class="mt-1 block text-xs text-slate-500">PDF, SVG, PNG, JPG or WebP · maximum 15 MB</small><input class="mt-4 text-sm" type="file" name="artwork_file" accept=".pdf,.svg,.png,.jpg,.jpeg,.webp"></label>@endif @endforeach
                        </div>
                    </section>
                @endif

                @if($product['is_customizable'] && $decorationGroups->isNotEmpty())
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="decoration-details">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">4</span><div><h3 class="text-2xl font-black text-brand-ink">Decoration & Print Details</h3><p class="mt-1 text-sm leading-6 text-slate-500">Print positions, methods, proof requirements, and product-specific instructions.</p></div></div>
                        <div class="space-y-6 p-5 sm:p-6">@foreach($decorationGroups as $group)<x-storefront.product.option-group :group="$group" />@endforeach</div>
                    </section>
                @endif

                @if(!empty($product['production_speeds']))
                    <section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="delivery-options">
                        <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">5</span><div><h3 class="text-2xl font-black text-brand-ink">Production & Delivery</h3><p class="mt-1 text-sm leading-6 text-slate-500">Choose an admin-configured production service level.</p></div></div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6"><label class="text-sm font-black text-slate-700">Delivery ZIP code<input class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4" inputmode="numeric" maxlength="10" placeholder="Example: 10001"></label><label class="text-sm font-black text-slate-700">Production speed<select class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4" x-model="productionSpeed" @change="sync()">@foreach($product['production_speeds'] as $speed)<option value="{{ $speed['id'] }}">{{ $speed['label'] }} · {{ $speed['minimum_days'] }}–{{ $speed['maximum_days'] }} days @if($speed['price_delta'] != 0)· +${{ number_format($speed['price_delta'],2) }}/unit @endif</option>@endforeach</select></label></div>
                    </section>
                @endif

                <div class="xl:hidden"><button type="submit" class="btn btn-red w-full py-4">Add Configured Product</button></div>
            </form>

            <aside class="h-fit overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-hero xl:sticky xl:top-36">
                <div class="bg-gradient-to-br from-brand-dark to-brand-navy p-5 text-white"><p class="text-[10px] font-black uppercase tracking-[.16em] text-blue-200">Live estimate</p><h3 class="mt-1 text-2xl font-black">Your Custom Order</h3></div>
                <div class="grid grid-cols-[72px_1fr] gap-3 border-b border-slate-200 p-5"><img src="{{ $product['image'] }}" alt="" class="h-[72px] w-[72px] rounded-xl object-cover"><div><strong class="block text-sm leading-5">{{ $product['title'] }}</strong><small class="mt-1 block text-xs text-slate-500">SKU: {{ $product['sku'] }}</small><small class="mt-1 block text-xs text-slate-500"><span x-text="totalQuantity()"></span> total pieces</small></div></div>
                <div class="max-h-64 space-y-2 overflow-y-auto border-b border-slate-200 p-5 text-xs">
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Selections</span><strong class="text-right" x-text="selectionSummary() || 'No options selected' "></strong></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Sizes</span><strong class="text-right" x-text="sizeSummary() || 'No quantities selected'"></strong></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Artwork</span><strong class="text-right" x-text="artworkLabel()"></strong></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-500">Production</span><strong class="text-right" x-text="speedLabel()"></strong></div>
                </div>
                <div class="space-y-3 p-5 text-sm"><div class="flex justify-between"><span class="text-slate-500">Tier base price</span><strong x-text="money(tierPrice())"></strong></div><div class="flex justify-between"><span class="text-slate-500">Options / unit</span><strong x-text="money(optionSurcharge())"></strong></div><div class="flex justify-between"><span class="text-slate-500">Estimated unit price</span><strong x-text="money(unitPrice())"></strong></div><div class="flex justify-between"><span class="text-slate-500">Quantity</span><strong x-text="totalQuantity()"></strong></div><div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-4"><span class="font-black">Estimated total</span><strong class="text-2xl font-black text-brand-red" x-text="money(totalPrice())"></strong></div></div>
                <div class="px-5 pb-5"><button type="button" class="btn btn-red hidden w-full py-4 xl:flex" @click="$root.querySelector('form').requestSubmit()">Add Configured Product</button><p class="mt-3 text-center text-[10px] leading-4 text-slate-500">Final price, stock, selections, and uploaded files are validated again by the Laravel backend.</p></div>
            </aside>
        </div>
    </div>
</section>
