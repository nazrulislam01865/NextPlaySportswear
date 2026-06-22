<x-layouts.storefront :seo="[
    'title' => 'Bulk and Team Ordering Guide | ' . config('storefront.name'),
    'description' => 'Plan team, school, league, event, corporate, and high-volume sportswear orders with guidance on quantities, rosters, artwork, proofs, payment, and delivery.',
]">
    <x-storefront.content.hero
        eyebrow="Team and high-volume orders"
        title="Organize the Details Before Production"
        description="Bulk orders work best when the product, quantity, size breakdown, artwork, player roster, deadline, and delivery plan are prepared together. A quotation gives those requirements one clear reference."
        image="https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1100&q=80"
        image-alt="Large sports team preparing for a game"
    >
        <a href="{{ route('quote.request') }}" class="btn btn-red">Request Bulk Quote</a>
        <a href="#bulk-checklist" class="btn btn-white">View Checklist</a>
    </x-storefront.content.hero>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Best for organizations" title="When to Use the Bulk Ordering Path" description="A quote is especially helpful when the order has many variables or needs coordinated pricing and production." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="T" title="Teams and leagues" description="Uniform sets, rosters, coaching apparel, bags, supporter gear, and repeat player orders." />
                <x-storefront.content.icon-card icon="S" title="Schools and colleges" description="Athletic programs, PE apparel, spirit wear, tournaments, clubs, and campus events." />
                <x-storefront.content.icon-card icon="E" title="Events and promotions" description="Participant shirts, caps, bags, staff apparel, giveaways, and sponsor branding." />
                <x-storefront.content.icon-card icon="B" title="Businesses and groups" description="Branded workwear, corporate events, campaigns, community groups, and organization merchandise." />
            </div>
        </div>
    </section>

    <section class="section-alt section-padding" id="bulk-checklist">
        <div class="site-container grid gap-9 lg:grid-cols-[1.05fr_.95fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Quote preparation</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">What to include in a useful request</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['Product and sport', 'Identify the jersey, uniform, apparel, bag, cap, or promotional item needed.'],
                        ['Expected quantity', 'Provide the total and any separate quantity by style, color, or size group.'],
                        ['Roster or size list', 'List size, name, number, and quantity for each participant where applicable.'],
                        ['Artwork and colors', 'Upload logos, sponsor marks, references, and preferred color information.'],
                        ['Delivery deadline', 'State the required in-hand date and event date, not only the intended order date.'],
                        ['Shipping destination', 'Include ZIP code and whether split shipments or multiple locations are required.'],
                    ] as [$title, $text])
                        <article class="rounded-2xl border border-slate-200 bg-white p-5">
                            <h3 class="font-extrabold text-brand-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="rounded-3xl bg-brand-navy p-7 text-white shadow-soft sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.16em] text-red-300">Example quantity paths</p>
                <h3 class="mt-2 font-display text-3xl font-bold uppercase">Every product can have different minimums</h3>
                <div class="mt-6 grid gap-3">
                    @foreach([
                        ['10+ pieces', 'Small team or coordinated group order'],
                        ['50+ pieces', 'School, event, league, or staff apparel'],
                        ['100+ pieces', 'Promotional, tournament, or organization order'],
                        ['500+ pieces', 'High-volume production quotation'],
                        ['1,000+ pieces', 'Large campaign, distribution, or multi-location order'],
                    ] as [$quantity, $label])
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-white/15 bg-white/10 px-4 py-3"><strong>{{ $quantity }}</strong><span class="text-right text-xs text-white/70">{{ $label }}</span></div>
                    @endforeach
                </div>
                <p class="mt-5 text-xs leading-5 text-white/60">These are examples, not universal minimum quantities. The selected product, decoration, colors, material, and production method determine the actual requirement.</p>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Order workflow" title="How a Bulk Order Moves Forward" description="A structured process reduces missing information and helps keep approvals traceable." />
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                @foreach([
                    ['1', 'Submit requirements', 'Product, quantity, sizes, artwork, and deadline.'],
                    ['2', 'Receive quotation', 'Pricing, options, estimated production, and shipping direction.'],
                    ['3', 'Review proof', 'Check all visible design and roster information.'],
                    ['4', 'Approve and pay', 'Complete the required approval and payment schedule.'],
                    ['5', 'Production and delivery', 'Track progress and shipment when available.'],
                ] as [$number, $title, $text])
                    <article class="relative rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-card">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-navy font-display text-xl font-bold text-white">{{ $number }}</span>
                        <h3 class="mt-4 font-extrabold text-brand-ink">{{ $title }}</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-6 lg:grid-cols-2">
            <x-storefront.content.callout title="Plan for revisions" tone="amber">Artwork changes, missing roster information, substituted products, delayed payment, or revised quantities can change price and timing. Submit final information as early as possible.</x-storefront.content.callout>
            <x-storefront.content.callout title="Do not assume a deadline" tone="red">An event date is not confirmed until the production and shipping plan has been reviewed. Delivery estimates can be affected by approvals, carriers, weather, customs, and other conditions.</x-storefront.content.callout>
        </div>
    </section>

    <x-storefront.content.cta title="Ready to Prepare a Team or Bulk Order?" description="Submit the product, quantity, size list, artwork, destination, and required date for a structured quotation." primary-label="Request Bulk Quote" :primary-href="route('quote.request')" secondary-label="Contact Support" :secondary-href="route('contact')" />
</x-layouts.storefront>
