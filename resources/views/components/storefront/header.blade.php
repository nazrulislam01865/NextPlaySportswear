<header
    x-data="{ open: false }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
    @keydown.escape.window="open = false"
    class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm"
    aria-label="Site header"
>
    <div class="site-container flex min-h-[72px] items-center justify-between gap-2 py-3 lg:grid lg:grid-cols-[auto_minmax(220px,1fr)_auto] lg:gap-6 lg:py-4">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 font-display text-lg font-bold uppercase leading-none tracking-tight text-brand-ink sm:gap-3 sm:text-xl lg:text-2xl" aria-label="{{ config('storefront.name') }} home">
            <span class="relative grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[9px] border-[3px] border-brand-red text-brand-red">
                ✓
                <span class="absolute -top-2 h-1.5 w-3.5 rounded-t-lg border-2 border-b-0 border-current" aria-hidden="true"></span>
            </span>
            <span class="truncate">NextPlay <span class="hidden sm:inline text-brand-red">Sportswear</span></span>
        </a>

        <form
            method="GET"
            action="{{ route('products.index') }}"
            role="search"
            aria-label="Product search"
            class="relative mx-auto hidden w-full max-w-[450px] lg:block"
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
                class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-4 text-sm text-slate-700 outline-none focus:border-brand-blue"
            >
        </form>

        <div class="flex shrink-0 items-center justify-end gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-white hidden lg:inline-flex">Shop Now</a>
            <a href="{{ route('quote.request') }}" class="btn btn-red hidden lg:inline-flex">Request Quote</a>

            @if(auth('admin')->check())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-white hidden xl:inline-flex">Admin Dashboard</a>
            @elseif(auth('web')->check())
                <a href="{{ route('account.dashboard') }}" class="btn btn-white hidden xl:inline-flex">My Account</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-white hidden xl:inline-flex">Login</a>
            @endif

            <a href="{{ route('cart.index') }}" class="relative inline-flex min-h-11 items-center justify-center gap-1 rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-extrabold text-slate-900" aria-label="Shopping cart{{ ($cartItemCount ?? 0) > 0 ? ', ' . $cartItemCount . ' items' : '' }}">
                <span class="cart-label">Cart</span>
                <svg class="sm:hidden" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="20" r="1"></circle><circle cx="19" cy="20" r="1"></circle><path d="M3 4h2l2.7 11.1a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 2-1.6L21 8H6"></path></svg>
                @if (($cartItemCount ?? 0) > 0)
                    <span class="rounded-full bg-brand-red px-2 py-0.5 text-xs font-black text-white">{{ $cartItemCount }}</span>
                @endif
            </a>

            <button
                type="button"
                class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-slate-300 bg-white lg:hidden"
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

    <div class="border-t border-slate-200">
        <nav class="site-container hidden flex-wrap items-center gap-x-3 gap-y-1 py-2 text-sm font-extrabold text-slate-600 lg:flex" aria-label="Main navigation">
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
            <form method="GET" action="{{ route('products.index') }}" role="search" class="mb-4">
                <label for="mobile-product-search" class="sr-only">Search products</label>
                <div class="flex gap-2">
                    <input id="mobile-product-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search products..." class="h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm outline-none focus:border-brand-blue">
                    <button class="btn btn-red shrink-0 px-4" type="submit">Search</button>
                </div>
            </form>

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
