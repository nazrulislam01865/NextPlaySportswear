<x-storefront.order.shell
    :seo="$seo"
    :order="$order"
    :summary="$orderSummary"
    badge="✓ Order placed securely"
    badge-tone="success"
    title="Thanks, Your Order Is Confirmed"
    description="We received your custom sportswear order. Your order details are saved and the next step is secure payment verification, invoice review, or design proof preparation."
    status-title="{{ \Illuminate\Support\Str::headline($order['status']) }}"
    status-subtitle="Estimated delivery: {{ $order['estimated_delivery'] }}"
>
    <x-slot:actions>
        <a href="{{ route('orders.details.legacy') }}" class="btn btn-red">View Order Details</a>
        <a href="{{ route('orders.track') }}" class="btn btn-white">Track Order</a>
    </x-slot:actions>

    <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card lg:p-7">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start">
            <div>
                <h2 class="text-2xl font-black text-brand-ink">What Happens Next?</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Custom orders are checked before production, especially when names, numbers, logos, artwork, or proof approval are included.</p>
            </div>
            <x-storefront.order.status-badge :status="$order['payment_status']" />
        </div>

        <div class="mt-6">
            <x-storefront.order.timeline :timeline="$timeline" />
        </div>

        <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold leading-6 text-green-800">
            Your customization details are saved. Contact support before design approval if you need to change artwork, sizes, names, numbers, or delivery notes.
        </div>

        <x-storefront.order.support-strip :tips="$supportTips" />
    </section>
</x-storefront.order.shell>
