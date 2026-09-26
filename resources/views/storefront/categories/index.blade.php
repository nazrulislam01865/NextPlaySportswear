<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    <div x-data="{ activeParent: 'all' }">
    <section class="bg-[#f3f5f7] pb-[66px] pt-11" aria-labelledby="browse-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <h1 id="browse-title" class="font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Browse Categories</h1>
            </div>

            <div class="flex flex-nowrap justify-start gap-2.5 overflow-x-auto pb-1 lg:flex-wrap lg:justify-center" role="list" aria-label="Main category filters">
                <button
                    type="button"
                    class="whitespace-nowrap rounded-full border px-3.5 py-2 text-[13px] font-black transition"
                    :class="activeParent === 'all' ? 'border-brand-navy bg-brand-navy text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-navy hover:bg-brand-navy hover:text-white'"
                    @click="activeParent = 'all'"
                >All Categories</button>

                @foreach ($categoryBrowser as $group)
                    <button
                        type="button"
                        class="whitespace-nowrap rounded-full border px-3.5 py-2 text-[13px] font-black transition"
                        :class="activeParent === @js($group['parent']['slug']) ? 'border-brand-navy bg-brand-navy text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-navy hover:bg-brand-navy hover:text-white'"
                        @click="activeParent = @js($group['parent']['slug'])"
                    >{{ $group['parent']['title'] }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section id="categories" class="bg-white py-[66px]" aria-labelledby="categories-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <h2 id="categories-title" class="font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">All Product Categories</h2>
            </div>

            @php
                $hasProductCategories = collect($categoryBrowser)->contains(
                    fn ($group) => ! empty($group['children'])
                );
            @endphp

            @if ($hasProductCategories)
                <div class="np-shared-category-card-grid np-all-categories-grid" id="categoryGroups">
                    @foreach ($categoryBrowser as $group)
                        @foreach ($group['children'] as $category)
                            <div
                                class="min-w-0"
                                data-parent-category="{{ $group['parent']['slug'] }}"
                                data-category-depth="{{ $category['depth'] }}"
                                x-show="activeParent === 'all' || activeParent === @js($group['parent']['slug'])"
                                x-transition.opacity.duration.150ms
                            >
                                <x-storefront.category-card :category="$category" />
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-slate-600">
                    No product categories with active products are currently available.
                </div>
            @endif
        </div>
    </section>
    </div>

    <section class="bg-[#f3f5f7] py-[66px]" id="sports" aria-labelledby="sports-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <h2 id="sports-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Shop by Sport</h2>
                <p class="mx-auto mt-2 max-w-[700px] text-slate-500 max-sm:mx-0">Looking for sport-specific uniforms or gear? Start with your sport and find matching products faster.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($sports as $sport)
                    <x-storefront.sport-index-card :sport="$sport" />
                @endforeach
            </div>
        </div>
    </section>

    <section id="bulk" class="bg-[#f3f5f7] py-[66px]">
        <div class="site-container">
            <div class="storefront-category-bulk-cta grid items-center gap-9 rounded-[20px] bg-cover bg-center p-7 text-white shadow-hero lg:grid-cols-[1fr_.82fr] lg:p-[42px]">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Team and bulk ordering</span>
                    <h2 class="mt-2 font-display text-[clamp(32px,4vw,42px)] font-bold uppercase leading-[1.02]">Ordering for a Team, School, League, or Event?</h2>
                    <p class="mt-3 text-white/90">Bulk orders need a little more detail. Send us your product choice, quantity, logo, size list, player names, numbers, color preference, and delivery timeline. We’ll help you prepare a clear quote.</p>
                    <a class="btn btn-primary mt-[18px] max-sm:w-full" href="{{ route('quote.request') }}">Request Bulk Quote</a>
                    <div class="mt-[18px] border-l-[3px] border-brand-red pl-3 text-[13px] text-white/90">You can place regular orders directly on the website using Add to Cart. For larger orders, especially 500+ or 1,000+ pieces, contact us by email or WhatsApp for a custom quotation and possible special bulk pricing.</div>
                </div>

                <ul class="grid gap-2.5 font-bold">
                    @foreach (['Full team uniform orders', 'School and college apparel', 'League and tournament orders', 'Corporate and event merchandise', '500+ or 1,000+ piece bulk production orders'] as $benefit)
                        <li class="flex items-start gap-2.5"><span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand-red text-xs">✓</span><span>{{ $benefit }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="bg-white py-[66px] text-brand-ink" id="contact">
        <div class="site-container text-center">
            <h2 class="font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Ready to Find Your Gear?</h2>
            <p class="mx-auto mt-2 max-w-[660px] text-slate-500">Choose a category to start shopping, or send us your order details if you need help with team, school, event, or bulk production.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a class="btn btn-primary max-sm:w-full" href="#categories">Browse All Categories</a>
                <a class="btn btn-outline max-sm:w-full" href="{{ route('quote.request') }}">Request Bulk Quote</a>
            </div>
            <div class="mt-3.5 text-[13px] text-slate-500">Email: <strong class="text-brand-ink">{{ config('storefront.email') }}</strong> &nbsp; | &nbsp; WhatsApp: <strong class="text-brand-ink">{{ config('storefront.whatsapp') }}</strong></div>
        </div>
    </section>
</x-layouts.storefront>
