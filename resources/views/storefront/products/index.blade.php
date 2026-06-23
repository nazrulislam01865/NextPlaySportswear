<x-layouts.storefront :seo="$seo">
    <section class="bg-gradient-to-br from-brand-navy via-brand-dark to-brand-blue py-16 text-white">
        <div class="site-container">
            <p class="text-xs font-black uppercase tracking-[.2em] text-red-100">NextPlay catalog</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl">Products</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50">Browse custom jerseys, team uniforms, hoodies, caps, bags, and quote-ready sportswear products.</p>

            <form method="GET" action="{{ route('products.index') }}" class="mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl bg-white p-2 shadow-hero sm:flex-row">
                <input type="search" name="q" value="{{ $query }}" placeholder="Search football, jersey, cap, bag..." class="min-h-12 flex-1 rounded-xl border border-slate-200 px-4 text-sm text-slate-700 outline-none focus:border-brand-red">
                <button class="btn btn-red" type="submit">Search</button>
            </form>
        </div>
    </section>

    <section class="section-padding bg-slate-50">
        <div class="site-container">
            <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-500">{{ count($products) }} product{{ count($products) === 1 ? '' : 's' }} found</p>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">{{ filled($query) ? 'Search: ' . $query : 'Featured Products' }}</h2>
                </div>
                <a href="{{ route('quote.request') }}" class="btn btn-white">Need Bulk Quote?</a>
            </div>

            @if (count($products))
                <div class="grid-4">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
                    <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No products found</h3>
                    <p class="mt-2 text-slate-600">Try another search keyword or request a custom quote.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-red mt-5">View All Products</a>
                </div>
            @endif
        </div>
    </section>
</x-layouts.storefront>
