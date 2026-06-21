<x-storefront.order.shell
    :seo="$seo"
    :order="$order"
    :summary="$orderSummary"
    badge="Payment not completed"
    badge-tone="danger"
    title="Payment Could Not Be Processed"
    description="The payment was declined, cancelled, or interrupted. Your cart/order details are preserved so you can retry safely or choose another payment method."
    status-title="Action Needed"
    status-subtitle="Order is not confirmed as paid yet."
>
    <x-slot:actions>
        <a href="{{ route('checkout.payment-method') }}" class="btn btn-red">Try Payment Again</a>
        <a href="#support-help" class="btn btn-white">Contact Support</a>
    </x-slot:actions>

    <section id="support-help" class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card lg:p-7">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start">
            <div>
                <h2 class="text-2xl font-black text-brand-ink">Choose What To Do Next</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Use one safe action below. Do not refresh repeatedly while payment is processing.</p>
            </div>
            <x-storefront.order.status-badge status="failed" tone="danger">Payment issue</x-storefront.order.status-badge>
        </div>

        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold leading-6 text-red-800">
            For safety, NextPlay does not mark an order as paid from a browser return page. Payment status must be confirmed by provider webhook or admin verification.
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <x-storefront.order.info-box title="Try again">Use the same method after checking card details and billing address.</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Use another method">Choose another card, PayPal, or request an invoice for bulk orders.</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Check billing address">Make sure billing details match the payment account.</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Need help?">Contact support with order reference {{ $order['order_number'] }}.</x-storefront.order.info-box>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('checkout.payment-method') }}" class="btn btn-red">Change Payment Method</a>
            <a href="{{ route('cart.index') }}" class="btn btn-white">Back to Cart</a>
        </div>
    </section>
</x-storefront.order.shell>
