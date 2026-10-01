<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    <script src="{{ asset('js/product-image-viewer.js') }}?v=20261001-preview-visible-v4"></script>

    <div class="np-product-page" x-data="productImageViewerV4()" x-on:open-product-image.window="open($event.detail)">
        <span class="sr-only" data-product-view-track data-product-id="{{ $product['id'] ?? '' }}" aria-hidden="true"></span>

        <nav class="np-product-breadcrumb" aria-label="Breadcrumb">
            <div class="site-container">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                @if($product['category_slug'])
                    <a href="{{ route('categories.show', $product['category_slug']) }}">{{ $product['category'] }}</a>
                    <span aria-hidden="true">/</span>
                @endif
                @if($product['subcategory_slug'])
                    <a href="{{ route('categories.show', $product['subcategory_slug']) }}">{{ $product['subcategory'] }}</a>
                    <span aria-hidden="true">/</span>
                @endif
                <span aria-current="page">{{ $product['title'] }}</span>
            </div>
        </nav>

        <x-storefront.product.builder
            :product="$product"
            :edit-item="$cartEditItem"
            :social="$productSocial"
        />

        <x-storefront.product.details :product="$product" />
        <x-storefront.product.reviews :product="$product" />
        <x-storefront.product.related-products :products="$relatedProducts" />

        <template x-teleport="body">
            <div
                x-cloak
                x-show="imageOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="np-product-image-preview-overlay fixed inset-0 flex items-center justify-center overflow-hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Expanded product image"
                x-on:click.self="close()"
                x-on:keydown.escape.window="if (imageOpen) close()"
                x-on:keydown.tab.prevent="$refs.previewClose.focus()"
            >
                <div
                    class="np-product-image-preview-frame"
                    x-ref="previewFrame"
                >
                    <button
                        type="button"
                        class="np-product-image-preview-close"
                        x-ref="previewClose"
                        x-on:click="close()"
                        aria-label="Close image preview"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>

                    <img
                        :src="previewSrc || null"
                        :alt="image?.alt || 'Product image'"
                        class="np-product-image-preview"
                        x-ref="previewImage"
                        decoding="async"
                        x-on:error="close()"
                    >
                </div>
            </div>
        </template>
    </div>
</x-layouts.storefront>
