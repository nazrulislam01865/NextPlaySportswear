@php
    $faqEntities = collect($categories)
        ->flatten(1)
        ->map(fn (array $faq) => [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'],
            ],
        ])
        ->values()
        ->all();

    $faqStructuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqEntities,
    ]];
@endphp

<x-layouts.storefront :seo="[
    'title' => 'Help Center and FAQ | ' . config('storefront.name'),
    'description' => 'Answers about ordering, customization, artwork, shipping, tracking, returns, refunds, and payments at NextPlay Sportswear.',
    'schema_type' => 'FAQPage',
]" :structured-data="$faqStructuredData">
    <x-storefront.content.hero
        eyebrow="Help center"
        title="Common Questions, Clear Answers"
        description="Find guidance for regular online orders, custom team orders, artwork, production, shipping, tracking, returns, and payment."
        image="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1100&q=80"
        image-alt="Customer searching support information on a laptop"
    >
        <a href="#faq-browser" class="btn btn-red">Browse Questions</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Contact Support</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="faq-browser">
        <div class="site-container" x-data="{ query: '', category: 'all' }">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Search support</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase text-brand-ink sm:text-4xl">What Can We Help With?</h2>
                <div class="relative mt-6">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
                    <label for="faq-search" class="sr-only">Search frequently asked questions</label>
                    <input id="faq-search" type="search" x-model.debounce.150ms="query" class="h-14 w-full rounded-2xl border border-slate-300 bg-white pl-12 pr-4 text-sm shadow-sm outline-none focus:border-brand-blue" placeholder="Search sizes, artwork, shipping, returns...">
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button type="button" @click="category = 'all'" :class="category === 'all' ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-slate-600 border-slate-300'" class="rounded-full border px-4 py-2 text-xs font-extrabold">All Questions</button>
                    @foreach(array_keys($categories) as $categoryName)
                        <button type="button" @click="category = @js($categoryName)" :class="category === @js($categoryName) ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-slate-600 border-slate-300'" class="rounded-full border px-4 py-2 text-xs font-extrabold">{{ $categoryName }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mx-auto mt-10 max-w-4xl space-y-8">
                @foreach($categories as $categoryName => $faqs)
                    <section x-show="category === 'all' || category === @js($categoryName)" class="rounded-3xl border border-slate-200 bg-slate-50 p-5 sm:p-7">
                        <h3 class="font-display text-2xl font-bold uppercase text-brand-ink">{{ $categoryName }}</h3>
                        <div class="mt-4 space-y-3" x-data="{ open: null }">
                            @foreach($faqs as $index => $faq)
                                @php $searchText = strtolower($faq['question'] . ' ' . $faq['answer']); @endphp
                                <article
                                    x-show="query.trim() === '' || @js($searchText).includes(query.toLowerCase())"
                                    class="overflow-hidden rounded-xl border border-slate-200 bg-white"
                                >
                                    <button type="button" class="flex w-full items-center justify-between gap-5 px-5 py-4 text-left font-extrabold text-brand-ink" @click="open = open === {{ $index }} ? null : {{ $index }}" :aria-expanded="(open === {{ $index }}).toString()">
                                        <span>{{ $faq['question'] }}</span><span class="text-xl text-brand-red" x-text="open === {{ $index }} ? '−' : '+'"></span>
                                    </button>
                                    <div x-cloak x-show="open === {{ $index }}" x-transition class="px-5 pb-5 text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Popular guides" title="More Detailed Help" description="Use these guides when your question involves measurements, artwork, or order planning." />
            <div class="grid gap-5 md:grid-cols-3">
                <x-storefront.content.icon-card icon="1" title="How to Order" description="Understand the difference between regular checkout and team or bulk quotation workflows."><a href="{{ route('how-to-order') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">Read Guide →</a></x-storefront.content.icon-card>
                <x-storefront.content.icon-card icon="2" title="Size Guide" description="Measure accurately and review general size-chart guidance before submitting a roster."><a href="{{ route('size-guide') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">View Sizes →</a></x-storefront.content.icon-card>
                <x-storefront.content.icon-card icon="3" title="Artwork Guidelines" description="Prepare logos and design files that can be reviewed efficiently for custom production."><a href="{{ route('artwork-guidelines') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">View Artwork Guide →</a></x-storefront.content.icon-card>
            </div>
        </div>
    </section>

    <x-storefront.content.cta
        title="Still Need Help?"
        description="Send the product, order, artwork, or delivery details and our support team can review your question."
        primary-label="Contact Support"
        :primary-href="route('contact')"
        secondary-label="Track an Order"
        :secondary-href="route('orders.track')"
    />
</x-layouts.storefront>
