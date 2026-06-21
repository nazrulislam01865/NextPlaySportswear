@props([
    'seo' => [],
    'order' => [],
    'summary' => [],
    'badge' => null,
    'badgeTone' => 'info',
    'title' => 'Order Updates',
    'description' => 'Review your secure custom sportswear order.',
    'statusTitle' => null,
    'statusSubtitle' => null,
])

<x-layouts.storefront :seo="$seo">
    <section class="bg-gradient-to-br from-brand-navy via-brand-dark to-slate-950 py-10 text-white lg:py-14">
        <div class="site-container grid gap-7 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
            <div>
                <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm font-bold text-blue-100" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                    <span>/</span>
                    <span class="text-white">{{ $title }}</span>
                </nav>

                @if ($badge)
                    <x-storefront.order.status-badge :status="$badgeTone" :tone="$badgeTone" class="border-white/20 bg-white/10 text-white">{{ $badge }}</x-storefront.order.status-badge>
                @endif

                @if (($order['is_demo'] ?? false) === true)
                    <span class="ml-2 inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-800">Demo preview</span>
                @endif

                <h1 class="mt-4 font-display text-5xl font-bold uppercase tracking-tight sm:text-6xl">{{ $title }}</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-blue-100">{{ $description }}</p>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    {{ $actions ?? '' }}
                </div>
            </div>

            <aside class="rounded-[26px] border border-white/15 bg-white/10 p-6 shadow-hero backdrop-blur">
                <span class="text-xs font-black uppercase tracking-[.18em] text-blue-100">{{ $order['order_number'] ?? 'Order' }}</span>
                <strong class="mt-2 block text-3xl font-black">{{ $statusTitle ?? \Illuminate\Support\Str::headline($order['status'] ?? 'Order status') }}</strong>
                <span class="mt-2 block text-sm font-bold leading-6 text-blue-100">{{ $statusSubtitle ?? 'Estimated delivery: ' . ($order['estimated_delivery'] ?? 'To be confirmed') }}</span>
            </aside>
        </div>
    </section>

    <section class="bg-slate-50 py-10 lg:py-14">
        <div class="site-container">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-black text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_390px] lg:items-start">
                <div>{{ $slot }}</div>
                <x-storefront.order.summary-card :summary="$summary" />
            </div>
        </div>
    </section>
</x-layouts.storefront>
