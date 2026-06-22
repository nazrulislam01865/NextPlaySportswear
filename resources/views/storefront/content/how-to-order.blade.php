<x-layouts.storefront :seo="[
    'title' => 'How to Order | ' . config('storefront.name'),
    'description' => 'Learn how to place regular online orders and custom team, school, event, or bulk orders with NextPlay Sportswear.',
]">
    <x-storefront.content.hero
        eyebrow="Ordering guide"
        title="From Product Choice to Delivery"
        description="Choose the ordering path that matches your quantity and customization needs. Regular products can move through checkout, while complex team and bulk orders should be reviewed through a quotation."
        image="https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1100&q=80"
        image-alt="Order planning notes and laptop"
    >
        <a href="#order-paths" class="btn btn-red">Choose Your Path</a>
        <a href="{{ route('quote.request') }}" class="btn btn-white">Request a Quote</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="order-paths">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Start here" title="Two Ways to Order" description="The product page should make it clear whether the item can be purchased directly or requires a quotation." />
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-7 shadow-card sm:p-9">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase text-brand-blue">Regular online order</span>
                    <h2 class="mt-4 font-display text-3xl font-bold uppercase text-brand-ink">Shop, customize, and checkout</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Use this path when the product has an active price, supported options, and an Add to Cart action.</p>
                    <ol class="mt-6 grid gap-4">
                        @foreach(['Choose a product and review its price, options, size information, and estimated availability.', 'Select available sizes, colors, quantities, and supported custom details.', 'Review the cart carefully and apply an eligible coupon if available.', 'Enter contact, shipping, billing, delivery, and payment information.', 'Review the final order before submitting payment.'] as $i => $step)
                            <li class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-navy font-display font-bold text-white">{{ $i + 1 }}</span><p class="pt-1 text-sm leading-6 text-slate-600">{{ $step }}</p></li>
                        @endforeach
                    </ol>
                    <a href="{{ route('products.index') }}" class="btn btn-red mt-7">Shop Products</a>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-brand-navy p-7 text-white shadow-soft sm:p-9">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase text-red-300">Team or bulk order</span>
                    <h2 class="mt-4 font-display text-3xl font-bold uppercase">Send details for review</h2>
                    <p class="mt-3 text-sm leading-6 text-white/75">Use this path for rosters, special materials, large quantities, artwork review, multiple delivery needs, or a required deadline.</p>
                    <ol class="mt-6 grid gap-4">
                        @foreach(['Choose the product type, sport, expected quantity, and target delivery date.', 'Prepare sizes, player names, numbers, colors, logos, sponsor marks, and artwork.', 'Submit a quote request with as much accurate detail as possible.', 'Review the proposed price, production timeline, shipping plan, and artwork proof.', 'Accept the quotation and complete the required payment before production.'] as $i => $step)
                            <li class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-red font-display font-bold text-white">{{ $i + 1 }}</span><p class="pt-1 text-sm leading-6 text-white/75">{{ $step }}</p></li>
                        @endforeach
                    </ol>
                    <a href="{{ route('quote.request') }}" class="btn btn-red mt-7">Request a Quote</a>
                </article>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Custom-order checklist" title="Prepare These Details Before You Submit" description="Accurate inputs reduce revisions and help produce a useful quotation." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="Q" title="Quantity and sizes" description="Total pieces, size breakdown, youth/adult split, and any extras for future players." />
                <x-storefront.content.icon-card icon="D" title="Design details" description="Team colors, product style, placement, names, numbers, logos, and sponsor marks." />
                <x-storefront.content.icon-card icon="F" title="Artwork files" description="Preferred vector or high-resolution files and any reference image showing the intended look." />
                <x-storefront.content.icon-card icon="T" title="Timeline and delivery" description="Required in-hand date, event date, destination, and whether split delivery may be needed." />
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_.85fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Before confirming</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Review every custom detail</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Names, numbers, sizes, spelling, logo placement, colors, and delivery information should be checked before approval. A customer-approved proof becomes an important production reference.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach(['Product and fabric direction', 'Quantity and size list', 'Player names and numbers', 'Logo and sponsor placement', 'Color references', 'Production estimate', 'Shipping address and method', 'Price and payment schedule'] as $item)
                        <div class="flex gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-600"><span class="text-brand-red">✓</span>{{ $item }}</div>
                    @endforeach
                </div>
            </div>
            <x-storefront.content.callout title="Important for deadlines" tone="amber">
                Production and shipping estimates can change when artwork is revised, sizes are submitted late, payment is delayed, or the order changes after quotation. Build reasonable time into event planning and do not rely on an unconfirmed date.
            </x-storefront.content.callout>
        </div>
    </section>

    <x-storefront.content.cta title="Choose Your Ordering Path" description="Shop eligible products directly or send the complete requirements for a team and bulk quotation." />
</x-layouts.storefront>
