@props([
    'title' => 'My Account',
    'subtitle' => 'Manage your NextPlay Sportswear customer profile, orders, quotes, and saved designs.',
    'account' => [],
    'navigation' => [],
    'fullWidth' => false,
])

<section class="bg-brand-red py-6 text-white sm:py-7">
    <div class="site-container text-center">
        <p class="font-display text-3xl font-black uppercase italic leading-tight tracking-wide drop-shadow-lg sm:text-4xl md:text-5xl">
            {{ $title }}
        </p>
        <p class="mx-auto mt-2 max-w-2xl text-sm font-bold text-white/85 md:text-base">
            {{ $subtitle }}
        </p>
    </div>
</section>

<section class="bg-slate-50 py-8 md:py-12">
    <div class="{{ $fullWidth ? 'mx-auto' : 'site-container' }}" @if($fullWidth) style="width:min(1180px, calc(100% - 24px)); margin-inline:auto;" @endif>
        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-extrabold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('password_status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-extrabold text-emerald-800 shadow-sm">
                {{ session('password_status') }}
            </div>
        @endif

        @if ($fullWidth)
            {{ $slot }}
        @else
            <div class="np-account-prototype-layout">
                <x-storefront.account.sidebar :account="$account" :navigation="$navigation" />
                <div class="np-account-prototype-content np-account-square-cards">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </div>
</section>
