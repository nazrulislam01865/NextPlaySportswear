<x-layouts.admin title="Navigation Menu" eyebrow="Storefront" subtitle="Manage the new Vue storefront header navigation and mega menus." :storefront-url="route('home')">
    @php
        $navigationItems = array_values(array_filter((array) old('navigation.items', $settings['items'] ?? []), 'is_array'));
    @endphp

    <div class="mx-auto max-w-6xl space-y-6" data-navigation-settings>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900">New Vue Navigation</h2>
                    <p class="mt-1 text-sm text-slate-500">This is the single navigation source for the new Vue storefront. Desktop and mobile use the same menu and mega-menu configuration.</p>
                </div>
                <span class="inline-flex min-h-10 items-center rounded-xl bg-slate-100 px-4 text-xs font-black uppercase tracking-wide text-slate-600">Vue storefront source</span>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.navigation-settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            @include('admin.storefront-settings.partials.navigation-menu-editor', [
                'navigationItems' => $navigationItems,
            ])

            <div class="sticky bottom-3 z-30 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur">
                <button class="btn btn-red">Save Navigation Menu</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
