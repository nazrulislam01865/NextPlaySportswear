@php
    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['title'],
        'image' => $product['gallery'],
        'description' => $product['summary'],
        'sku' => $product['sku'],
        'brand' => [
            '@type' => 'Brand',
            'name' => config('storefront.name'),
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => $product['rating'],
            'reviewCount' => $product['reviews_count'],
        ],
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'USD',
            'price' => $product['base_price'],
            'availability' => 'https://schema.org/InStock',
            'url' => $product['url'],
        ],
    ];
@endphp

<x-layouts.storefront :seo="$seo">
    <script type="application/ld+json">
        @json($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    </script>

    <section class="bg-slate-50 py-6">
        <div class="site-container text-sm font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-red">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('products.index') }}" class="hover:text-brand-red">Products</a>
            <span class="mx-2">/</span>
            <span class="text-brand-ink">{{ $product['short_title'] }}</span>
        </div>
    </section>

    <section class="section-padding bg-white pt-8">
        <div class="site-container grid gap-10 lg:grid-cols-[1fr_1fr]">
            <x-storefront.product.gallery :product="$product" />

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-brand-red">{{ $product['tag'] }}</span>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-brand-blue">{{ $product['sport'] }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-600">SKU: {{ $product['sku'] }}</span>
                </div>

                <h1 class="mt-4 font-display text-5xl font-bold uppercase leading-tight tracking-tight text-brand-ink lg:text-6xl">{{ $product['title'] }}</h1>
                <p class="mt-4 text-lg leading-8 text-slate-600">{{ $product['summary'] }}</p>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <span class="font-display text-4xl font-bold text-brand-red">{{ $product['price'] }}</span>
                    <span class="text-sm font-bold text-amber-500">{{ str_repeat('★', (int) $product['rating']) }}</span>
                    <span class="text-sm font-semibold text-slate-500">{{ $product['reviews_count'] }} reviews</span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($product['features'] as $feature)
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-green-100 text-xs text-green-700">✓</span>
                            <span>{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <x-storefront.product.detail-information :product="$product" />
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <form method="POST" action="{{ route('cart.items.store') }}" class="sm:col-span-1">
                        @csrf
                        <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="design_option" value="Default Team Style">
                        <input type="hidden" name="delivery_preference" value="Standard production">
                        <input type="hidden" name="size_summary" value="Sizes can be confirmed during proof review">
                        <input type="hidden" name="artwork_status" value="Artwork/logo can be sent now or later">
                        <button type="submit" class="btn btn-red w-full">Add to Cart</button>
                    </form>
                    <a href="#customize" class="btn btn-white sm:col-span-1">Customize First</a>
                    <a href="{{ route('quote.request') }}" class="btn btn-light sm:col-span-1">Request Bulk Quote</a>
                </div>
            </div>
        </div>
    </section>

    <section id="customize" class="section-padding bg-slate-50">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_360px]">
            <div class="grid gap-8">
                <x-storefront.product.customizable-options :options="$product['customizable_options']" />
                <x-storefront.product.size-quantity-selector :product="$product" />
                <x-storefront.product.price-table :tiers="$product['price_tiers']" />
                <x-storefront.product.size-chart :chart="$product['size_chart']" />
            </div>

            <aside class="grid h-fit gap-5 lg:sticky lg:top-32">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Order steps</p>
                    <h2 class="mt-1 font-display text-3xl font-bold uppercase text-brand-ink">How to Customize</h2>
                    <div class="mt-5 grid gap-3">
                        @foreach ($product['option_steps'] as $step)
                            <div class="flex gap-3 rounded-2xl bg-slate-50 p-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-navy text-xs font-black text-white">{{ $loop->iteration }}</span>
                                <div>
                                    <h3 class="text-sm font-black text-brand-ink">{{ $step['title'] }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5">
                    <h3 class="text-lg font-black text-brand-navy">Digital Proof Support</h3>
                    <p class="mt-2 text-sm leading-6 text-brand-blue">Artwork, roster, spelling, alignment, color, and placement can be reviewed before production. This is ready for the future order workflow.</p>
                    <form method="POST" action="{{ route('cart.items.store') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="design_option" value="Modern Graphic">
                        <input type="hidden" name="delivery_preference" value="Standard production">
                        <input type="hidden" name="size_summary" value="Confirm size breakdown during proof review">
                        <input type="hidden" name="artwork_status" value="Artwork/logo can be sent now or later">
                        <button type="submit" class="btn btn-red w-full">Add Custom Order to Cart</button>
                    </form>
                    <a href="{{ route('quote.request') }}" class="btn btn-white mt-3 w-full">Send Order Details</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="section-padding bg-white">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card lg:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Product description</p>
                <h2 class="mt-1 font-display text-3xl font-bold uppercase tracking-tight text-brand-ink">Built for Custom Team Orders</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $product['description'] }}</p>
                <p class="mt-4 text-sm leading-7 text-slate-600">Customers can select a design option, send roster details, upload artwork, choose delivery preference, and request a proof before final production.</p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card lg:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">FAQ</p>
                <h2 class="mt-1 font-display text-3xl font-bold uppercase tracking-tight text-brand-ink">Product Questions</h2>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach ($product['faqs'] as $faq)
                        <details class="group py-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-brand-ink">
                                {{ $faq['question'] }}
                                <span class="text-brand-red group-open:rotate-45">+</span>
                            </summary>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding bg-slate-50">
        <div class="site-container">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">More products</p>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">Related Products</h2>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-white">View All Products</a>
            </div>

            <div class="grid-4">
                @foreach ($relatedProducts as $relatedProduct)
                    <x-storefront.product-card :product="$relatedProduct" />
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.storefront>
