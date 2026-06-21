<header
    x-data="{ open: false }"
    class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm"
    aria-label="Site header"
>
    <div class="site-container grid grid-cols-[auto_1fr_auto] items-center gap-4 py-4 lg:gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3 font-display text-xl font-bold uppercase leading-none tracking-tight text-brand-ink lg:text-2xl">
            <span class="grid h-8 w-8 place-items-center rounded-lg border-[3px] border-brand-red text-brand-red">
                □
            </span>

            <span>
                NextPlay <span class="text-brand-red">Sportswear</span>
            </span>
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

            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search jerseys, uniforms, caps, bags..."
                class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-4 text-sm text-slate-700 outline-none focus:border-brand-blue"
            >
        </form>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-white hidden lg:inline-flex">
                Shop Now
            </a>

            <a href="{{ route('quote.request') }}" class="btn btn-red hidden lg:inline-flex">
                Request Quote
            </a>

            @auth
                <a href="{{ route('account.dashboard') }}" class="btn btn-white hidden xl:inline-flex">
                    My Account
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-white hidden xl:inline-flex">
                    Login
                </a>
            @endauth

            <a href="{{ route('cart.index') }}" class="btn btn-light relative">
                Cart
                @if (($cartItemCount ?? 0) > 0)
                    <span class="ml-1 rounded-full bg-brand-red px-2 py-0.5 text-xs font-black text-white">{{ $cartItemCount }}</span>
                @endif
            </a>

            <button
                type="button"
                class="grid h-11 w-11 place-items-center rounded-xl border border-slate-300 bg-white lg:hidden"
                aria-label="Open menu"
                :aria-expanded="open.toString()"
                @click="open = !open"
            >
                <span class="relative block h-0.5 w-5 bg-slate-800 before:absolute before:left-0 before:top-[-6px] before:h-0.5 before:w-5 before:bg-slate-800 before:content-[''] after:absolute after:left-0 after:top-[6px] after:h-0.5 after:w-5 after:bg-slate-800 after:content-['']"></span>
            </button>
        </div>
    </div>

    <div class="border-t border-slate-200">
        <nav
            class="site-container hidden items-center justify-between gap-4 overflow-x-auto py-3 text-sm font-extrabold text-slate-600 lg:flex"
            aria-label="Main navigation"
        >
            <x-storefront.nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('products.index') }}">Shop by Sport</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('products.index') }}">Team Uniforms</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('products.index') }}">Custom Jerseys</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('products.index') }}">Apparel</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('products.index') }}">Accessories</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('quote.request') }}">Bulk Quote</x-storefront.nav-link>
            <x-storefront.nav-link href="{{ route('orders.track') }}">Track Order</x-storefront.nav-link>
        </nav>

        <nav
            x-cloak
            x-show="open"
            x-transition
            class="site-container grid gap-1 py-3 text-sm font-extrabold text-slate-700 lg:hidden"
            aria-label="Mobile navigation"
        >
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('home') }}">Home</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('products.index') }}">Shop by Sport</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('products.index') }}">Team Uniforms</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('products.index') }}">Custom Jerseys</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('products.index') }}">Apparel</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('quote.request') }}">Bulk Quote</a>
            <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('orders.track') }}">Track Order</a>
            @auth
                <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('account.dashboard') }}">My Account</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-2 py-2 text-left hover:bg-slate-100">Logout</button>
                </form>
            @else
                <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('login') }}">Login</a>
                <a class="rounded-lg px-2 py-2 hover:bg-slate-100" href="{{ route('register') }}">Create Account</a>
            @endauth
        </nav>
    </div>
</header>
