<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    @php
        $heroConfig = [
            'title' => $product['title'],
            'currency' => $product['currency'] ?? 'USD',
            'base_price' => $product['base_price'],
            'minimum_quantity' => $product['minimum_quantity'] ?? 1,
            'gallery' => $product['gallery'],
            'option_groups' => [],
            'size_groups' => [],
            'artwork_methods' => [],
            'production_speeds' => [],
            'price_tiers' => $product['price_tiers'] ?? [],
        ];

    @endphp

    <style>
        .product-gallery-frame {
            background:
                linear-gradient(90deg, #1877df 0 56%, #55d4d4 56% 76%, #e7efef 76% 100%) top/100% 10px no-repeat,
                linear-gradient(#1877df, #1877df) left/10px 100% no-repeat,
                linear-gradient(90deg, #1877df 0 56%, #55d4d4 56% 76%, #e7efef 76% 100%) bottom/100% 10px no-repeat;
            padding-top: 10px;
            padding-left: 10px;
            padding-bottom: 10px;
        }



        .np-product-page .np-product-title {
            font-size: clamp(28px, 3.1vw, 38px);
            line-height: 1.13;
            letter-spacing: -0.028em;
        }

        .np-product-page .np-product-summary {
            font-size: 15px;
            line-height: 1.68;
        }

        .np-product-page .np-detail-information table {
            font-size: 14px;
            line-height: 1.45;
        }

        .np-product-page .np-detail-information thead tr {
            font-size: 12px;
            letter-spacing: .035em;
        }

        .np-product-page .np-detail-information th,
        .np-product-page .np-detail-information td {
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .np-product-page .np-product-meta {
            font-size: 13px;
            line-height: 1.65;
        }

        .np-product-page .product-rich-content,
        .np-product-page .product-rich-content p,
        .np-product-page .product-rich-content li,
        .np-product-page .product-rich-content td,
        .np-product-page .product-rich-content th {
            font-size: 14px;
            line-height: 1.7;
        }

        .np-product-page .product-rich-content h2 {
            font-size: 22px;
            line-height: 1.25;
        }

        .np-product-page .product-rich-content h3 {
            font-size: 18px;
            line-height: 1.3;
        }

        .np-product-page .np-selected-size-chart table {
            font-size: 13px;
        }

        .np-product-page .np-selected-size-chart th,
        .np-product-page .np-selected-size-chart td {
            padding: 9px 12px;
        }

        @media (max-width: 640px) {
            .np-product-page .np-product-title {
                font-size: 28px;
                line-height: 1.15;
            }

            .np-product-page .np-product-summary,
            .np-product-page .np-detail-information table,
            .np-product-page .product-rich-content,
            .np-product-page .product-rich-content p,
            .np-product-page .product-rich-content li,
            .np-product-page .product-rich-content td,
            .np-product-page .product-rich-content th {
                font-size: 13px;
            }
        }

        @media (max-width: 640px) {
            .product-gallery-frame {
                background:
                    linear-gradient(90deg, #1877df 0 56%, #55d4d4 56% 76%, #e7efef 76% 100%) top/100% 7px no-repeat,
                    linear-gradient(#1877df, #1877df) left/7px 100% no-repeat,
                    linear-gradient(90deg, #1877df 0 56%, #55d4d4 56% 76%, #e7efef 76% 100%) bottom/100% 7px no-repeat;
                padding-top: 7px;
                padding-left: 7px;
                padding-bottom: 7px;
            }
        }
    </style>

    <div class="np-product-page" x-data="{ imageOpen: false, image: null }" @open-product-image.window="image = $event.detail; imageOpen = true">
        <nav class="border-b border-slate-200 bg-slate-50" aria-label="Breadcrumb">
            <div class="site-container flex flex-wrap items-center gap-2 py-4 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-brand-red">Home</a><span>/</span>
                @if($product['category_slug'])
                    <a href="{{ route('categories.show', $product['category_slug']) }}" class="hover:text-brand-red">{{ $product['category'] }}</a><span>/</span>
                @endif
                @if($product['subcategory_slug'])
                    <a href="{{ route('categories.show', $product['subcategory_slug']) }}" class="hover:text-brand-red">{{ $product['subcategory'] }}</a><span>/</span>
                @endif
                <span class="font-bold text-brand-ink">{{ $product['title'] }}</span>
            </div>
        </nav>

        <section class="py-8 sm:py-12 lg:py-14">
            <div
                class="site-container min-w-0"
                x-data="productBuilder(@js($heroConfig))"
                x-init="init()"
            >
                <div class="grid min-w-0 gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(420px,.94fr)] lg:items-start xl:gap-14">
                    <x-storefront.product.gallery :gallery="$product['gallery']" :badge="$product['tag']" />

                    <article class="min-w-0 lg:pt-1">
                        <h1 class="np-product-title max-w-3xl font-black text-slate-950">
                            {{ $product['title'] }}
                        </h1>

                        <div class="mt-5 h-1 w-10 bg-slate-200" aria-hidden="true"></div>

                        @if(filled($product['summary']))
                            <p class="np-product-summary mt-5 max-w-3xl text-slate-700">
                                {{ $product['summary'] }}
                            </p>
                        @endif

                        <x-storefront.product.detail-information :product="$product" />
                    </article>
                </div>

                <div class="mt-8 min-w-0 sm:mt-10 lg:mt-12">
                    <x-storefront.product.price-table :table="$product['price_table']" embedded />
                </div>
            </div>
        </section>

        <x-storefront.product.builder :product="$product" />
        <x-storefront.product.details :product="$product" />

        @if(!empty($relatedProducts))
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
                        @foreach($relatedProducts as $relatedProduct)
                            <x-storefront.product-card :product="$relatedProduct" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <div
            x-cloak
            x-show="imageOpen"
            x-transition.opacity
            class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/80 p-4"
            @click.self="imageOpen=false"
            @keydown.escape.window="imageOpen=false"
        >
            <div class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white">
                <button
                    type="button"
                    class="absolute right-4 top-4 z-10 grid h-11 w-11 place-items-center rounded-full bg-white text-2xl shadow-card"
                    @click="imageOpen=false"
                    aria-label="Close image preview"
                >×</button>
                <img :src="image?.url" :alt="image?.alt" class="max-h-[92vh] w-full bg-white object-contain">
            </div>
        </div>
    </div>
</x-layouts.storefront>
