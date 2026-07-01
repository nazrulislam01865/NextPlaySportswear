@props([
    'eyebrow',
    'title',
    'description',
    'image' => null,
    'imageAlt' => '',
    'compact' => false,
])

<section class="border-b border-slate-200 bg-[#f2f4f7]">
    <div class="site-container grid items-center gap-10 py-12 lg:grid-cols-[1.05fr_.95fr] lg:py-16">
        <div>
            <p class="mb-3 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[.18em] text-brand-red before:h-[3px] before:w-7 before:rounded-full before:bg-brand-red">
                {{ $eyebrow }}
            </p>
            <h1 class="max-w-3xl font-display text-4xl font-bold uppercase leading-[.98] tracking-tight text-brand-ink sm:text-5xl lg:text-6xl">
                {{ $title }}
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                {{ $description }}
            </p>
            @if(trim((string) $slot) !== '')
                <div class="mt-7 flex flex-wrap gap-3">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if($image)
            <div class="relative">
                <div class="rounded-[22px] border border-white bg-white p-3 shadow-hero sm:p-4">
                    <img
                        src="{{ $image }}"
                        alt="{{ $imageAlt }}"
                        width="900"
                        height="620"
                        loading="lazy"
                        decoding="async"
                        class="w-full rounded-2xl object-cover {{ $compact ? 'h-64' : 'h-72 sm:h-80' }}"
                    >
                </div>
                <div class="absolute -bottom-4 left-4 rounded-xl bg-brand-navy px-5 py-3 text-white shadow-xl sm:-left-5">
                    <span class="block font-display text-xl font-bold uppercase">NextPlay Support</span>
                    <span class="text-xs font-semibold text-white/80">Clear guidance for every order</span>
                </div>
            </div>
        @else
            <div class="rounded-[22px] bg-brand-navy p-7 text-white shadow-hero sm:p-9">
                <p class="text-xs font-black uppercase tracking-[.18em] text-red-300">Need direct help?</p>
                <h2 class="mt-3 font-display text-3xl font-bold uppercase leading-tight">Talk to our support team</h2>
                <p class="mt-3 text-sm leading-6 text-white/75">Questions about sizes, artwork, shipping, returns, or a team order can be sent through our contact page.</p>
                <a href="{{ route('contact') }}" class="btn btn-red mt-6">Contact Us</a>
            </div>
        @endif
    </div>
</section>
