@props(['title' => 'Admin', 'eyebrow' => null, 'subtitle' => null, 'compactHeader' => false, 'storefrontUrl' => null])
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
    <div class="min-h-screen lg:grid lg:grid-cols-[276px_minmax(0,1fr)] lg:items-start">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside
            id="admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-[min(86vw,276px)] -translate-x-full flex-col overflow-hidden bg-brand-dark text-white shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:max-h-screen lg:w-[276px] lg:translate-x-0 lg:self-start lg:shadow-none"
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

            <nav data-admin-sidebar-nav class="admin-sidebar-nav min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5 text-sm" @click="if ($event.target.closest('a')) sidebarOpen = false">
                <p class="px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Main Menu</p>
                <x-admin.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="▦">Dashboard</x-admin.sidebar-link>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Catalog</p>
                <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" icon="◇">Products</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')" icon="⌘">Categories</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.attributes.index')" :active="request()->routeIs('admin.attributes.*')" icon="◫">Catalog Attributes</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.menus.index')" :active="request()->routeIs('admin.menus.*')" icon="☷">Navigation Menus</x-admin.sidebar-link>
                <x-admin.sidebar-link :href="route('admin.modules.show', 'inventory')" :active="request()->routeIs('admin.modules.show') && request()->route('module') === 'inventory'" icon="▤">Inventory</x-admin.sidebar-link>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Master Data</p>
                @php
                    $jerseyMenuTypes = [
                        ['number' => '1.1.1', 'value' => \App\Enums\JerseyCustomizationType::Color->value, 'label' => 'Color'],
                        ['number' => '1.1.2', 'value' => \App\Enums\JerseyCustomizationType::NeckAndCollar->value, 'label' => 'Neck & Collar'],
                        ['number' => '1.1.3', 'value' => \App\Enums\JerseyCustomizationType::Fabric->value, 'label' => 'Fabric'],
                        ['number' => '1.1.4', 'value' => \App\Enums\JerseyCustomizationType::SleevesAndCuffs->value, 'label' => 'Sleeves & Cuffs'],
                        ['number' => '1.1.5', 'value' => \App\Enums\JerseyCustomizationType::JerseyStyle->value, 'label' => 'Jersey Style'],
                    ];
                    $isJerseyCustomizationActive = request()->routeIs('admin.jersey-customization-options.*');
                    $activeJerseyType = request()->route('type');
                @endphp
                <x-admin.sidebar-group
                    label="Master Data"
                    icon="◈"
                    :active="$isJerseyCustomizationActive || request()->routeIs('admin.size-option-groups.*')"
                >
                    <div class="space-y-1" x-data="{ jerseyOpen: @js($isJerseyCustomizationActive) }">
                        <button
                            type="button"
                            class="flex min-h-10 w-full min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-black transition"
                            :class="jerseyOpen || @js($isJerseyCustomizationActive) ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/10 hover:text-white'"
                            @click="jerseyOpen = ! jerseyOpen"
                            :aria-expanded="jerseyOpen.toString()"
                        >
                            <span class="text-[10px] text-slate-500">1.1</span>
                            <span class="min-w-0 flex-1 truncate">Jersey Customization</span>
                            <span class="text-[10px] transition-transform" :class="jerseyOpen ? 'rotate-180' : ''">⌄</span>
                        </button>

                        <div x-cloak x-show="jerseyOpen" class="space-y-1 pl-4">
                            @foreach($jerseyMenuTypes as $jerseyMenuType)
                                <a
                                    href="{{ route('admin.jersey-customization-options.type', $jerseyMenuType['value']) }}"
                                    @if($isJerseyCustomizationActive && $activeJerseyType === $jerseyMenuType['value']) data-sidebar-active="true" @endif
                                    @class([
                                        'flex min-h-9 min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-bold transition',
                                        'bg-brand-red text-white' => $isJerseyCustomizationActive && $activeJerseyType === $jerseyMenuType['value'],
                                        'text-slate-400 hover:bg-white/10 hover:text-white' => ! ($isJerseyCustomizationActive && $activeJerseyType === $jerseyMenuType['value']),
                                    ])
                                >
                                    <span class="shrink-0 text-[10px] opacity-80">{{ $jerseyMenuType['number'] }}</span>
                                    <span class="min-w-0 truncate">{{ $jerseyMenuType['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <x-admin.sidebar-sub-link
                        :href="route('admin.size-option-groups.index')"
                        :active="request()->routeIs('admin.size-option-groups.*')"
                    >1.2 Size Options</x-admin.sidebar-sub-link>
                </x-admin.sidebar-group>

                <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Commerce</p>
                @if(auth()->user()->canManageOrders())
                    <x-admin.sidebar-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" icon="▣">Orders</x-admin.sidebar-link>
                    <x-admin.sidebar-link :href="route('admin.returns.index')" :active="request()->routeIs('admin.returns.*')" icon="↶">Returns & Exchanges</x-admin.sidebar-link>
                @endif
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
                <p class="mb-3 px-1 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Manage Store</p>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="mb-3 block rounded-xl border border-white/15 bg-white/5 p-3 transition hover:bg-white/10">
                    <p class="truncate text-sm font-black text-white">NextPlay Athletic Store</p>
                    <p class="mt-1 truncate text-xs font-semibold text-slate-300">nextplay.com ↗</p>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="min-h-11 w-full rounded-xl border border-white/15 px-4 py-2.5 text-left text-sm font-bold hover:bg-white/10">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header @class([
                'sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-5 backdrop-blur sm:px-7 lg:px-10',
                'min-h-[92px] lg:min-h-[112px]' => $compactHeader,
                'min-h-[104px] lg:min-h-[126px]' => ! $compactHeader,
            ])>
                <div class="flex min-w-0 items-center gap-4">
                    <button type="button" class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl lg:hidden" @click="sidebarOpen = true" aria-label="Open sidebar" aria-controls="admin-sidebar">☰</button>
                    <div class="min-w-0">
                        <p class="text-[11px] font-black uppercase tracking-[.28em] text-brand-red">{{ $eyebrow ?? 'Administration' }}</p>
                        <h1 @class([
                            'truncate font-black leading-tight text-brand-ink',
                            'text-2xl sm:text-3xl lg:text-[32px]' => $compactHeader,
                            'text-2xl sm:text-4xl lg:text-[40px]' => ! $compactHeader,
                        ])>{{ $title }}</h1>
                        @if($subtitle)
                            <p class="mt-1 max-w-2xl truncate text-sm font-semibold text-slate-500 sm:text-base">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ $storefrontUrl ?: route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 shrink-0 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-brand-blue shadow-sm transition hover:bg-slate-50 sm:min-h-12 sm:px-5"><span class="hidden sm:inline">View storefront&nbsp;</span>↗</a>
            </header>

            <main class="min-w-0 p-4 sm:p-7 lg:p-10">
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
