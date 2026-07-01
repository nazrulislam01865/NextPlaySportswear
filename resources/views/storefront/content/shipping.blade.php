<x-layouts.storefront :seo="[
    'title' => 'Shipping and Delivery Information | ' . config('storefront.name'),
    'description' => 'Learn how production time, shipping methods, tracking, delivery estimates, address accuracy, and damaged shipments are handled at NextPlay Sportswear.',
]">
    <x-storefront.content.hero
        eyebrow="Shipping and delivery"
        title="Understand Production Time and Transit Time"
        description="Custom products usually require production before they can ship. Delivery planning should consider artwork approval, roster completion, payment, manufacturing, carrier transit, and the destination."
        :image="asset('storage/storefront/content/shipping.webp')"
        image-alt="Packages prepared in a shipping warehouse"
    >
        <a href="{{ route('orders.track') }}" class="btn btn-red">Track an Order</a>
        <a href="#delivery-timeline" class="btn btn-white">View Delivery Steps</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="delivery-timeline">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Order timeline" title="Delivery Has More Than One Stage" description="The shipping estimate shown by a carrier normally begins after the package leaves the production or fulfilment location." />
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                @foreach([
                    ['1', 'Order confirmed', 'Required payment and order details are accepted.'],
                    ['2', 'Artwork approved', 'Custom design and roster information are finalized.'],
                    ['3', 'Production', 'Products are prepared, decorated, and checked.'],
                    ['4', 'Carrier handoff', 'Tracking is created and the package enters transit.'],
                    ['5', 'Delivery', 'The carrier attempts delivery at the submitted address.'],
                ] as [$number, $title, $text])
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-card">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-navy font-display text-xl font-bold text-white">{{ $number }}</span>
                        <h3 class="mt-4 font-extrabold text-brand-ink">{{ $title }}</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_.9fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Shipping methods</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Options depend on the order and destination</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['Standard delivery', 'A cost-conscious option for orders without an urgent delivery requirement.'],
                        ['Expedited delivery', 'Faster transit may be available, but it does not automatically reduce production time.'],
                        ['Large or freight shipment', 'High-volume orders may require special handling, appointment delivery, or multiple cartons.'],
                        ['Split shipment', 'Available only when approved and may add fulfilment, handling, or carrier charges.'],
                    ] as [$title, $text])
                        <article class="rounded-2xl border border-slate-200 bg-white p-5">
                            <h3 class="font-extrabold text-brand-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-5">
                <x-storefront.content.callout title="Address accuracy" tone="navy">Customers are responsible for submitting a complete deliverable address, including apartment, suite, building, organization, and contact details when required.</x-storefront.content.callout>
                <x-storefront.content.callout title="Event-date planning" tone="amber">Choose a required in-hand date earlier than the actual event whenever possible. Expedited shipping cannot recover time lost to late artwork, roster changes, or delayed approval.</x-storefront.content.callout>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Tracking and delivery" title="What to Do After Shipment" description="Use the carrier information and inspect the delivery promptly." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="T" title="Check tracking" description="Tracking can take time to update after a label is created. Use the Track Order page for the latest available status." />
                <x-storefront.content.icon-card icon="A" title="Monitor the address" description="Arrange for someone to receive the delivery when the carrier requires a signature or the package cannot be left safely." />
                <x-storefront.content.icon-card icon="I" title="Inspect cartons" description="Photograph visible damage before opening and keep the packaging while the issue is reviewed." />
                <x-storefront.content.icon-card icon="R" title="Report issues quickly" description="Contact support promptly with the order number, photos, affected quantities, and a description of the issue." />
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-card">
                <h2 class="font-display text-3xl font-bold uppercase text-brand-ink">Possible causes of delay</h2>
                <ul class="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                    @foreach(['Artwork revisions', 'Incomplete roster or size list', 'Payment verification', 'Product availability', 'Production capacity', 'Carrier interruption', 'Weather conditions', 'Incorrect address', 'Delivery appointment', 'Customs or regulatory review'] as $item)
                        <li class="flex gap-2"><span class="text-brand-red">✓</span>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-3xl bg-brand-navy p-7 text-white shadow-soft">
                <h2 class="font-display text-3xl font-bold uppercase">Lost, damaged, or missing items</h2>
                <p class="mt-3 text-sm leading-6 text-white/75">Contact support with the order number, carrier tracking, carton count, photos, and a clear list of affected products. Do not discard packaging until the carrier or support team confirms it is no longer required.</p>
                <a href="{{ route('contact') }}" class="btn btn-red mt-6">Report a Delivery Issue</a>
            </div>
        </div>
    </section>

    <x-storefront.content.cta title="Need a Shipping Update?" description="Track the order first, then contact support if the shipment is delayed, damaged, incomplete, or delivered to the wrong location." primary-label="Track Order" :primary-href="route('orders.track')" secondary-label="Contact Support" :secondary-href="route('contact')" />
</x-layouts.storefront>
