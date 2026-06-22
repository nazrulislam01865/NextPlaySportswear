<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    @php
        $heroConfig = [
            'title' => $product['title'], 'currency' => $product['currency'] ?? 'USD',
            'base_price' => $product['base_price'], 'minimum_quantity' => $product['minimum_quantity'] ?? 1,
            'gallery' => $product['gallery'], 'option_groups' => [], 'size_groups' => [],
            'artwork_methods' => [], 'production_speeds' => [], 'price_tiers' => $product['price_tiers'] ?? [],
        ];
    @endphp
    <div x-data="{ imageOpen: false, image: null }" @open-product-image.window="image = $event.detail; imageOpen = true">
        <nav class="border-b border-slate-200 bg-slate-50" aria-label="Breadcrumb">
            <div class="site-container flex flex-wrap items-center gap-2 py-4 text-xs text-slate-500">
                <a href="{{ route('home') }}">Home</a><span>/</span>
                @if($product['category_slug'])<a href="{{ route('categories.show',$product['category_slug']) }}">{{ $product['category'] }}</a><span>/</span>@endif
                @if($product['subcategory_slug'])<a href="{{ route('categories.show',$product['subcategory_slug']) }}">{{ $product['subcategory'] }}</a><span>/</span>@endif
                <span class="font-bold text-brand-ink">{{ $product['title'] }}</span>
            </div>
        </nav>

        <section class="py-10 sm:py-14">
            <div class="site-container grid gap-10 lg:grid-cols-[minmax(0,1.03fr)_minmax(390px,.97fr)] lg:items-start" x-data="productBuilder(@js($heroConfig))" x-init="init()">
                <x-storefront.product.gallery :gallery="$product['gallery']" :badge="$product['tag'] ?: 'Product'" />

                <article class="min-w-0">
                    <div class="flex flex-wrap gap-2">
                        @if($product['tag'])<span class="rounded-full bg-red-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.12em] text-brand-red">{{ $product['tag'] }}</span>@endif
                        @if($product['category'])<span class="rounded-full bg-blue-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.12em] text-brand-blue">{{ $product['category'] }}</span>@endif
                        @if($product['subcategory'])<span class="rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.12em] text-slate-600">{{ $product['subcategory'] }}</span>@endif
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-[.12em] text-slate-600">SKU: {{ $product['sku'] }}</span>
                    </div>
                    <h1 class="mt-5 font-display text-5xl font-bold uppercase leading-[.98] tracking-tight text-brand-ink sm:text-6xl">{{ $product['title'] }}</h1>
                    @if($product['reviews_count'] > 0)<div class="mt-4 flex items-center gap-2 text-sm"><span class="text-amber-500">★★★★★</span><strong>{{ number_format($product['rating'],1) }}</strong><span class="text-slate-400">{{ $product['reviews_count'] }} verified reviews</span></div>@endif
                    <p class="mt-5 text-base leading-8 text-slate-600">{{ $product['summary'] }}</p>
                    <div class="mt-6 flex flex-wrap items-end gap-3"><span class="text-sm font-bold text-slate-500">Starting at</span><strong class="text-4xl font-black tracking-tight text-brand-red">{{ $product['currency'] }} {{ number_format((float)collect($product['price_tiers'])->min('unit') ?: $product['base_price'],2) }}</strong><span class="pb-1 text-xs text-slate-500">per item at applicable quantity tier</span></div>
                    @if($product['compare_at_price'])<p class="mt-2 text-sm text-slate-400 line-through">Compare at {{ $product['currency'] }} {{ number_format($product['compare_at_price'],2) }}</p>@endif

                    @if(!empty($product['features']))<div class="mt-7 grid gap-3 sm:grid-cols-2">@foreach($product['features'] as $feature)<div class="flex gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm font-bold leading-6 text-slate-700"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">✓</span><span>{{ $feature }}</span></div>@endforeach</div>@endif

                    @if(!empty($product['detail_information']))<div class="mt-7 overflow-hidden rounded-2xl border border-slate-200"><div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4"><strong>Product information</strong><span class="text-xs font-bold text-brand-blue">Admin controlled</span></div><dl class="grid sm:grid-cols-2">@foreach(array_slice($product['detail_information'],0,6,true) as $label=>$value)<div class="border-b border-slate-100 px-5 py-4 odd:sm:border-r"><dt class="text-[10px] font-black uppercase tracking-[.12em] text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-black">{{ $value }}</dd></div>@endforeach</dl></div>@endif

                    <div class="mt-7 grid gap-3 sm:grid-cols-2"><a href="#configure-product" class="btn btn-red py-4">Start Customizing ↓</a><a href="{{ route('quote.request') }}" class="btn btn-white py-4">Request Bulk Quote</a></div>
                    <div class="mt-4 flex flex-wrap justify-between gap-3 rounded-2xl bg-blue-50 p-4 text-xs font-bold text-brand-blue"><span>✓ Free artwork review</span><span>✓ Proof before production</span><span>✓ Secure checkout</span></div>
                </article>
            </div>
        </section>

        <x-storefront.product.price-table :table="$product['price_table']" />
        <x-storefront.product.builder :product="$product" />
        <x-storefront.product.details :product="$product" />

        @if(!empty($relatedProducts))
            <section class="section-padding bg-slate-50">
                <div class="site-container"><div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">More products</p><h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">Related Products</h2></div><a href="{{ route('products.index') }}" class="btn btn-white">View All Products</a></div><div class="grid-4">@foreach($relatedProducts as $relatedProduct)<x-storefront.product-card :product="$relatedProduct" />@endforeach</div></div>
            </section>
        @endif

        <div x-cloak x-show="imageOpen" x-transition.opacity class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/80 p-4" @click.self="imageOpen=false" @keydown.escape.window="imageOpen=false">
            <div class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white"><button type="button" class="absolute right-4 top-4 z-10 grid h-11 w-11 place-items-center rounded-full bg-white text-2xl shadow-card" @click="imageOpen=false">×</button><img :src="image?.url" :alt="image?.alt" class="max-h-[92vh] w-full object-contain"></div>
        </div>
    </div>
</x-layouts.storefront>
