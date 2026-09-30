<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    <script src="{{ asset('js/product-image-viewer.js') }}?v=20260930-product-detail-prototype"></script>

    <div class="np-product-page" x-data="productImageViewer()" @open-product-image.window="open($event.detail)">
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

        <div
            x-cloak
            x-show="imageOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="np-product-image-preview-overlay fixed inset-0 z-[80] flex items-center justify-center overflow-hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Expanded product image"
            @click.self="close()"
            @keydown.escape.window="close()"
        >
            <button
                type="button"
                class="np-product-image-preview-close"
                @click="close()"
                aria-label="Close image preview"
            >×</button>

            <span
                x-show="previewLoading"
                class="np-product-image-preview-loader"
                role="status"
                aria-label="Preparing image preview"
            ></span>

            <img
                x-cloak
                x-show="!previewLoading && previewSrc"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-[.985]"
                x-transition:enter-end="opacity-100 scale-100"
                :src="previewSrc"
                :alt="image?.alt || 'Product image'"
                class="np-product-image-preview"
                decoding="async"
                @load="previewLoading = false"
            >
        </div>
    </div>
</x-layouts.storefront>
