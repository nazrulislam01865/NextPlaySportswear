@props([
    'seo' => [],
    'steps' => [],
    'currentStep' => 'information',
    'title' => 'Secure Checkout',
    'description' => 'Complete your order securely.',
    'summary' => [],
])

<x-layouts.storefront :seo="$seo">
    <section class="bg-gradient-to-br from-brand-navy via-brand-dark to-slate-950 py-10 text-white">
        <div class="site-container grid gap-7 lg:grid-cols-[minmax(0,1fr)_390px] lg:items-center">
            <div>
                <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm font-bold text-blue-100" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                    <span>/</span>
                    <a href="{{ route('cart.index') }}" class="hover:text-white">Cart</a>
                    <span>/</span>
                    <span class="text-white">Checkout</span>
                </nav>
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Secure checkout</p>
                <h1 class="mt-3 font-display text-5xl font-bold uppercase tracking-tight sm:text-6xl">{{ $title }}</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-blue-100">{{ $description }}</p>
            </div>

            <aside class="rounded-[26px] border border-white/15 bg-white/10 p-5 shadow-hero backdrop-blur">
                <div class="grid gap-4 text-sm font-semibold text-blue-50">
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-brand-red">✓</span><span>Encrypted checkout and protected customer information.</span></div>
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-brand-red">✓</span><span>Design support before custom production begins.</span></div>
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-brand-red">✓</span><span>Server-side totals, shipping, tax, and payment validation.</span></div>
                </div>
            </aside>
        </div>
    </section>

    <x-storefront.checkout.stepper :steps="$steps" :current-step="$currentStep" />

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
                <div>
                    {{ $slot }}
                </div>

                <x-storefront.checkout.summary-card :summary="$summary" />
            </div>
        </div>
    </section>
</x-layouts.storefront>
