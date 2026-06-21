<x-storefront.order.shell
    :seo="$seo"
    :order="$order"
    :summary="$orderSummary"
    badge="✓ Payment return received"
    badge-tone="success"
    title="Payment Completed Successfully"
    description="Your payment return was received. For production security, the final paid status should only be confirmed after the verified Stripe, PayPal, or payment-provider webhook."
    status-title="${{ number_format($order['totals']['total'], 2) }}"
    status-subtitle="Payment method: {{ $order['payment_method']['label'] ?? 'Secure provider' }}"
>
    <x-slot:actions>
        <a href="{{ route('order.confirmation') }}" class="btn btn-red">View Confirmation</a>
        <a href="{{ route('orders.invoice.legacy') }}" class="btn btn-white">View Invoice</a>
    </x-slot:actions>

    <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card lg:p-7">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start">
            <div>
                <h2 class="text-2xl font-black text-brand-ink">Payment Details</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Your order is ready for the next secure verification step and design review workflow.</p>
            </div>
            <x-storefront.order.status-badge status="verified" tone="success">Secure return</x-storefront.order.status-badge>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <x-storefront.order.info-box title="Order Number">{{ $order['order_number'] }}</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Payment Method">{{ $order['payment_method']['label'] ?? 'Secure payment provider' }}</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Amount">${{ number_format($order['totals']['total'], 2) }}</x-storefront.order.info-box>
            <x-storefront.order.info-box title="Payment Protection">Final paid status must be set by verified webhook, not by frontend redirect.</x-storefront.order.info-box>
        </div>

        <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold leading-6 text-green-800">
            No raw card data is stored by NextPlay. Payment should be handled by PCI-compliant hosted checkout or tokenized provider fields.
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('orders.track') }}" class="btn btn-red">Track Order</a>
            <a href="{{ route('orders.invoice.legacy') }}" class="btn btn-white">Download Invoice</a>
        </div>
    </section>
</x-storefront.order.shell>
