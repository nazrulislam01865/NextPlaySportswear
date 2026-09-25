<x-layouts.storefront :seo="$seo">
    <section
        class="np-wishlist-page"
        data-wishlist-page
        data-authenticated="{{ $isAuthenticatedCustomer ? '1' : '0' }}"
        data-storage-key="{{ $guestStorageKey }}"
        data-products-endpoint="{{ $guestProductsEndpoint }}"
        data-login-url="{{ $loginUrl }}"
        data-sort="{{ $sort }}"
    >
        <div class="site-container">
            <header class="np-wishlist-toolbar">
                <div class="np-wishlist-heading-line">
                    <h1 class="font-display">My Wishlist</h1>
                    <span class="np-wishlist-count" aria-live="polite">
                        <span data-wishlist-page-count>{{ $items->count() }}</span>
                        <span data-wishlist-page-count-label>{{ $items->count() === 1 ? 'item' : 'items' }}</span>
                    </span>
                </div>

                <div class="np-wishlist-sort-wrap">
                    <label for="wishlist-sort" class="sr-only">Sort wishlist</label>
                    <select id="wishlist-sort" class="np-wishlist-sort" data-wishlist-sort>
                        <option value="recent" @selected($sort === 'recent')>Sort: Recently saved</option>
                        <option value="oldest" @selected($sort === 'oldest')>Sort: Oldest saved</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Sort: Price low to high</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Sort: Price high to low</option>
                        <option value="name_asc" @selected($sort === 'name_asc')>Sort: Name A-Z</option>
                    </select>
                    <svg class="np-wishlist-sort-chevron" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 8 4 4 4-4"></path>
                    </svg>
                </div>
            </header>

            @unless($isAuthenticatedCustomer)
                <div class="np-wishlist-signin-note">
                    <div class="np-wishlist-signin-copy">
                        <span class="np-wishlist-info-icon" aria-hidden="true">i</span>
                        <p>Sign in to keep your wishlist across devices.</p>
                    </div>
                    <a href="{{ $loginUrl }}">Sign In</a>
                </div>
            @endunless

            <div
                data-wishlist-loading
                class="np-wishlist-loading {{ $isAuthenticatedCustomer ? 'hidden' : '' }}"
                role="status"
                aria-live="polite"
            >
                Loading your saved products…
            </div>

            <div class="np-wishlist-grid np-product-listing-grid np-product-listing-grid--three" data-wishlist-items>
                @foreach($items as $item)
                    <x-storefront.wishlist-card :item="$item" />
                @endforeach
            </div>

            <div
                data-wishlist-empty
                class="np-wishlist-empty {{ $isAuthenticatedCustomer && $items->isEmpty() ? '' : 'hidden' }}"
            >
                <div class="np-wishlist-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                    </svg>
                </div>
                <h2>Your wishlist is empty</h2>
                <p>Save products with the heart button and they will appear here.</p>
                <a href="{{ route('products.index') }}" class="btn btn-navy">Browse Products</a>
            </div>

            <a
                href="{{ route('products.index') }}"
                class="np-wishlist-continue {{ $items->isEmpty() ? 'hidden' : '' }}"
                data-wishlist-continue
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m11 18-6-6 6-6"></path>
                </svg>
                Continue Shopping
            </a>
        </div>

        <template data-wishlist-guest-template>
            <x-storefront.wishlist-card guest />
        </template>
    </section>
</x-layouts.storefront>
