@props(['title' => 'Admin'])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} | NextPlay Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-slate-100 text-slate-900"
    x-data="{ sidebarOpen: false }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', sidebarOpen)"
    @keydown.escape.window="sidebarOpen = false"
>
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside
            id="admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex w-[min(86vw,280px)] -translate-x-full flex-col bg-brand-dark text-white shadow-2xl transition-transform duration-200 lg:static lg:w-[280px] lg:translate-x-0 lg:shadow-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            aria-label="Admin navigation"
        >
            <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3 font-black" @click="sidebarOpen = false">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border-2 border-brand-red text-brand-red">✓</span>
                    <span class="min-w-0"><span class="block truncate text-lg">NextPlay</span><span class="block truncate text-[10px] uppercase tracking-[.25em] text-slate-400">Commerce Admin</span></span>
                </a>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-2xl text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Close sidebar">×</button>
            </div>

            <nav class="flex-1 overflow-y-auto overscroll-contain px-3 py-5 text-sm" @click="if ($event.target.closest('a')) sidebarOpen = false">
                <p class="px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Overview</p>
                <x-admin.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="▦">Dashboard</x-admin.sidebar-link>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Catalog</p>
                <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" icon="◇">Products</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')" icon="⌘">Category Tree</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.attributes.index')" :active="request()->routeIs('admin.attributes.*')" icon="◫">Catalog Attributes</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.menus.index')" :active="request()->routeIs('admin.menus.*')" icon="☷">Navigation Menus</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'inventory')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'inventory'" icon="▤">Inventory</x-admin.sidebar-link>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Commerce</p>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'orders')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'orders'" icon="▣">Orders</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'customers')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'customers'" icon="♙">Customers</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'discounts')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'discounts'" icon="%">Discounts & Coupons</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'reviews')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'reviews'" icon="★">Reviews</x-admin.sidebar-link>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Store</p>
                <x-admin.sidebar-link :href="route('admin.homepage-slides.index')" :active="request()->routeIs('admin.homepage-slides.*')" icon="▧">Homepage Slider</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'content')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'content'" icon="✎">Content & Navigation</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'shipping')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'shipping'" icon="➜">Shipping</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'taxes')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'taxes'" icon="§">Taxes</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'payments')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'payments'" icon="$">Payments</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'reports')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'reports'" icon="↗">Reports</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'settings')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'settings'" icon="⚙">Settings</x-admin.sidebar-link>
            </nav>

            <div class="shrink-0 border-t border-white/10 p-4">
                <div class="mb-3 rounded-xl bg-white/5 p-3">
                    <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                    <p class="mt-1 text-[10px] uppercase tracking-[.14em] text-brand-red">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="min-h-11 w-full rounded-xl border border-white/15 px-4 py-2.5 text-left text-sm font-bold hover:bg-white/10">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-20 items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-slate-200 lg:hidden" @click="sidebarOpen = true" aria-label="Open sidebar" aria-controls="admin-sidebar">☰</button>
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-[.18em] text-brand-red">Administration</p>
                        <h1 class="truncate text-lg font-black text-brand-ink sm:text-2xl">{{ $title }}</h1>
                    </div>
                </div>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 shrink-0 items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-brand-navy hover:bg-slate-50 sm:px-4"><span class="hidden sm:inline">View storefront&nbsp;</span>↗</a>
            </header>

            <main class="min-w-0 p-3 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <p class="font-black">Please correct the highlighted information.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
