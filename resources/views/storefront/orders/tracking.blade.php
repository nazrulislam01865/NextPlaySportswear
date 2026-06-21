<x-storefront.order.shell
    :seo="$seo"
    :order="$order"
    :summary="$orderSummary"
    badge="Track your custom order"
    badge-tone="info"
    title="Track Order Status"
    description="Enter your order number and email to verify access and check design review, production, shipping, and delivery status."
    status-title="{{ \Illuminate\Support\Str::headline($order['status']) }}"
    status-subtitle="Estimated delivery: {{ $order['estimated_delivery'] }}"
>
    <x-slot:actions>
        <a href="{{ route('orders.details.legacy') }}" class="btn btn-red">Order Details</a>
        <a href="{{ route('products.index') }}" class="btn btn-white">Continue Shopping</a>
    </x-slot:actions>

    <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card lg:p-7">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start">
            <div>
                <h2 class="text-2xl font-black text-brand-ink">Tracking Lookup</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">For privacy, guest tracking must verify the order number and email used at checkout.</p>
            </div>
            <form method="POST" action="{{ route('orders.track.lookup') }}" class="grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-[1fr_1fr_auto] lg:min-w-[560px]">
                @csrf
                <label class="grid gap-1 text-sm font-black text-slate-700">
                    Order number
                    <input name="order_number" value="{{ old('order_number', $order['is_demo'] ? '' : $order['order_number']) }}" placeholder="NP-10482" class="h-11 rounded-xl border border-slate-300 px-3 text-sm font-semibold outline-none focus:border-brand-blue">
                </label>
                <label class="grid gap-1 text-sm font-black text-slate-700">
                    Email
                    <input type="email" name="email" value="{{ old('email', $order['is_demo'] ? '' : $order['customer_email']) }}" placeholder="you@example.com" class="h-11 rounded-xl border border-slate-300 px-3 text-sm font-semibold outline-none focus:border-brand-blue">
                </label>
                <button type="submit" class="btn btn-red self-end">Track</button>
            </form>
        </div>

        <div class="mt-6">
            <h3 class="text-lg font-black text-brand-ink">Tracking Timeline</h3>
            <p class="mt-1 text-sm font-semibold text-slate-600">Real-time production and shipping milestones will appear here after order verification.</p>
            <div class="mt-5">
                <x-storefront.order.timeline :timeline="$timeline" />
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold leading-6 text-blue-800">
            Tracking number will appear after your order leaves production and ships with the carrier. Custom orders may require proof approval before production starts.
        </div>

        <x-storefront.order.support-strip :tips="$supportTips" />
    </section>
</x-storefront.order.shell>
