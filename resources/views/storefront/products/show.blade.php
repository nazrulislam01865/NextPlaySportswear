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
            'social' => $productSocial,
        ];

    @endphp

    <style>
        /* Product-detail pages keep a little more breathing room than the
           wide catalog/home layout while preserving a balanced two-column view. */
        @media (min-width: 1024px) {
            .storefront-clean-ui .np-product-page .site-container {
                width: min(1680px, calc(100% - clamp(88px, 10vw, 220px))) !important;
                max-width: none !important;
                margin-inline: auto !important;
            }
        }

        /* Render product media at its original aspect ratio. The gallery no longer
           inserts a square canvas, inner padding, or a white presentation frame. */
        .np-product-page .np-product-gallery-column {
            width: 100%;
            max-width: 620px;
        }

        .np-product-page .product-gallery-frame {
            width: 100% !important;
            max-width: 620px !important;
            height: auto !important;
            aspect-ratio: auto !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
            padding: 0 !important;
        }

        .np-product-page .product-gallery-frame .np-product-gallery-main,
        .np-product-page .product-gallery-frame .np-product-gallery-slide {
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            aspect-ratio: auto !important;
            background: transparent !important;
            overflow: visible !important;
            padding: 0 !important;
        }

        .product-gallery-frame .np-product-gallery-main > button:not(.np-product-gallery-slide) {
            height: 44px;
            width: 44px;
            aspect-ratio: 1 / 1;
        }

        .np-product-page .product-gallery-frame .np-product-gallery-image {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: none !important;
            aspect-ratio: auto !important;
            object-fit: contain !important;
            object-position: center !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        .np-product-page .np-product-gallery-thumbnails {
            width: 100%;
            max-width: 620px;
            align-items: flex-start;
        }

        .np-product-page .np-product-gallery-thumb {
            flex: 0 0 104px !important;
            width: 104px !important;
            min-width: 104px !important;
            max-width: 104px !important;
            height: auto !important;
            aspect-ratio: auto !important;
            border: 0 !important;
            border-radius: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .np-product-page .np-product-gallery-thumb[aria-current="true"] {
            opacity: 1;
        }

        .np-product-page .np-product-gallery-thumb:not([aria-current="true"]) {
            opacity: .72;
        }

        .np-product-page .np-product-gallery-thumb:hover,
        .np-product-page .np-product-gallery-thumb:focus-visible {
            opacity: 1;
            outline: 2px solid transparent !important;
            box-shadow: none !important;
        }

        .np-product-page .np-product-gallery-thumb-image {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            aspect-ratio: auto !important;
            object-fit: contain !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* The enlarged preview has no presentation panel at all. The image is
           placed directly on the dark viewport overlay so no white wrapper,
           padding, frame, radius, or shadow can appear around it. */
        .np-product-image-preview {
            display: block !important;
            width: auto !important;
            max-width: 100vw !important;
            height: auto !important;
            max-height: 100vh !important;
            object-fit: contain !important;
            object-position: center !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
        }

        .np-product-image-preview-close {
            position: fixed !important;
            top: 16px !important;
            right: 16px !important;
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

        .np-product-wishlist-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 20;
            display: grid;
            width: 3rem;
            height: 3rem;
            place-items: center;
            border: 1px solid #dbe3ee;
            border-radius: 9999px;
            background: rgba(255, 255, 255, .96);
            color: #15345d;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
            backdrop-filter: blur(8px);
        }

        .np-product-wishlist-button:hover {
            border-color: #ef1b34;
            color: #ef1b34;
            transform: translateY(-2px);
        }

        .np-product-wishlist-button:focus-visible,
        .np-product-share-menu button:focus-visible {
            outline: 3px solid rgba(24, 92, 170, .28);
            outline-offset: 3px;
        }

        .np-product-share-wrapper {
            position: relative;
        }

        .np-product-share-menu {
            position: absolute;
            top: calc(100% + .65rem);
            left: 0;
            z-index: 40;
            width: 16rem;
            overflow: hidden;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            background: #fff;
            padding: .5rem;
            box-shadow: 0 24px 55px rgba(15, 23, 42, .2);
        }

        .np-product-share-menu button {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .75rem;
            border-radius: .75rem;
            padding: .75rem;
            text-align: left;
            font-size: .875rem;
            font-weight: 700;
            color: #334155;
            transition: background-color .18s ease, color .18s ease;
        }

        .np-product-share-menu button:hover {
            background: #f1f5f9;
            color: #15345d;
        }

        .np-product-wishlist-button[aria-pressed="true"] svg {
            color: #ef1b34;
            transform: scale(1.1);
        }

        @media (min-width: 640px) {
            .np-product-wishlist-button {
                top: 1.25rem;
                right: 1.25rem;
            }
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

    </style>

    <div class="np-product-page" x-data="{ imageOpen: false, image: null }" @open-product-image.window="image = $event.detail; imageOpen = true">
        <span class="sr-only" data-product-view-track data-product-id="{{ $product['id'] ?? '' }}" aria-hidden="true"></span>
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
                <div class="np-product-hero-grid grid min-w-0 gap-8 lg:items-start xl:gap-10">
                    <x-storefront.product.gallery :gallery="$product['gallery']" :badge="$product['tag']" :social="$productSocial" />

                    <article class="min-w-0 lg:pt-1">
                        <h1 class="np-product-title max-w-3xl font-black text-slate-950">
                            {{ $product['title'] }}
                        </h1>

                        <x-storefront.product.purchase-signals :product="$product" />

                        <div class="mt-4 h-px w-full bg-slate-200" aria-hidden="true"></div>

                        @if(filled($product['summary']))
                            <p class="np-product-summary mt-4 max-w-3xl text-slate-700">
                                {{ $product['summary'] }}
                            </p>
                        @endif

                        @if(filled($product['sku'] ?? null))
                            <div class="mt-4 inline-flex max-w-full items-center gap-2.5 rounded-full border border-slate-200 bg-slate-50 px-3.5 py-1.5 text-[11px] font-black uppercase tracking-[.16em] text-slate-400 sm:px-4">
                                <span>SKU</span>
                                <strong class="break-all font-black tracking-[.18em] text-slate-900">{{ $product['sku'] }}</strong>
                            </div>
                        @endif

                        <x-storefront.product.detail-information :product="$product" />
                    </article>
                </div>

                <div class="mt-8 min-w-0 sm:mt-10 lg:mt-12">
                    <x-storefront.product.price-table :table="$product['price_table']" :fabric-tables="$product['fabric_price_tables'] ?? []" embedded />
                </div>
            </div>
        </section>

        <x-storefront.product.builder :product="$product" :edit-item="$cartEditItem" />
        <x-storefront.product.details :product="$product" />
        <x-storefront.product.reviews :product="$product" />
        <x-storefront.product.related-products :products="$relatedProducts" />

        <div
            x-cloak
            x-show="imageOpen"
            x-transition.opacity
            class="fixed inset-0 z-[80] flex items-center justify-center overflow-hidden bg-slate-950/90"
            style="margin:0;padding:0;border:0;background-color:rgba(2,6,23,.90);"
            @click.self="imageOpen=false"
            @keydown.escape.window="imageOpen=false"
        >
            <button
                type="button"
                class="np-product-image-preview-close z-10 grid h-11 w-11 place-items-center rounded-full bg-white/95 text-2xl shadow-card backdrop-blur"
                @click="imageOpen=false"
                aria-label="Close image preview"
            >×</button>
            <img
                :src="image?.url"
                :alt="image?.alt"
                class="np-product-image-preview"
                style="display:block;width:auto;height:auto;max-width:100vw;max-height:100vh;margin:0;padding:0;border:0;border-radius:0;outline:0;box-shadow:none;background:transparent;"
            >
        </div>
    </div>
</x-layouts.storefront>
