<x-layouts.storefront :seo="$seo" :structured-data="$structuredData">
    <div x-data="{ filter: 'all' }">
    <section class="bg-[#f3f5f7] pb-[66px] pt-11" aria-labelledby="browse-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Quick navigation</span>
                <h1 id="browse-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Browse Categories</h1>
                <p class="mx-auto mt-2 max-w-[700px] text-slate-500 max-sm:mx-0">Use the category tabs below to quickly find what you need.</p>
            </div>

            <div class="flex flex-nowrap justify-start gap-2.5 overflow-x-auto pb-1 lg:flex-wrap lg:justify-center" role="list" aria-label="Category filters">
                <button
                    type="button"
                    class="whitespace-nowrap rounded-full border px-3.5 py-2 text-[13px] font-black transition"
                    :class="filter === 'all' ? 'border-brand-navy bg-brand-navy text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-navy hover:bg-brand-navy hover:text-white'"
                    @click="filter = 'all'"
                >All Categories</button>

                @foreach ($filterTags as $tag)
                    <button
                        type="button"
                        class="whitespace-nowrap rounded-full border px-3.5 py-2 text-[13px] font-black transition"
                        :class="filter === @js($tag['slug']) ? 'border-brand-navy bg-brand-navy text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-navy hover:bg-brand-navy hover:text-white'"
                        @click="filter = @js($tag['slug'])"
                    >{{ $tag['name'] }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section id="categories" class="bg-white py-[66px]" aria-labelledby="categories-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">All product groups</span>
                <h2 id="categories-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">All Product Categories</h2>
                <p class="mx-auto mt-2 max-w-[700px] text-slate-500 max-sm:mx-0">Start with the product type that matches your team, event, business, or personal order.</p>
            </div>

            <div class="grid grid-cols-1 gap-[22px] md:grid-cols-2 lg:grid-cols-3" id="categoryGrid">
                @foreach ($collections as $category)
                    <x-storefront.category-index-card :category="$category" />
                @endforeach
            </div>

            <div
                x-cloak
                x-show="filter !== 'all' && !Array.from(document.querySelectorAll('#categoryGrid [data-tags]')).some(card => card.dataset.tags.split(' ').includes(filter))"
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-600"
            >
                No active categories are assigned to this filter.
            </div>
        </div>
    </section>
    </div>

    <section class="bg-[#f3f5f7] py-[66px]" id="sports" aria-labelledby="sports-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Sport-specific</span>
                <h2 id="sports-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Shop by Sport</h2>
                <p class="mx-auto mt-2 max-w-[700px] text-slate-500 max-sm:mx-0">Looking for sport-specific uniforms or gear? Start with your sport and find matching products faster.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($sports as $sport)
                    <x-storefront.sport-index-card :sport="$sport" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-[66px]" aria-labelledby="customize-title">
        <div class="site-container">
            <div class="grid items-center gap-[26px] rounded-[18px] border border-slate-300 bg-white p-[26px] shadow-soft lg:grid-cols-[.85fr_1.15fr]">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Customization support</span>
                    <h2 id="customize-title" class="mt-2 font-display text-[clamp(32px,4vw,38px)] font-bold uppercase leading-[1.02] text-brand-ink">Customize Your Gear Your Way</h2>
                    <p class="mt-2 text-slate-500">Many products can be customized with team logos, player names, numbers, colors, sponsor marks, event names, and brand artwork. For team and bulk orders, we can review your design details before production.</p>
                    <a class="btn btn-red mt-[18px] max-sm:w-full" href="{{ route('products.index') }}">Start Custom Order</a>
                </div>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <div class="rounded-[13px] border border-slate-200 bg-slate-50 p-3.5"><strong class="block text-brand-ink">Logo Printing</strong><span class="mt-1 block text-[13px] text-slate-500">Add your team, school, business, or event logo.</span></div>
                    <div class="rounded-[13px] border border-slate-200 bg-slate-50 p-3.5"><strong class="block text-brand-ink">Player Names & Numbers</strong><span class="mt-1 block text-[13px] text-slate-500">Personalize jerseys and uniforms for each player.</span></div>
                    <div class="rounded-[13px] border border-slate-200 bg-slate-50 p-3.5"><strong class="block text-brand-ink">Team Colors</strong><span class="mt-1 block text-[13px] text-slate-500">Match your team, club, school, or brand colors.</span></div>
                    <div class="rounded-[13px] border border-slate-200 bg-slate-50 p-3.5"><strong class="block text-brand-ink">Artwork Review</strong><span class="mt-1 block text-[13px] text-slate-500">Send your design file or idea. A proof or mockup may be reviewed before production.</span></div>
                </div>
            </div>
        </div>
    </section>

    <section id="bulk" class="bg-[#f3f5f7] py-[66px]">
        <div class="site-container">
            <div class="grid items-center gap-9 rounded-[20px] bg-[linear-gradient(90deg,rgba(13,37,69,.95),rgba(21,52,93,.83)),url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=1400&q=80')] bg-cover bg-center p-7 text-white shadow-hero lg:grid-cols-[1fr_.82fr] lg:p-[42px]">
                <div>
                    <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Team and bulk ordering</span>
                    <h2 class="mt-2 font-display text-[clamp(32px,4vw,42px)] font-bold uppercase leading-[1.02]">Ordering for a Team, School, League, or Event?</h2>
                    <p class="mt-3 text-white/90">Bulk orders need a little more detail. Send us your product choice, quantity, logo, size list, player names, numbers, color preference, and delivery timeline. We’ll help you prepare a clear quote.</p>
                    <a class="btn btn-red mt-[18px] max-sm:w-full" href="{{ route('quote.request') }}">Request Bulk Quote</a>
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

    <section id="choose" class="bg-white py-[66px]" aria-labelledby="choose-title">
        <div class="site-container grid items-start gap-7 lg:grid-cols-[.9fr_1.1fr]">
            <div>
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Simple guide</span>
                <h2 id="choose-title" class="mt-2 font-display text-[clamp(32px,4vw,38px)] font-bold uppercase leading-[1.02] text-brand-ink">Not Sure Where to Start?</h2>
                <p class="mt-2 text-slate-500">Here is a simple way to choose the right category.</p>
                <a class="btn btn-light mt-[18px] max-sm:w-full" href="{{ route('home') }}#contact">Need Help Choosing? Contact Us</a>
            </div>

            <div class="grid gap-3">
                @php
                    $choices = [
                        ['Need a full team set?', 'Start with Custom Team Uniforms.'],
                        ['Need only jerseys?', 'Start with Custom Jerseys or choose your sport.'],
                        ['Need branded clothing for an event?', 'Start with Promotional Products or Performance T-Shirts.'],
                        ['Need matching team travel gear?', 'Start with Hoodies, Outerwear, or Sports Bags.'],
                        ['Need fan merchandise?', 'Start with Fan Gear, Caps, or Custom Jerseys.'],
                    ];
                @endphp
                @foreach ($choices as $index => $choice)
                    <div class="grid grid-cols-[auto_1fr] items-start gap-3 rounded-[13px] border border-slate-200 bg-white px-4 py-[15px]">
                        <span class="grid h-[30px] w-[30px] place-items-center rounded-full bg-brand-navy text-[13px] font-black text-white">{{ $index + 1 }}</span>
                        <div><strong>{{ $choice[0] }}</strong><br><span class="text-slate-700">{{ $choice[1] }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#f3f5f7] py-[66px]" aria-labelledby="why-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">Less guessing</span>
                <h2 id="why-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Find the Right Product Without Guesswork</h2>
                <p class="mx-auto mt-2 max-w-[700px] text-slate-500 max-sm:mx-0">A custom order can have many details. Category browsing helps you start with the right product first, then choose customization, quantity, size, and order type.</p>
            </div>

            <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Clear Product Groups', 'Browse by product type, sport, or order purpose.'],
                    ['Online and Bulk Options', 'Order selected products online or request a quote for larger quantities.'],
                    ['Team-Friendly Ordering', 'Good for size lists, player names, numbers, and team color matching.'],
                    ['Custom Design Support', 'Send your logo or design idea before production.'],
                ] as $index => $reason)
                    <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-card">
                        <div class="grid h-10 w-10 place-items-center rounded-xl bg-red-50 font-black text-brand-red">{{ $index + 1 }}</div>
                        <h3 class="mt-3 text-base font-extrabold text-brand-ink">{{ $reason[0] }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $reason[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-[66px]" aria-labelledby="faq-title">
        <div class="site-container">
            <div class="mb-8 text-center max-sm:text-left">
                <span class="text-xs font-black uppercase tracking-[.08em] text-brand-red">FAQ</span>
                <h2 id="faq-title" class="mt-2 font-display text-[clamp(28px,4vw,42px)] font-bold uppercase leading-[1.05] text-brand-ink">Category Shopping Questions</h2>
            </div>
            <x-storefront.faq-list :faqs="$faqs" :initial-open="null" />
        </div>
    </section>

    <section class="bg-brand-navy py-[66px] text-white" id="contact">
        <div class="site-container text-center">
            <h2 class="font-display text-[clamp(32px,4vw,42px)] font-bold uppercase leading-[1.02]">Ready to Find Your Gear?</h2>
            <p class="mx-auto mt-2 max-w-[660px] text-white/85">Choose a category to start shopping, or send us your order details if you need help with team, school, event, or bulk production.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a class="btn btn-red max-sm:w-full" href="#categories">Browse All Categories</a>
                <a class="btn btn-white max-sm:w-full" href="{{ route('quote.request') }}">Request Bulk Quote</a>
            </div>
            <div class="mt-3.5 text-[13px] text-white/90">Email: <strong>{{ config('storefront.email') }}</strong> &nbsp; | &nbsp; WhatsApp: <strong>{{ config('storefront.whatsapp') }}</strong></div>
        </div>
    </section>
</x-layouts.storefront>
