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
@endphp

<header
    x-data="{ open: false }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    @keydown.escape.window="open = false"
    class="storefront-site-header sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm"
    aria-label="Site header"
>
    <div class="site-container storefront-header-main flex min-h-[64px] items-center justify-between gap-2 py-2.5 sm:min-h-[72px] sm:py-3 lg:grid lg:grid-cols-[auto_minmax(220px,1fr)_auto] lg:gap-6 lg:py-4">
        <a href="{{ route('home') }}" class="storefront-logo flex min-w-0 items-center gap-2 font-display text-lg font-bold uppercase leading-none tracking-tight text-brand-ink sm:gap-3 sm:text-xl lg:text-2xl" aria-label="{{ config('storefront.name') }} home">
            <span class="storefront-logo-mark relative grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[9px] border-[3px] border-brand-red text-brand-red">
                ✓
                <span class="absolute -top-2 h-1.5 w-3.5 rounded-t-lg border-2 border-b-0 border-current" aria-hidden="true"></span>
            </span>
            <span class="storefront-logo-text truncate">NextPlay <span class="hidden sm:inline text-brand-red">Sportswear</span></span>
        </a>

        <form
            method="GET"
            action="{{ route('products.index') }}"
            role="search"
            aria-label="Product search"
            class="relative mx-auto hidden w-full max-w-[450px] lg:block"
            data-storefront-search-suggest
            data-suggest-url="{{ route('products.suggestions') }}"
        >
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
            </span>
            <label for="site-product-search" class="sr-only">Search products</label>
            <input
                id="site-product-search"
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search jerseys, uniforms, caps, bags..."
                autocomplete="off"
                class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-4 text-sm text-slate-700 outline-none focus:border-brand-blue"
            >
            <div
                class="storefront-search-suggestions absolute left-0 right-0 top-[calc(100%+0.5rem)] z-[70] hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                data-storefront-search-suggestions
                role="listbox"
                aria-label="Product suggestions"
            ></div>
        </form>

        <div class="storefront-header-actions flex shrink-0 items-center justify-end gap-1.5 sm:gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-white hidden xl:inline-flex">Shop Now</a>
            <a href="{{ route('quote.request') }}" class="btn btn-red hidden xl:inline-flex">Request Quote</a>

            @if(auth('admin')->check())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-white hidden xl:inline-flex">Admin Dashboard</a>
            @elseif(auth('web')->check())
                <a href="{{ route('account.dashboard') }}" class="btn btn-white hidden xl:inline-flex">My Account</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-white hidden xl:inline-flex">Login</a>
            @endif

            <div class="storefront-cart-hover relative">
                <a
                    href="{{ route('cart.index') }}"
                    class="storefront-cart-icon-button storefront-cart-button relative inline-grid h-10 w-10 place-items-center rounded-xl border border-slate-300 bg-slate-50 text-slate-900 transition hover:border-brand-blue hover:bg-blue-50 hover:text-brand-blue sm:h-11 sm:w-11"
                    aria-label="Shopping cart{{ $cartItemCount > 0 ? ', ' . $cartItemCount . ' items' : '' }}"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="20" r="1.5"></circle>
                        <circle cx="19" cy="20" r="1.5"></circle>
                        <path d="M3 4h2l2.7 11.1a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L21 8H6"></path>
                    </svg>
                    @if ($cartItemCount > 0)
                        <span class="storefront-cart-count-badge absolute -right-1.5 -top-1.5 min-w-[22px] rounded-full bg-brand-red px-1.5 py-0.5 text-center text-[11px] font-black leading-4 text-white shadow-sm">{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>
                    @endif
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
                class="storefront-menu-button grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-300 bg-white lg:hidden sm:h-11 sm:w-11"
                :aria-label="open ? 'Close menu' : 'Open menu'"
                :aria-expanded="open.toString()"
                aria-controls="storefront-mobile-menu"
                @click="open = !open"
            >
                <span x-show="!open" class="relative block h-0.5 w-5 bg-slate-800 before:absolute before:left-0 before:top-[-6px] before:h-0.5 before:w-5 before:bg-slate-800 before:content-[''] after:absolute after:left-0 after:top-[6px] after:h-0.5 after:w-5 after:bg-slate-800 after:content-['']"></span>
                <span x-cloak x-show="open" class="text-2xl leading-none" aria-hidden="true">×</span>
            </button>
        </div>
    </div>

    <div class="storefront-nav-row">
        <nav class="storefront-main-nav site-container hidden lg:flex" aria-label="Main navigation">
            @forelse(($storefrontMenus['header'] ?? collect()) as $item)
                <x-storefront.menu.desktop-item :item="$item" :align="$loop->index >= 4 ? 'right' : 'left'" />
            @empty
                <x-storefront.nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-storefront.nav-link>
                <x-storefront.nav-link href="{{ route('categories.index') }}">Shop Categories</x-storefront.nav-link>
                <x-storefront.nav-link href="{{ route('products.index') }}">All Products</x-storefront.nav-link>
                <x-storefront.nav-link href="{{ route('quote.request') }}">Bulk Quote</x-storefront.nav-link>
            @endforelse
        </nav>

        <nav
            id="storefront-mobile-menu"
            x-cloak
            x-show="open"
            x-transition
            @click.outside="open=false"
            class="site-container max-h-[calc(100dvh-74px)] overflow-y-auto overscroll-contain py-4 text-sm text-slate-700 lg:hidden"
            aria-label="Mobile navigation"
        >
            <form
                method="GET"
                action="{{ route('products.index') }}"
                role="search"
                class="relative mb-4"
                data-storefront-search-suggest
                data-suggest-url="{{ route('products.suggestions') }}"
            >
                <label for="mobile-product-search" class="sr-only">Search products</label>
                <div class="flex gap-2">
                    <input id="mobile-product-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search products..." autocomplete="off" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm outline-none focus:border-brand-blue">
                    <button class="btn btn-red shrink-0 px-4" type="submit">Search</button>
                </div>
                <div
                    class="storefront-search-suggestions absolute left-0 right-0 top-[calc(100%+0.5rem)] z-[70] hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    data-storefront-search-suggestions
                    role="listbox"
                    aria-label="Product suggestions"
                ></div>
            </form>

            <div class="mb-4 grid grid-cols-2 gap-2" @click="if ($event.target.closest('a')) open = false">
                <a href="{{ route('products.index') }}" class="btn btn-light w-full text-xs">Shop Now</a>
                <a href="{{ route('quote.request') }}" class="btn btn-red w-full text-xs">Request Quote</a>
            </div>

            <div @click="if ($event.target.closest('a')) open = false">
                @forelse(($storefrontMenus['header'] ?? collect()) as $item)
                    <x-storefront.menu.mobile-item :item="$item" />
                @empty
                    <a class="block rounded-lg px-3 py-3 font-bold hover:bg-slate-100" href="{{ route('categories.index') }}">Shop Categories</a>
                    <a class="block rounded-lg px-3 py-3 font-bold hover:bg-slate-100" href="{{ route('products.index') }}">All Products</a>
                @endforelse
            </div>

            <div class="mt-3 border-t border-slate-200 pt-3" @click="if ($event.target.closest('a')) open = false">
                <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('orders.track') }}">Track Order</a>
                <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('faq') }}">Help Center</a>
                <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('contact') }}">Contact Us</a>
                @if(auth('admin')->check())
                    <a class="block rounded-lg px-3 py-3 font-bold text-brand-blue hover:bg-slate-100" href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="w-full rounded-lg px-3 py-3 text-left hover:bg-slate-100">Admin Logout</button></form>
                @elseif(auth('web')->check())
                    <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('account.dashboard') }}">My Account</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-3 text-left hover:bg-slate-100">Logout</button></form>
                @else
                    <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('login') }}">Login</a>
                    <a class="block rounded-lg px-3 py-3 hover:bg-slate-100" href="{{ route('register') }}">Create Account</a>
                @endif
            </div>
        </nav>
    </div>
</header>


