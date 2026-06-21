@props([
    'mode' => 'login',
    'eyebrow' => 'Customer account',
    'title',
    'subtitle',
])

@php
    $isRegister = $mode === 'register';
    $benefits = $isRegister
        ? [
            'Save order details for repeat team purchases.',
            'Track artwork proof and production updates.',
            'Move faster through checkout and quote requests.',
        ]
        : [
            'Review active quotes and previous custom orders.',
            'Approve artwork proofs without searching emails.',
            'Keep billing, shipping, and team details ready.',
        ];
@endphp

<section class="relative overflow-hidden bg-[#f6f8fb] py-10 sm:py-14 lg:py-16">
    <div class="pointer-events-none absolute -left-24 top-16 h-64 w-64 rounded-full bg-brand-red/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-28 bottom-8 h-72 w-72 rounded-full bg-brand-blue/10 blur-3xl"></div>

    <div class="site-container relative">
        <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">{{ $eyebrow }}</p>
                <h1 class="mt-2 font-display text-4xl font-bold uppercase leading-none text-brand-ink sm:text-5xl">
                    {{ $title }}
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                    {{ $subtitle }}
                </p>
            </div>

            <a href="{{ route('products.index') }}" class="btn btn-white w-full sm:w-auto">
                Continue Shopping
            </a>
        </div>

        <div class="grid overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-hero lg:grid-cols-[.92fr_1.08fr]">
            <aside class="relative min-h-[430px] overflow-hidden bg-brand-dark p-8 text-white sm:p-10 lg:p-12">
                <div class="absolute inset-0 opacity-35" aria-hidden="true">
                    <div class="absolute -right-24 top-8 h-56 w-56 rounded-full bg-brand-red blur-3xl"></div>
                    <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-brand-blue blur-3xl"></div>
                </div>

                <div class="relative flex h-full flex-col justify-between gap-10">
                    <div>
                        <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[.16em] text-white/90">
                            <span class="h-2 w-2 rounded-full bg-brand-red"></span>
                            Team order ready
                        </div>

                        <h2 class="font-display text-4xl font-bold uppercase leading-tight sm:text-5xl">
                            Custom gear, quotes, proofs, and checkout in one place.
                        </h2>

                        <p class="mt-5 max-w-md text-sm leading-7 text-white/75">
                            NextPlay accounts are built for team buyers, coaches, schools, clubs, and repeat custom sportswear customers.
                        </p>
                    </div>

                    <div class="grid gap-3">
                        @foreach ($benefits as $benefit)
                            <div class="flex gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-red text-sm font-black">✓</span>
                                <p class="text-sm font-bold leading-6 text-white/90">{{ $benefit }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-3 gap-3 border-t border-white/10 pt-6 text-center">
                        <div>
                            <p class="font-display text-3xl font-bold uppercase">24h</p>
                            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-white/60">Proof help</p>
                        </div>
                        <div>
                            <p class="font-display text-3xl font-bold uppercase">US</p>
                            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-white/60">Focused store</p>
                        </div>
                        <div>
                            <p class="font-display text-3xl font-bold uppercase">1+</p>
                            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-white/60">MOQ support</p>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="p-6 sm:p-8 lg:p-10">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
