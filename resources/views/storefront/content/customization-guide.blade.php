<x-layouts.storefront :seo="[
    'title' => 'Customization Guide | ' . config('storefront.name'),
    'description' => 'Explore names, numbers, logos, colors, fabrics, printing, embroidery, sublimation, and artwork proof options for custom sportswear.',
]">
    <x-storefront.content.hero
        eyebrow="Make it yours"
        title="Plan Every Detail of Your Custom Gear"
        description="Customization can range from a simple name and number to a complete team identity with logos, sponsor marks, fabrics, colors, player rosters, and coordinated accessories."
        image="https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1100&q=80"
        image-alt="Custom sports uniforms and team gear"
    >
        <a href="#custom-options" class="btn btn-red">Explore Options</a>
        <a href="{{ route('quote.request') }}" class="btn btn-white">Start a Custom Quote</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="custom-options">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Available by product" title="Common Customization Options" description="Not every product supports every option. The product page or quotation should identify what is available." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['#', 'Names and numbers', 'Personalize individual garments for players, staff, supporters, or event participants.'],
                    ['◎', 'Team and sponsor logos', 'Add authorized team crests, organization marks, sponsors, or event branding.'],
                    ['◐', 'Custom colors', 'Coordinate primary, secondary, trim, and accent colors around a team or brand identity.'],
                    ['T', 'Text and typography', 'Choose approved wording, capitalization, placement, and type direction for the design.'],
                    ['F', 'Fabric direction', 'Select from available performance, mesh, stretch, fleece, or casual apparel materials.'],
                    ['P', 'Print locations', 'Plan front, back, sleeve, shoulder, leg, cap, bag, or other product-specific placement.'],
                    ['R', 'Roster ordering', 'Submit size, name, number, and quantity information for every participant.'],
                    ['B', 'Branded packaging', 'Available for selected bulk, promotional, or organization orders when quoted.'],
                ] as [$icon, $title, $text])
                    <x-storefront.content.icon-card :icon="$icon" :title="$title" :description="$text" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Decoration choices" title="Choose the Right Production Method" description="The product material, design, order quantity, intended use, and budget help determine the best method." />
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach([
                    ['Sublimation', 'Full-color artwork becomes part of compatible performance fabric, making it useful for detailed jerseys and uniform designs.', ['Detailed gradients and patterns', 'Names and numbers integrated into the design', 'Best on compatible synthetic fabrics']],
                    ['Embroidery', 'Thread creates a textured, professional result for caps, polos, jackets, bags, and selected apparel.', ['Durable raised appearance', 'Suitable for many logos and text', 'Small details may need simplification']],
                    ['Screen Printing', 'Ink is applied through screens and can be efficient for repeated designs, shirts, hoodies, and event apparel.', ['Good for clean graphic shapes', 'Pricing may depend on color count', 'Works well for many bulk applications']],
                    ['Heat Transfer', 'Applied graphics, names, and numbers can support personalization and selected small-to-medium production runs.', ['Useful for individual names and numbers', 'Multiple material options', 'Product and care requirements vary']],
                ] as [$title, $description, $items])
                    <article class="rounded-3xl border border-slate-200 bg-white p-7 shadow-card">
                        <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
                        <ul class="mt-5 grid gap-3 text-sm text-slate-600">
                            @foreach($items as $item)<li class="flex gap-3"><span class="text-brand-red">✓</span>{{ $item }}</li>@endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container grid gap-10 lg:grid-cols-[1fr_.9fr] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Design workflow</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">A practical customization process</h2>
                <div class="mt-6 grid gap-4">
                    @foreach([
                        ['Choose the base product', 'Start with the sport, garment, fabric direction, quantity, and intended use.'],
                        ['Organize the design details', 'Prepare colors, logos, names, numbers, placements, and reference images.'],
                        ['Submit sizes and roster', 'Use one consistent format and verify every line before submission.'],
                        ['Review pricing and proof', 'Confirm the quotation, visual layout, production estimate, and shipping plan.'],
                        ['Approve and produce', 'Production begins only after the required approval and payment conditions are satisfied.'],
                    ] as $i => [$title, $text])
                        <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-navy font-display text-lg font-bold text-white">{{ $i + 1 }}</span>
                            <div><h3 class="font-extrabold text-brand-ink">{{ $title }}</h3><p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-3 shadow-soft">
                <img loading="lazy" src="https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=950&q=80" alt="Custom sports uniforms displayed together" class="h-[520px] w-full rounded-2xl object-cover">
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-6 lg:grid-cols-2">
            <x-storefront.content.callout title="Color expectations" tone="amber">Screens, fabric, thread, print methods, lighting, and material lots can affect color appearance. Provide a color reference and understand that an exact screen-to-product match may not be possible.</x-storefront.content.callout>
            <x-storefront.content.callout title="Customer responsibility" tone="red">Review all names, numbers, spelling, sizes, logos, and placements before approval. You must also have permission to reproduce submitted marks and artwork.</x-storefront.content.callout>
        </div>
    </section>

    <x-storefront.content.cta title="Build a Custom Order Around Your Team" description="Browse customizable products or send the full roster, artwork, quantity, and deadline for a detailed quotation." primary-label="Shop Custom Products" :primary-href="route('products.index')" secondary-label="Request Custom Quote" :secondary-href="route('quote.request')" />
</x-layouts.storefront>
