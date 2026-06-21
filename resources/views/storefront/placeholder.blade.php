<x-layouts.storefront :seo="[
    'title' => $title . ' | ' . config('storefront.name'),
    'description' => $message,
    'robots' => 'noindex, follow',
]">
    <section class="section-padding">
        <div class="site-container">
            <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-card">
                <p class="mb-2 text-xs font-black uppercase tracking-[.18em] text-brand-red">Coming next</p>
                <h1 class="font-display text-4xl font-bold uppercase text-brand-ink">{{ $title }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-slate-500">{{ $message }}</p>
                <a href="{{ route('home') }}" class="btn btn-red mt-6">Back to Home</a>
            </div>
        </div>
    </section>
</x-layouts.storefront>
