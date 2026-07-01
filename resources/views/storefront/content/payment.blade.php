<x-layouts.storefront :seo="[
    'title' => 'Payment Information | ' . config('storefront.name'),
    'description' => 'Learn about secure checkout, payment authorization, taxes, quote deposits, payment status, failed payments, refunds, and card-data handling.',
]">
    <x-storefront.content.hero
        eyebrow="Secure checkout guidance"
        title="Know When and How Payment Is Collected"
        description="Regular online orders follow the payment method selected during checkout. Team and bulk orders may use a deposit or staged payment schedule stated in the accepted quotation."
        :image="asset('storage/storefront/content/payment.webp')"
        image-alt="Secure online payment on a mobile device"
    >
        <a href="#payment-types" class="btn btn-red">View Payment Guidance</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Ask a Payment Question</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="payment-types">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Order types" title="Payment Can Differ by Purchase Path" description="Review the final checkout summary or accepted quote before submitting payment." />
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-7 shadow-card sm:p-9">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase text-brand-blue">Online checkout</span>
                    <h2 class="mt-4 font-display text-3xl font-bold uppercase text-brand-ink">Regular product orders</h2>
                    <ul class="mt-5 grid gap-3 text-sm leading-6 text-slate-600">
                        <li>✓ Product subtotal, discounts, shipping, and estimated tax are reviewed before placement.</li>
                        <li>✓ The payment provider may authorize or capture the amount when the order is submitted.</li>
                        <li>✓ A payment result page should confirm whether the transaction succeeded or failed.</li>
                        <li>✓ An order confirmation does not override a failed or reversed payment status.</li>
                    </ul>
                </article>
                <article class="rounded-3xl bg-brand-navy p-7 text-white shadow-soft sm:p-9">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase text-red-300">Quote-based order</span>
                    <h2 class="mt-4 font-display text-3xl font-bold uppercase">Team and bulk payments</h2>
                    <ul class="mt-5 grid gap-3 text-sm leading-6 text-white/75">
                        <li>✓ The quotation should state price, taxes, shipping direction, expiration, and payment schedule.</li>
                        <li>✓ A deposit may be required before artwork finalization, material commitment, or production.</li>
                        <li>✓ The remaining balance may be required before production completion or shipment.</li>
                        <li>✓ Changes to quantity, design, product, or delivery can require a revised quotation.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Payment status" title="What Different Results Mean" description="The payment provider and order system should remain synchronized before an order moves forward." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="✓" title="Paid" description="The transaction has been confirmed and the order can proceed subject to product and customization requirements." />
                <x-storefront.content.icon-card icon="…" title="Pending" description="The transaction is still being verified, processed, reviewed, or settled. Do not submit repeated payments without checking status." />
                <x-storefront.content.icon-card icon="!" title="Failed" description="The provider did not approve the transaction. Review the message, billing information, card status, or alternative method." />
                <x-storefront.content.icon-card icon="↶" title="Refunded" description="An approved amount has been sent back through the permitted method. Bank processing time may still apply." />
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_.9fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Security and privacy</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Protect payment details</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Sensitive card information should be entered only into the secure payment form provided during checkout. Never send full card numbers, security codes, passwords, or online-banking credentials through email, contact forms, artwork notes, or chat.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['Use the official checkout', 'Confirm the website address and use the configured payment provider.'],
                        ['Review billing details', 'Name, address, ZIP code, and other information may need to match the payment account.'],
                        ['Avoid repeated charges', 'If the result is unclear, review the order and payment status before trying again.'],
                        ['Report suspicious activity', 'Contact the payment provider and support promptly if an unauthorized transaction is suspected.'],
                    ] as [$title, $text])
                        <article class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="font-extrabold text-brand-ink">{{ $title }}</h3><p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p></article>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-5">
                <x-storefront.content.callout title="Taxes and shipping" tone="navy">The final payable amount may include applicable sales tax, delivery charges, handling, customization, artwork, rush service, or other disclosed costs.</x-storefront.content.callout>
                <x-storefront.content.callout title="Payment does not replace approval" tone="amber">A custom order may still require complete roster information and artwork approval after payment. Production timing can begin only when all required conditions are satisfied.</x-storefront.content.callout>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Payment troubleshooting" title="Before Trying Again" description="A failed payment can be caused by the bank, payment provider, browser, billing information, fraud screening, or transaction limits." />
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach(['Confirm the card number, expiration, security code, and billing ZIP code.', 'Check available funds, transaction limits, and bank fraud alerts.', 'Avoid refreshing or submitting the payment repeatedly.', 'Try a supported alternative payment method when available.', 'Check whether the payment is pending before creating another order.', 'Contact support with the order reference—never with full card details.'] as $item)
                    <div class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-600 shadow-card"><span class="font-black text-brand-red">✓</span>{{ $item }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <x-storefront.content.cta title="Have a Payment or Order Status Question?" description="Use the order reference and the payment result shown on the storefront when contacting support. Never send sensitive card information." primary-label="Contact Support" :primary-href="route('contact')" secondary-label="Track Order" :secondary-href="route('orders.track')" />
</x-layouts.storefront>
