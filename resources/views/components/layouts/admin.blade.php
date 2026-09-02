@props(['title' => 'Admin', 'eyebrow' => null, 'subtitle' => null, 'compactHeader' => false, 'storefrontUrl' => null])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} | NextPlay Admin</title>
    <x-admin.sidebar-prepaint-state />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @php
        $adminPusherEnabled = filled(config('services.pusher.key'))
            && filled(config('services.pusher.secret'))
            && filled(config('services.pusher.app_id'))
            && filled(config('services.pusher.cluster'));
        $adminNotificationsJsVersion = file_exists(public_path('js/admin-notifications.js'))
            ? filemtime(public_path('js/admin-notifications.js'))
            : time();
    @endphp
</head>
<body
    @class([
        'admin-clean-ui bg-slate-100 text-slate-900',
        'admin-ui-reference' => request()->routeIs('admin.dashboard') || request()->routeIs('admin.products.*'),
        'admin-ui-minimalize' => ! request()->routeIs('admin.dashboard') && ! request()->routeIs('admin.products.*'),
    ])
    x-data="{ sidebarOpen: false }"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', sidebarOpen)"
    @keydown.escape.window="sidebarOpen = false"
>
    <a class="admin-skip-link" href="#admin-main-content">Skip to main content</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[var(--admin-sidebar-width,256px)_minmax(0,1fr)] lg:items-start" data-admin-shell>
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

        <aside
            id="admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-[min(86vw,var(--admin-sidebar-width,256px))] -translate-x-full flex-col overflow-hidden bg-brand-dark text-white shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:max-h-screen lg:w-[var(--admin-sidebar-width,256px)] lg:translate-x-0 lg:self-start lg:shadow-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            aria-label="Admin navigation"
        >
            <div class="flex h-[72px] shrink-0 items-center justify-between border-b border-white/10 px-4">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3 font-black" @click="sidebarOpen = false">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border-2 border-brand-red text-brand-red">✓</span>
                    <span class="min-w-0"><span class="block truncate text-lg">NextPlay</span><span class="block truncate text-[10px] uppercase tracking-[.25em] text-slate-400">Commerce Admin</span></span>
                </a>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-2xl text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Close sidebar">×</button>
            </div>

            <nav data-admin-sidebar-nav class="admin-sidebar-nav min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-4 text-sm" @click="if ($event.target.closest('a')) sidebarOpen = false">
                @php
                    $adminUser = auth('admin')->user();
                    $canAdmin = static fn (string $permission): bool => (bool) ($adminUser?->canAdmin($permission) ?? false);
                @endphp

                <p class="px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Main Menu</p>
                @if($canAdmin('dashboard.view'))
                    <x-admin.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="▦">Dashboard</x-admin.sidebar-link>
                @endif

                @if($canAdmin('products.view') || $canAdmin('categories.view') || $canAdmin('attributes.view') || $canAdmin('menus.view'))
                    <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Catalog</p>
                    @if($canAdmin('products.view'))
                        <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" icon="◇">Products</x-admin.sidebar-link>
                        <x-admin.sidebar-group
                            label="Media"
                            icon="▧"
                            :active="request()->routeIs('admin.media-library.*')"
                        >
                            <x-admin.sidebar-sub-link
                                :href="route('admin.media-library.index')"
                                :active="request()->routeIs('admin.media-library.index')"
                            >Gallery</x-admin.sidebar-sub-link>
                            <x-admin.sidebar-sub-link
                                :href="route('admin.media-library.upload')"
                                :active="request()->routeIs('admin.media-library.upload')"
                            >Upload Media File</x-admin.sidebar-sub-link>
                        </x-admin.sidebar-group>
                    @endif
                    @if($canAdmin('categories.view'))
                        <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')" icon="⌘">Categories</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('attributes.view'))
                        <x-admin.sidebar-link :href="route('admin.attributes.index')" :active="request()->routeIs('admin.attributes.*')" icon="◫">Catalog Attributes</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('menus.view'))
                        <x-admin.sidebar-link :href="route('admin.menus.index')" :active="request()->routeIs('admin.menus.*')" icon="☷">Navigation Menus</x-admin.sidebar-link>
                    @endif
                @endif

                @if($canAdmin('customization.view') || $canAdmin('shipping.view'))
                    <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Master Data</p>
                    @php
                        $customizationMenuGroups = \App\Enums\JerseyCustomizationType::menuGroups();
                        $primaryCustomizationMenuGroups = array_filter(
                            $customizationMenuGroups,
                            static fn (array $group): bool => version_compare($group['number'], '1.19', '<')
                        );
                        $isCustomizationActive = request()->routeIs('admin.jersey-customization-options.*');
                        $isSizeOptionActive = request()->routeIs('admin.size-option-groups.*');
                        $activeCustomizationType = request()->route('type');
                        $activeCustomizationOption = request()->route('jerseyCustomizationOption');

                        if (! $activeCustomizationType && $activeCustomizationOption instanceof \App\Models\JerseyCustomizationOption) {
                            $activeCustomizationType = $activeCustomizationOption->type instanceof \App\Enums\JerseyCustomizationType
                                ? $activeCustomizationOption->type->value
                                : (string) $activeCustomizationOption->type;
                        }

                        if (! $activeCustomizationType && old('type')) {
                            $activeCustomizationType = old('type');
                        }

                        $activeCustomizationTypeEnum = \App\Enums\JerseyCustomizationType::tryFrom((string) $activeCustomizationType);
                        $activeCustomizationGroup = $activeCustomizationTypeEnum?->group();
                        $activeSizeCustomizationGroup = request()->query('customization', 'jersey');
                        $trainingVestMenuGroups = \App\Enums\TrainingVestCustomizationType::menuGroups();
                        $trainingVestExtensionGroup = $customizationMenuGroups['training_vest'] ?? null;
                        $isTrainingVestExtensionActive = $isCustomizationActive && $activeCustomizationGroup === 'training_vest';
                        $afterTrainingVestCustomizationGroups = \App\Support\ProductCustomizationMenuRegistry::afterTrainingVestGroups($customizationMenuGroups);
                        $trailingMasterDataNumbers = \App\Support\ProductCustomizationMenuRegistry::trailingMasterDataNumbers();
                        $isWorldCupCustomizationActive = request()->routeIs('admin.world-cup-customization-options.*');
                        $activeWorldCupCustomizationType = request()->route('type');
                        $activeWorldCupCustomizationOption = request()->route('worldCupCustomizationOption');
                        if (! $activeWorldCupCustomizationType && $activeWorldCupCustomizationOption instanceof \App\Models\WorldCupCustomizationOption) {
                            $activeWorldCupCustomizationType = $activeWorldCupCustomizationOption->type instanceof \App\Enums\WorldCupCustomizationType
                                ? $activeWorldCupCustomizationOption->type->value
                                : (string) $activeWorldCupCustomizationOption->type;
                        }
                        if (! $activeWorldCupCustomizationType && request()->routeIs('admin.world-cup-customization-options.*') && old('type')) {
                            $activeWorldCupCustomizationType = old('type');
                        }
                        $activeWorldCupTypeEnum = \App\Enums\WorldCupCustomizationType::tryFrom((string) $activeWorldCupCustomizationType);
                        $activeWorldCupCategory = $activeWorldCupTypeEnum?->categoryKey();
                        $worldCupCustomizationCategories = \App\Support\WorldCupCustomizationRegistry::menuCategories();

                        $isTrainingVestCustomizationActive = request()->routeIs('admin.training-vest-customization-options.*') || request()->routeIs('admin.training-vest-size-option-groups.*');
                        $activeTrainingVestCustomizationType = request()->route('type');
                        $activeTrainingVestCustomizationOption = request()->route('trainingVestCustomizationOption');

                        if (! $activeTrainingVestCustomizationType && $activeTrainingVestCustomizationOption instanceof \App\Models\TrainingVestCustomizationOption) {
                            $activeTrainingVestCustomizationType = $activeTrainingVestCustomizationOption->type instanceof \App\Enums\TrainingVestCustomizationType
                                ? $activeTrainingVestCustomizationOption->type->value
                                : (string) $activeTrainingVestCustomizationOption->type;
                        }

                        if (! $activeTrainingVestCustomizationType && request()->routeIs('admin.training-vest-size-option-groups.*')) {
                            $activeTrainingVestCustomizationType = \App\Enums\TrainingVestCustomizationType::Size->value;
                        }

                        if (! $activeTrainingVestCustomizationType && old('type')) {
                            $activeTrainingVestCustomizationType = old('type');
                        }

                        if (! array_key_exists((string) $activeSizeCustomizationGroup, $customizationMenuGroups) || ! \App\Support\ProductSizing::supportsMasterDataSizeOptions((string) $activeSizeCustomizationGroup)) {
                            $activeSizeCustomizationGroup = 'jersey';
                        }
                    @endphp
                    <x-admin.sidebar-group
                        label="Master Data"
                        icon="◈"
                        :active="$isCustomizationActive || $isSizeOptionActive || $isTrainingVestCustomizationActive || $isWorldCupCustomizationActive || request()->routeIs('admin.production-methods.*') || request()->routeIs('admin.shipping-methods.*') || request()->routeIs('admin.faqs.*')"
                    >
                        @if($canAdmin('customization.view'))
                        @foreach($primaryCustomizationMenuGroups as $groupKey => $customizationGroup)
                            <x-admin.sidebar-product-customization-group
                                :group-key="$groupKey"
                                :group="$customizationGroup"
                                :is-customization-active="$isCustomizationActive"
                                :is-size-option-active="$isSizeOptionActive"
                                :active-customization-type="$activeCustomizationType"
                                :active-customization-group="$activeCustomizationGroup"
                                :active-size-customization-group="$activeSizeCustomizationGroup"
                            />
                        @endforeach

                        @foreach($trainingVestMenuGroups as $groupKey => $trainingVestGroup)
                            <x-admin.sidebar-customization-group
                                :number="$trainingVestGroup['number']"
                                :label="$trainingVestGroup['label']"
                                :active="$isTrainingVestCustomizationActive || $isTrainingVestExtensionActive"
                            >
                                    <x-admin.sidebar-customization-type-links
                                        :types="$trainingVestGroup['types']"
                                        route-name="admin.training-vest-customization-options.type"
                                        :is-active="$isTrainingVestCustomizationActive"
                                        :active-type="$activeTrainingVestCustomizationType"
                                    />

                                    @if($trainingVestExtensionGroup)
                                        <x-admin.sidebar-customization-type-links
                                            :types="$trainingVestExtensionGroup['types']"
                                            route-name="admin.jersey-customization-options.type"
                                            :is-active="$isCustomizationActive"
                                            :active-type="$activeCustomizationType"
                                        />
                                    @endif
                            </x-admin.sidebar-customization-group>
                        @endforeach

                        @foreach($afterTrainingVestCustomizationGroups as $groupKey => $customizationGroup)
                            @if($groupKey === 'world_cup')
                                <x-admin.sidebar-customization-group
                                    :number="$customizationGroup['number']"
                                    :label="$customizationGroup['label']"
                                    :active="$isWorldCupCustomizationActive"
                                >
                                    @foreach($worldCupCustomizationCategories as $worldCupCategoryKey => $worldCupCategory)
                                        <x-admin.sidebar-customization-group
                                            :number="$worldCupCategory['number']"
                                            :label="$worldCupCategory['label']"
                                            :active="$isWorldCupCustomizationActive && $activeWorldCupCategory === $worldCupCategoryKey"
                                        >
                                            <x-admin.sidebar-customization-type-links
                                                :types="$worldCupCategory['types']"
                                                route-name="admin.world-cup-customization-options.type"
                                                :is-active="$isWorldCupCustomizationActive"
                                                :active-type="$activeWorldCupCustomizationType"
                                            />
                                        </x-admin.sidebar-customization-group>
                                    @endforeach
                                </x-admin.sidebar-customization-group>
                            @elseif(isset($customizationGroup['types']))
                                <x-admin.sidebar-product-customization-group
                                    :group-key="$groupKey"
                                    :group="$customizationGroup"
                                    :is-customization-active="$isCustomizationActive"
                                    :is-size-option-active="$isSizeOptionActive"
                                    :active-customization-type="$activeCustomizationType"
                                    :active-customization-group="$activeCustomizationGroup"
                                    :active-size-customization-group="$activeSizeCustomizationGroup"
                                />
                            @else
                                <x-admin.sidebar-customization-group
                                    :number="$customizationGroup['number']"
                                    :label="$customizationGroup['label']"
                                    :has-items="false"
                                />
                            @endif
                        @endforeach
                        @endif

                        @if($canAdmin('shipping.view'))
                            <x-admin.sidebar-sub-link
                                :href="route('admin.production-methods.index')"
                                :active="request()->routeIs('admin.production-methods.*')"
                            >{{ $trailingMasterDataNumbers['production_methods'] }} Production Methods</x-admin.sidebar-sub-link>
                            <x-admin.sidebar-sub-link
                                :href="route('admin.shipping-methods.index')"
                                :active="request()->routeIs('admin.shipping-methods.*')"
                            >{{ $trailingMasterDataNumbers['shipping_methods'] }} Shipping Methods</x-admin.sidebar-sub-link>
                        @endif

                        @if($canAdmin('customization.view'))
                            <x-admin.sidebar-sub-link
                                :href="route('admin.faqs.index')"
                                :active="request()->routeIs('admin.faqs.*')"
                            >{{ $trailingMasterDataNumbers['faqs'] }} FAQs</x-admin.sidebar-sub-link>
                        @endif
                    </x-admin.sidebar-group>
                @endif

                @if($canAdmin('orders.view') || $canAdmin('returns.view') || $canAdmin('coupons.view'))
                    <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Commerce</p>
                    @if($canAdmin('orders.view'))
                        <x-admin.sidebar-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" icon="▣">Orders</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('returns.view'))
                        <x-admin.sidebar-link :href="route('admin.returns.index')" :active="request()->routeIs('admin.returns.*')" icon="↶">Returns & Exchanges</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('coupons.view'))
                        <x-admin.sidebar-link :href="route('admin.coupons.index')" :active="request()->routeIs('admin.coupons.*')" icon="%">Discounts & Coupons</x-admin.sidebar-link>
                    @endif
                @endif

                @if($canAdmin('homepage_sections.view') || $canAdmin('homepage_slides.view') || $canAdmin('newsletters.view') || $canAdmin('rural_surcharges.view') || $canAdmin('payment_methods.view'))
                    <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Store</p>
                    @if($canAdmin('homepage_sections.view'))
                        @php($homepageDefinitions = \App\Support\HomepageSectionRegistry::orderedDefinitions())
                        <x-admin.sidebar-group
                            label="Homepage"
                            icon="▧"
                            :active="request()->routeIs('admin.homepage.*') || request()->routeIs('admin.homepage-slides.*')"
                        >
                            <x-admin.sidebar-sub-link
                                :href="route('admin.homepage.sections.index')"
                                :active="request()->routeIs('admin.homepage.sections.index')"
                            >Overview</x-admin.sidebar-sub-link>
                            @foreach($homepageDefinitions as $homepageDefinition)
                                <x-admin.sidebar-sub-link
                                    :href="route('admin.homepage.sections.edit', $homepageDefinition['key'])"
                                    :active="request()->routeIs('admin.homepage.sections.edit', 'admin.homepage.sections.update') && request()->route('key') === $homepageDefinition['key']"
                                >{{ $homepageDefinition['name'] }}</x-admin.sidebar-sub-link>
                            @endforeach
                        </x-admin.sidebar-group>
                    @elseif($canAdmin('homepage_slides.view'))
                        <x-admin.sidebar-link :href="route('admin.homepage-slides.index')" :active="request()->routeIs('admin.homepage-slides.*')" icon="▧">Homepage Slider</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('newsletters.view'))
                        <x-admin.sidebar-link :href="route('admin.newsletter-subscribers.index')" :active="request()->routeIs('admin.newsletter-subscribers.*')" icon="@">Newsletter Emails</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('rural_surcharges.view'))
                        <x-admin.sidebar-link :href="route('admin.rural-area-surcharges.index')" :active="request()->routeIs('admin.rural-area-surcharges.*')" icon="⌁">Rural Surcharges</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('payment_methods.view'))
                        <x-admin.sidebar-link :href="route('admin.payment-methods.index')" :active="request()->routeIs('admin.payment-methods.*')" icon="$">Payment Methods</x-admin.sidebar-link>
                    @endif
                @endif

                @if($canAdmin('users.view') || $canAdmin('role_matrix.view'))
                    <p class="mt-6 px-3 pb-2 text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Access Control</p>
                    @if($canAdmin('users.view'))
                        <x-admin.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="♚">Admin Users</x-admin.sidebar-link>
                    @endif
                    @if($canAdmin('role_matrix.view'))
                        <x-admin.sidebar-link :href="route('admin.role-matrix.index')" :active="request()->routeIs('admin.role-matrix.*')" icon="▦">Role Matrix</x-admin.sidebar-link>
                    @endif
                @endif
            </nav>
            <x-admin.sidebar-scroll-restore />

            <div class="shrink-0 border-t border-white/10 p-3">
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

            <div
                class="admin-sidebar-resizer hidden lg:block"
                data-admin-sidebar-resizer
                role="separator"
                aria-orientation="vertical"
                aria-label="Resize admin menu"
                aria-valuemin="220"
                aria-valuemax="380"
                aria-valuenow="256"
                tabindex="0"
            ></div>
        </aside>

        <div class="min-w-0">
            <header @class([
                'sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8',
                'min-h-[82px] lg:min-h-[92px]' => $compactHeader,
                'min-h-[90px] lg:min-h-[104px]' => ! $compactHeader,
            ])>
                <div class="flex min-w-0 items-center gap-4">
                    <button type="button" class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-slate-200 text-xl lg:hidden" @click="sidebarOpen = true" aria-label="Open sidebar" aria-controls="admin-sidebar">☰</button>
                    <div class="min-w-0">
                        <p class="text-[11px] font-black uppercase tracking-[.28em] text-brand-red">{{ $eyebrow ?? 'Administration' }}</p>
                        <h1 @class([
                            'truncate font-black leading-tight text-brand-ink',
                            'text-xl sm:text-2xl lg:text-[28px]' => $compactHeader,
                            'text-2xl sm:text-3xl lg:text-[34px]' => ! $compactHeader,
                        ])>{{ $title }}</h1>
                        @if($subtitle)
                            <p class="mt-1 max-w-2xl truncate text-sm font-medium text-slate-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @auth('admin')
                        <x-admin.notification-bell />
                    @endauth
                    <a href="{{ $storefrontUrl ?: route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-10 shrink-0 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-brand-blue shadow-sm transition hover:bg-slate-50 sm:min-h-11"><span class="hidden sm:inline">View storefront&nbsp;</span>↗</a>
                </div>
            </header>

            <main id="admin-main-content" tabindex="-1" class="min-w-0 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any() && ! request()->routeIs('admin.products.create', 'admin.products.edit', 'admin.homepage.sections.edit'))
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
    @if(session()->has('storefront_catalog_changed_at'))
        <script>
            (() => {
                const changedAt = @json((string) session('storefront_catalog_changed_at'));

                try {
                    window.localStorage.setItem('nextplay:catalog-updated-at', changedAt);
                } catch (error) {
                    // Local storage can be unavailable in restricted browsers.
                }

                if ('BroadcastChannel' in window) {
                    const channel = new BroadcastChannel('nextplay:storefront-updates');
                    channel.postMessage({ type: 'catalog-updated', changedAt });
                    channel.close();
                }
            })();
        </script>
    @endif

    @auth('admin')
        <div class="admin-toast" id="adminToast" aria-live="polite"></div>
        <script>
            window.NEXTPLAY_ADMIN_NOTIFICATIONS = {
                userId: {{ (int) auth('admin')->id() }},
                feedUrl: @json(route('admin.notifications.feed')),
                readAllUrl: @json(route('admin.notifications.read-all')),
                readUrlTemplate: @json(route('admin.notifications.read', ['notification' => '__ID__'])),
                pusherAuthUrl: @json(route('admin.notifications.pusher-auth')),
                pusherEnabled: @json($adminPusherEnabled),
                pusherKey: @json(config('services.pusher.key')),
                pusherCluster: @json(config('services.pusher.cluster')),
                pollIntervalMs: 60000
            };
        </script>
        @if($adminPusherEnabled)
            <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
        @endif
        <script src="{{ asset('js/admin-notifications.js') }}?v={{ $adminNotificationsJsVersion }}"></script>
    @endauth
</body>
</html>
