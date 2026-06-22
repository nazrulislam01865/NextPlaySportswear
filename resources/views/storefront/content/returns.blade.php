<x-layouts.storefront :seo="[
    'title' => 'Returns, Refunds, and Exchanges | ' . config('storefront.name'),
    'description' => 'Review return, refund, exchange, cancellation, damaged item, and custom product guidance for NextPlay Sportswear orders.',
]">
    <x-storefront.content.hero
        eyebrow="After-purchase support"
        title="Returns Depend on the Product and Order Type"
        description="Standard, unused products may qualify for return under the applicable conditions. Customized products are made from approved details and usually have more limited return and cancellation options."
        image="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1100&q=80"
        image-alt="Customer service representative reviewing an order"
    >
        <a href="#eligibility" class="btn btn-red">Check Eligibility</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Start a Support Request</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="eligibility">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="General guidance" title="Common Eligibility Scenarios" description="Final eligibility depends on the product, customization, order status, condition, timing, and applicable law." />
            <div class="grid gap-6 lg:grid-cols-3">
                <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-7">
                    <span class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">May be eligible</span>
                    <h2 class="mt-2 font-display text-3xl font-bold uppercase text-emerald-950">Standard unused products</h2>
                    <ul class="mt-5 grid gap-3 text-sm text-emerald-900/75">
                        <li>• Unworn, unwashed, and unused</li><li>• Original tags and packaging retained</li><li>• Request made within the stated return window</li><li>• Product is not final sale or otherwise excluded</li>
                    </ul>
                </article>
                <article class="rounded-3xl border border-amber-200 bg-amber-50 p-7">
                    <span class="text-xs font-black uppercase tracking-[.16em] text-amber-700">Requires review</span>
                    <h2 class="mt-2 font-display text-3xl font-bold uppercase text-amber-950">Damaged or incorrect items</h2>
                    <ul class="mt-5 grid gap-3 text-sm text-amber-900/75">
                        <li>• Item arrived damaged or defective</li><li>• Product differs from the approved order details</li><li>• Quantity or size received is incorrect</li><li>• Clear photos and order information are provided promptly</li>
                    </ul>
                </article>
                <article class="rounded-3xl border border-red-200 bg-red-50 p-7">
                    <span class="text-xs font-black uppercase tracking-[.16em] text-red-700">Usually not returnable</span>
                    <h2 class="mt-2 font-display text-3xl font-bold uppercase text-red-950">Personalized products</h2>
                    <ul class="mt-5 grid gap-3 text-sm text-red-900/75">
                        <li>• Names, numbers, logos, or custom colors</li><li>• Customer-approved artwork and roster details</li><li>• Wrong size selected by the customer</li><li>• Preference change after production begins</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_.9fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Request process</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">How to report a return or quality issue</h2>
                <ol class="mt-6 grid gap-4">
                    @foreach([
                        ['Contact support promptly', 'Provide the order number, affected item, quantity, and reason for the request.'],
                        ['Send clear evidence', 'Include photos of the item, packaging, labels, defect, damage, or incorrect customization.'],
                        ['Wait for instructions', 'Do not ship a product back until a return authorization or other direction is provided.'],
                        ['Package approved returns safely', 'Use suitable packaging and the instructed carrier or tracking method.'],
                        ['Allow inspection and processing', 'Refund, replacement, repair, exchange, or credit depends on the approved resolution.'],
                    ] as $i => [$title, $text])
                        <li class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-navy font-display text-lg font-bold text-white">{{ $i + 1 }}</span>
                            <div><h3 class="font-extrabold text-brand-ink">{{ $title }}</h3><p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="grid gap-5">
                <x-storefront.content.callout title="Keep the packaging" tone="navy">For damage, missing items, or carrier claims, retain cartons, labels, packing materials, and photographs until the issue has been resolved.</x-storefront.content.callout>
                <x-storefront.content.callout title="Do not send unauthorized returns" tone="red">A return sent without approval may be refused, delayed, or returned to the sender. Contact support first for instructions.</x-storefront.content.callout>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Refunds and exchanges" title="How Approved Resolutions May Work" description="The available outcome depends on the issue and the stage at which it is reported." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="R" title="Replacement" description="An affected item may be remade or replaced when a verified production or fulfilment error occurred." />
                <x-storefront.content.icon-card icon="E" title="Exchange" description="Eligible standard products may be exchanged, subject to stock and the applicable return conditions." />
                <x-storefront.content.icon-card icon="$" title="Refund" description="Approved refunds are generally issued to the original payment method or another legally permitted method." />
                <x-storefront.content.icon-card icon="C" title="Store credit" description="A credit may be offered where agreed and allowed, especially when a replacement product is not suitable." />
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-6 lg:grid-cols-2">
            <x-storefront.content.callout title="Cancellation before production" tone="amber">Contact support immediately. Cancellation may be possible before artwork, materials, or production are committed, but fees or non-refundable work may still apply.</x-storefront.content.callout>
            <x-storefront.content.callout title="Cancellation after approval" tone="red">Customized orders may no longer be cancellable after artwork approval, material commitment, or production start. Any available refund may be reduced by completed work and committed costs.</x-storefront.content.callout>
        </div>
    </section>

    <x-storefront.content.cta title="Need to Report an Order Issue?" description="Send the order number, affected products, quantities, photos, and a clear description so support can review the available resolution." primary-label="Contact Support" :primary-href="route('contact')" secondary-label="View Order Details" :secondary-href="route('orders.track')" />
</x-layouts.storefront>
