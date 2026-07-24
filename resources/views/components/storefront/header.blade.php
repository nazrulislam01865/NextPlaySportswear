@php
    $headerCart = $headerCart ?? [
        'items' => [],
        'total_items' => 0,
        'remaining_items' => 0,
        'quantity' => 0,
        'subtotal' => 0,
        'total' => 0,
        'is_empty' => true,
        'checkout_ready' => false,
    ];
    $cartItemCount = (int) ($cartItemCount ?? ($headerCart['quantity'] ?? 0));
    $wishlistItemCount = max(0, (int) ($wishlistItemCount ?? 0));
    $wishlistAuthenticated = (bool) ($wishlistAuthenticated ?? false);

    $headerMenu = collect($storefrontMenus['header'] ?? []);
    $shopMenuItem = $headerMenu->first(function ($item) {
        $label = str($item->label ?? '')->lower()->squish()->toString();
        return $label === 'shop products' || (($item->route_name ?? null) === 'categories.index');
    });
    $shopChildren = $shopMenuItem?->childrenRecursive ?? collect();
    $shopUrl = $shopMenuItem?->resolvedUrl() ?? route('categories.index');

    $homeActive = request()->routeIs('home');
    $shopActive = request()->routeIs('categories.*');
    $productsActive = request()->routeIs('products.*');
    $howActive = request()->routeIs('how-to-order');
    $shippingActive = request()->routeIs('shipping');
    $cartActive = request()->routeIs('cart.*');
    $wishlistActive = request()->routeIs('wishlist.*');
    $contactActive = request()->routeIs('contact') || request()->routeIs('contact.store');
    $trackActive = request()->routeIs('orders.track') || request()->routeIs('orders.track.lookup');

    $storefrontReturnUrl = request()->routeIs('login', 'register') ? null : request()->fullUrl();
    $accountHref = auth('admin')->check()
        ? route('admin.dashboard')
        : (auth('web')->check()
            ? route('account.dashboard')
            : route('login', array_filter(['redirect' => $storefrontReturnUrl])));
    $accountLabel = auth('admin')->check()
        ? 'Admin Dashboard'
        : (auth('web')->check() ? 'My Account' : 'Login');

    $whatsappDigits = preg_replace('/\D+/', '', (string) config('storefront.whatsapp'));
    $whatsappUrl = config('storefront.whatsapp_url') ?: ($whatsappDigits ? 'https://wa.me/'.$whatsappDigits : '#');
    $socialLinks = collect(config('storefront.social', []))->filter(fn ($url) => filled($url) && $url !== '#');
@endphp

<header
    x-data="{ open: false }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    @keydown.escape.window="open = false"
    class="storefront-site-header np-site-header sticky top-0 z-40 bg-white"
    aria-label="Site header"
>
    <div class="np-main-header">
        <div class="np-header-shell np-main-header-inner">
            <a href="{{ route('home') }}" class="np-brand-logo" aria-label="{{ config('storefront.name') }} home" data-header-analytics="header_navigation_click" data-header-analytics-label="logo_home">
                <span class="np-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 64 64" width="64" height="64" fill="none">
                        <path d="M18 20h28c3.2 0 5.8 2.6 5.8 5.8v24.4c0 3.2-2.6 5.8-5.8 5.8H18c-3.2 0-5.8-2.6-5.8-5.8V25.8C12.2 22.6 14.8 20 18 20Z" stroke="currentColor" stroke-width="4.8" />
                        <path d="M22.5 20v-4.2C22.5 9.9 26.5 6 32 6s9.5 3.9 9.5 9.8V20" stroke="currentColor" stroke-width="4.8" stroke-linecap="round" />
                        <path d="m22.5 36 7.1 7.5 13.9-17" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="np-brand-text"><span>NEXTPLAY</span> <strong>SPORTSWEAR</strong></span>
            </a>

            <form
                method="GET"
                action="{{ route('products.index') }}"
                role="search"
                aria-label="Product search"
                class="np-header-search"
                data-storefront-search-suggest
                data-suggest-url="{{ route('products.suggestions') }}"
                data-header-search
                data-header-analytics="header_search_submit"
                data-header-analytics-label="product_search"
            >
                <span class="np-header-search-icon" aria-hidden="true">
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                </span>
                <label for="site-product-search" class="sr-only">Search products and categories</label>
                <input
                    id="site-product-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search jerseys, uniforms, caps, bags..."
                    autocomplete="off"
                    class="np-header-search-input"
                >
                <div
                    class="storefront-search-suggestions np-search-suggestions hidden"
                    data-storefront-search-suggestions
                    role="listbox"
                    aria-label="Product suggestions"
                ></div>
            </form>

            <div class="np-header-actions">
                <a href="{{ route('products.index') }}" class="np-header-btn np-header-btn-light np-shop-now-btn" data-header-analytics="header_cta_click" data-header-analytics-label="shop_now">Shop Now</a>
                <a href="{{ $accountHref }}" class="np-header-btn np-header-btn-light np-account-action" data-header-analytics="header_cta_click" data-header-analytics-label="{{ auth('web')->check() || auth('admin')->check() ? 'account' : 'login' }}">{{ $accountLabel }}</a>

                <a
                    href="{{ route('wishlist.index') }}"
                    class="np-icon-action np-wishlist-action {{ $wishlistActive ? 'is-active' : '' }}"
                    aria-label="Wishlist, {{ $wishlistItemCount }} item{{ $wishlistItemCount === 1 ? '' : 's' }}"
                    data-wishlist-header-link
                    data-wishlist-authenticated="{{ $wishlistAuthenticated ? '1' : '0' }}"
                    data-wishlist-initial-count="{{ $wishlistItemCount }}"
                    data-wishlist-storage-key="nextplay:guest-wishlist:v1"
                    data-wishlist-status-endpoint="{{ route('wishlist.status') }}"
                    data-header-analytics="header_cta_click"
                    data-header-analytics-label="wishlist"
                >
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                    </svg>
                    <span class="np-wishlist-count-badge" data-wishlist-count>{{ $wishlistItemCount > 99 ? '99+' : $wishlistItemCount }}</span>
                </a>

                <div class="storefront-cart-hover np-cart-action-wrap">
                    <a
                        href="{{ route('cart.index') }}"
                        class="storefront-cart-button np-cart-action"
                        aria-label="Shopping cart, {{ $cartItemCount }} item{{ $cartItemCount === 1 ? '' : 's' }}"
                        data-header-analytics="header_cta_click"
                        data-header-analytics-label="cart"
                    >
                        <svg width="31" height="31" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="20" r="1.5"></circle>
                            <circle cx="19" cy="20" r="1.5"></circle>
                            <path d="M3 4h2l2.7 11.1a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L21 8H6"></path>
                        </svg>
                        <span class="storefront-cart-count-badge np-cart-count-badge">{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>
                    </a>

                    <div class="storefront-cart-preview absolute right-0 top-[calc(100%+0.7rem)] z-[80] hidden w-[360px] max-w-[calc(100vw-1.25rem)] rounded-[22px] border border-slate-200 bg-white text-left shadow-2xl" role="region" aria-label="Cart preview">
                        <span class="storefront-cart-preview-arrow" aria-hidden="true"></span>
                        <div class="border-b border-slate-100 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-black uppercase tracking-[.14em] text-brand-ink">Cart Preview</p>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">{{ $cartItemCount }} item{{ $cartItemCount === 1 ? '' : 's' }}</span>
                            </div>
                        </div>

                        @if (! empty($headerCart['items']))
                            <div class="max-h-[340px] overflow-y-auto p-3">
                                @foreach ($headerCart['items'] as $item)
                                    @php
                                        $previewProduct = (array) ($item['product'] ?? []);
                                        $previewTitle = $previewProduct['short_title'] ?? $previewProduct['title'] ?? 'Configured product';
                                        $previewImage = $previewProduct['image'] ?? null;
                                        $previewUrl = $previewProduct['url'] ?? route('cart.index');
                                    @endphp
                                    <a href="{{ $previewUrl }}" class="storefront-mini-cart-item flex gap-3 rounded-2xl p-2 transition hover:bg-slate-50">
                                        <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                                            @if ($previewImage)
                                                <img src="{{ $previewImage }}" alt="{{ $previewProduct['alt'] ?? $previewTitle }}" class="h-full w-full object-contain p-1" loading="lazy">
                                            @else
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-300" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 16 5-5 4 4 2-2 7 7"></path></svg>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-black leading-5 text-brand-ink">{{ $previewTitle }}</span>
                                            <span class="mt-1 block text-xs font-bold text-slate-500">Qty {{ (int) ($item['quantity'] ?? 0) }} · ${{ number_format((float) ($item['unit_price'] ?? 0), 2) }} each</span>
                                            <span class="mt-1 block text-sm font-black text-brand-red">${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</span>
                                        </span>
                                    </a>
                                @endforeach

                                @if (($headerCart['remaining_items'] ?? 0) > 0)
                                    <p class="px-2 pb-1 pt-2 text-xs font-black text-slate-500">+ {{ $headerCart['remaining_items'] }} more cart item{{ (int) $headerCart['remaining_items'] === 1 ? '' : 's' }}</p>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 p-4">
                                <div class="mb-3 flex items-center justify-between gap-4">
                                    <span class="text-sm font-bold text-slate-500">Estimated total</span>
                                    <span class="text-lg font-black text-brand-ink">${{ number_format((float) ($headerCart['total'] ?? 0), 2) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('cart.index') }}" class="btn btn-white w-full text-xs">View Cart</a>
                                    <a href="{{ route('checkout.index') }}" class="btn btn-red w-full text-xs {{ ($headerCart['checkout_ready'] ?? false) ? '' : 'pointer-events-none opacity-50' }}">Checkout</a>
                                </div>
                            </div>
                        @else
                            <div class="p-5 text-center">
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-50 text-slate-400">
                                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"></circle><circle cx="19" cy="20" r="1.5"></circle><path d="M3 4h2l2.7 11.1a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L21 8H6"></path></svg>
                                </div>
                                <p class="mt-3 text-base font-black text-brand-ink">Your cart is empty</p>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-500">Add products to see them here instantly.</p>
                                <a href="{{ route('products.index') }}" class="btn btn-red mt-4 w-full text-xs">Start Shopping</a>
                            </div>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    class="np-mobile-menu-toggle"
                    :aria-label="open ? 'Close menu' : 'Open menu'"
                    :aria-expanded="open.toString()"
                    aria-controls="storefront-mobile-menu"
                    @click="open = !open"
                >
                    <span x-show="!open" class="np-menu-bars" aria-hidden="true"></span>
                    <span x-cloak x-show="open" class="np-menu-close" aria-hidden="true">×</span>
                </button>
            </div>
        </div>
    </div>

    <div class="storefront-nav-row np-category-nav-row">
        <div class="np-header-shell np-category-nav-shell">
            <nav class="storefront-main-nav np-category-nav" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="np-category-link {{ $homeActive ? 'is-active' : '' }}" @if($homeActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="home">Home</a>

                <div class="np-menu-item np-category-menu-item {{ $shopActive ? 'is-active' : '' }}">
                    <a
                        href="{{ $shopUrl }}"
                        class="np-category-link np-menu-link {{ $shopActive ? 'is-active' : '' }}"
                        @if($shopActive) aria-current="page" @endif
                        @if($shopChildren->isNotEmpty()) aria-haspopup="true" aria-expanded="false" @endif
                        data-header-analytics="header_navigation_click"
                        data-header-analytics-label="shop_products"
                    >
                        <span>Shop Products</span>
                        <svg class="np-category-caret" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                    </a>

                    @if($shopChildren->isNotEmpty())
                        <div class="np-menu-panel np-shop-panel" role="group" aria-label="Shop Products submenu">
                            <a class="np-menu-view-all" href="{{ $shopUrl }}" data-header-analytics="header_navigation_click" data-header-analytics-label="shop_products_view_all">
                                <span>View all Shop Products</span>
                                <span aria-hidden="true">→</span>
                            </a>

                            <div class="np-mega-grid">
                                @foreach($shopChildren as $child)
                                    <div class="np-mega-card">
                                        <a
                                            href="{{ $child->resolvedUrl() }}"
                                            target="{{ $child->target }}"
                                            @if($child->target === '_blank') rel="noopener noreferrer" @endif
                                            class="np-mega-title np-mega-title-with-icon"
                                            data-header-analytics="header_navigation_click"
                                            data-header-analytics-label="category_{{ str($child->label)->slug('_') }}"
                                        >
                                            <span class="np-mega-category-icon" aria-hidden="true">
                                                <x-storefront.category-icon :label="$child->label" :icon-url="$child->icon_url" />
                                            </span>
                                            <span>{{ $child->label }}</span>
                                        </a>

                                        @if($child->childrenRecursive->isNotEmpty())
                                            <div class="np-mega-sublist">
                                                @foreach($child->childrenRecursive as $grandchild)
                                                    <div class="np-mega-subitem">
                                                        <a
                                                            href="{{ $grandchild->resolvedUrl() }}"
                                                            target="{{ $grandchild->target }}"
                                                            @if($grandchild->target === '_blank') rel="noopener noreferrer" @endif
                                                            class="np-mega-subtitle"
                                                            data-header-analytics="header_navigation_click"
                                                            data-header-analytics-label="category_{{ str($grandchild->label)->slug('_') }}"
                                                        >{{ $grandchild->label }}</a>

                                                        @if($grandchild->childrenRecursive->isNotEmpty())
                                                            <div class="np-mega-leaf-list">
                                                                @foreach($grandchild->childrenRecursive as $leaf)
                                                                    <a
                                                                        href="{{ $leaf->resolvedUrl() }}"
                                                                        target="{{ $leaf->target }}"
                                                                        @if($leaf->target === '_blank') rel="noopener noreferrer" @endif
                                                                        class="np-mega-leaf"
                                                                        data-header-analytics="header_navigation_click"
                                                                        data-header-analytics-label="category_{{ str($leaf->label)->slug('_') }}"
                                                                    >{{ $leaf->label }}</a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <a href="{{ route('products.index') }}" class="np-category-link {{ $productsActive ? 'is-active' : '' }}" @if($productsActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="all_products">All Products</a>
                <a href="{{ route('how-to-order') }}" class="np-category-link {{ $howActive ? 'is-active' : '' }}" @if($howActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="how_it_works">How It Works</a>
            </nav>

            <a href="{{ route('shipping') }}" class="np-usa-shipping {{ $shippingActive ? 'is-active' : '' }}" @if($shippingActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="usa_shipping">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7h11v10H3z" />
                    <path d="M14 10h3.6l2.4 3v4h-6" />
                    <circle cx="7" cy="18" r="2" />
                    <circle cx="17" cy="18" r="2" />
                </svg>
                <span>USA SHIPPING</span>
            </a>
        </div>

        <nav
            id="storefront-mobile-menu"
            x-cloak
            x-show="open"
            x-transition
            @click.outside="open=false"
            class="np-mobile-menu"
            aria-label="Mobile navigation"
        >
            <div class="np-header-shell">
                <form
                    method="GET"
                    action="{{ route('products.index') }}"
                    role="search"
                    class="np-mobile-search"
                    data-storefront-search-suggest
                    data-suggest-url="{{ route('products.suggestions') }}"
                    data-header-search
                    data-header-analytics="header_search_submit"
                    data-header-analytics-label="mobile_product_search"
                >
                    <label for="mobile-product-search" class="sr-only">Search products and categories</label>
                    <div class="np-mobile-search-row">
                        <input id="mobile-product-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search jerseys, uniforms, caps, bags..." autocomplete="off" class="np-header-search-input">
                        <button class="np-header-btn np-header-btn-red" type="submit">Search</button>
                    </div>
                    <div
                        class="storefront-search-suggestions np-search-suggestions hidden"
                        data-storefront-search-suggestions
                        role="listbox"
                        aria-label="Product suggestions"
                    ></div>
                </form>

                <div class="np-mobile-nav-list" @click="if ($event.target.closest('a')) open = false">
                    <a href="{{ route('home') }}" class="np-mobile-nav-link {{ $homeActive ? 'is-active' : '' }}" @if($homeActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="mobile_home">Home</a>

                    <details class="np-mobile-nav-details" {{ $shopActive ? 'open' : '' }}>
                        <summary class="np-mobile-nav-summary {{ $shopActive ? 'is-active' : '' }}">
                            <span>Shop Products</span>
                            <span aria-hidden="true">+</span>
                        </summary>
                        <div class="np-mobile-submenu">
                            <a href="{{ $shopUrl }}" class="np-mobile-nav-link np-mobile-view-all" data-header-analytics="header_navigation_click" data-header-analytics-label="mobile_shop_products_view_all">View all Shop Products</a>
                            @foreach($shopChildren as $child)
                                <x-storefront.menu.mobile-item :item="$child" :depth="1" />
                            @endforeach
                        </div>
                    </details>

                    <a href="{{ route('products.index') }}" class="np-mobile-nav-link {{ $productsActive ? 'is-active' : '' }}" @if($productsActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="mobile_all_products">All Products</a>
                    <a href="{{ route('how-to-order') }}" class="np-mobile-nav-link {{ $howActive ? 'is-active' : '' }}" @if($howActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="mobile_how_it_works">How It Works</a>
                    <a href="{{ route('shipping') }}" class="np-mobile-nav-link np-mobile-shipping-link {{ $shippingActive ? 'is-active' : '' }}" @if($shippingActive) aria-current="page" @endif data-header-analytics="header_navigation_click" data-header-analytics-label="mobile_usa_shipping">USA Shipping</a>
                </div>

                <div class="np-mobile-utility-list" @click="if ($event.target.closest('a')) open = false">
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="np-mobile-nav-link" data-header-analytics="header_cta_click" data-header-analytics-label="mobile_whatsapp">WhatsApp Us</a>
                    <a href="{{ $accountHref }}" class="np-mobile-nav-link" data-header-analytics="header_cta_click" data-header-analytics-label="mobile_account">{{ $accountLabel }}</a>
                    @if(auth('admin')->check())
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="np-mobile-nav-link np-mobile-nav-button">Admin Logout</button></form>
                    @elseif(auth('web')->check())
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="np-mobile-nav-link np-mobile-nav-button">Logout</button></form>
                    @else
                        <a href="{{ route('register') }}" class="np-mobile-nav-link" data-header-analytics="header_cta_click" data-header-analytics-label="mobile_create_account">Create Account</a>
                    @endif
                </div>

                {{-- Mobile quick actions stay near the bottom, directly above Follow us. --}}
                <div class="np-mobile-priority-actions" @click="if ($event.target.closest('a')) open = false">
                    <a href="{{ route('wishlist.index') }}" class="np-mobile-action-card {{ $wishlistActive ? 'is-active' : '' }}" @if($wishlistActive) aria-current="page" @endif data-wishlist-header-link data-wishlist-authenticated="{{ $wishlistAuthenticated ? '1' : '0' }}" data-wishlist-initial-count="{{ $wishlistItemCount }}" data-wishlist-storage-key="nextplay:guest-wishlist:v1" data-wishlist-status-endpoint="{{ route('wishlist.status') }}" data-header-analytics="header_cta_click" data-header-analytics-label="mobile_wishlist">
                        <span class="np-mobile-action-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                            </svg>
                        </span>
                        <span class="np-mobile-action-copy">
                            <span class="np-mobile-action-title">Wishlist</span>
                            <span class="np-mobile-action-meta"><span data-wishlist-count>{{ $wishlistItemCount }}</span> saved</span>
                        </span>
                        <span class="np-mobile-action-arrow" aria-hidden="true">›</span>
                    </a>

                    <a href="{{ route('cart.index') }}" class="np-mobile-action-card {{ $cartActive ? 'is-active' : '' }}" @if($cartActive) aria-current="page" @endif data-header-analytics="header_cta_click" data-header-analytics-label="mobile_cart">
                        <span class="np-mobile-action-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="20" r="1.5"></circle>
                                <circle cx="19" cy="20" r="1.5"></circle>
                                <path d="M3 4h2l2.7 11.1a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L21 8H6"></path>
                            </svg>
                        </span>
                        <span class="np-mobile-action-copy">
                            <span class="np-mobile-action-title">Cart</span>
                            <span class="np-mobile-action-meta">{{ $cartItemCount }} item{{ $cartItemCount === 1 ? '' : 's' }}</span>
                        </span>
                        <span class="np-mobile-action-arrow" aria-hidden="true">›</span>
                    </a>

                    <a href="{{ route('contact') }}" class="np-mobile-action-card {{ $contactActive ? 'is-active' : '' }}" @if($contactActive) aria-current="page" @endif data-header-analytics="header_cta_click" data-header-analytics-label="mobile_contact">
                        <span class="np-mobile-action-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
                                <path d="M8 9h8M8 13h5"></path>
                            </svg>
                        </span>
                        <span class="np-mobile-action-copy">
                            <span class="np-mobile-action-title">Contact Us</span>
                            <span class="np-mobile-action-meta">Talk to our team</span>
                        </span>
                        <span class="np-mobile-action-arrow" aria-hidden="true">›</span>
                    </a>

                    <a href="{{ route('orders.track') }}" class="np-mobile-action-card {{ $trackActive ? 'is-active' : '' }}" @if($trackActive) aria-current="page" @endif data-header-analytics="header_cta_click" data-header-analytics-label="mobile_track_order">
                        <span class="np-mobile-action-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7h11v10H3z"></path>
                                <path d="M14 10h3.6l2.4 3v4h-6"></path>
                                <circle cx="7" cy="18" r="2"></circle>
                                <circle cx="17" cy="18" r="2"></circle>
                            </svg>
                        </span>
                        <span class="np-mobile-action-copy">
                            <span class="np-mobile-action-title">Track Order</span>
                            <span class="np-mobile-action-meta">Check order status</span>
                        </span>
                        <span class="np-mobile-action-arrow" aria-hidden="true">›</span>
                    </a>
                </div>


                @if($socialLinks->isNotEmpty())
                    <div class="np-mobile-social-wrap">
                        <span>Follow us</span>
                        <div class="np-social-links">
                            @foreach($socialLinks as $network => $url)
                                @php($networkLabel = str($network)->headline()->toString())
                                <a href="{{ $url }}" class="np-social-link" target="_blank" rel="noopener noreferrer" aria-label="Follow NextPlay Sportswear on {{ $networkLabel }}" data-header-analytics="header_social_click" data-header-analytics-label="mobile_{{ $network }}">
                                    <span aria-hidden="true">{{ strtoupper(substr((string) $network, 0, 1)) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </nav>
    </div>
</header>
