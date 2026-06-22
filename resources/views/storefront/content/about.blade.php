<x-layouts.storefront :seo="[
    'title' => 'About Us | ' . config('storefront.name'),
    'description' => 'Learn how NextPlay Sportswear supports teams, schools, businesses, events, and individual buyers with custom sportswear and clear order guidance.',
]">
    <x-storefront.content.hero
        eyebrow="About NextPlay"
        title="Built Around Teams, Details, and Better Ordering"
        description="NextPlay Sportswear helps teams, schools, clubs, businesses, event organizers, and individual buyers turn an idea into wearable team gear. Our goal is to make custom ordering easier to understand from the first product choice to final delivery."
        image="https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1100&q=80"
        image-alt="Sports team gathering before a game"
    >
        <a href="{{ route('categories.index') }}" class="btn btn-red">Browse Categories</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Talk to Our Team</a>
    </x-storefront.content.hero>

    <section class="section-padding">
        <div class="site-container grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-3 shadow-soft">
                <img loading="lazy" src="https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=950&q=80" alt="Custom sports uniforms prepared for a team" class="h-[360px] w-full rounded-2xl object-cover">
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Our purpose</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase leading-tight text-brand-ink sm:text-4xl">Custom gear should feel organized—not confusing</h2>
                <p class="mt-5 text-sm leading-7 text-slate-600 sm:text-base">A custom order can include product style, sizes, quantities, names, numbers, logos, colors, artwork, production timing, and delivery requirements. We organize those details into a clear process so customers know what is needed before production begins.</p>
                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">The storefront supports regular online shopping as well as quote-based team and bulk orders. That gives individual buyers a straightforward checkout while larger organizations can receive guidance for more complex requirements.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4"><strong class="block text-brand-ink">Online ordering</strong><span class="mt-1 block text-sm text-slate-500">For eligible products and regular quantities.</span></div>
                    <div class="rounded-xl bg-slate-50 p-4"><strong class="block text-brand-ink">Team quote support</strong><span class="mt-1 block text-sm text-slate-500">For rosters, artwork, special pricing, and deadlines.</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="What guides us" title="How We Approach Every Order" description="Practical principles that keep the shopping and customization process clear." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="1" title="Clear product information" description="Customers should understand available options, price direction, minimum quantities, and customization requirements before committing." />
                <x-storefront.content.icon-card icon="2" title="Accurate custom details" description="Names, numbers, sizes, logo placement, colors, and artwork approvals must be organized and reviewable." />
                <x-storefront.content.icon-card icon="3" title="Support before production" description="Complex orders deserve a clear review before materials are committed and production begins." />
                <x-storefront.content.icon-card icon="4" title="Honest order communication" description="Production and delivery estimates should be communicated as estimates unless a specific commitment is confirmed." />
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Who we serve" title="Made for More Than One Type of Buyer" description="The same storefront can support individual products and coordinated organization-wide orders." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Teams & Leagues', 'Uniforms, rosters, practice gear, player bags, and repeat-order support.'],
                    ['Schools & Colleges', 'Athletic programs, spirit wear, event apparel, staff clothing, and promotional products.'],
                    ['Businesses & Events', 'Branded apparel, caps, bags, giveaways, campaign products, and staff orders.'],
                    ['Clubs & Community Groups', 'Shared colors, logos, member sizes, supporter wear, and event merchandise.'],
                    ['Coaches & Organizers', 'Centralized order details, deadlines, artwork review, and delivery planning.'],
                    ['Individual Buyers', 'Single products and smaller custom orders where the selected product allows it.'],
                ] as [$title, $description])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                        <h3 class="font-display text-xl font-bold uppercase text-brand-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-storefront.content.cta
        title="Ready to Start Your Next Order?"
        description="Browse products for regular orders or send your requirements for team, school, event, and bulk pricing."
        primary-label="Shop Products"
        :primary-href="route('products.index')"
        secondary-label="Request a Quote"
        :secondary-href="route('quote.request')"
    />
</x-layouts.storefront>
