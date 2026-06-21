<x-storefront.order.shell
    :seo="$seo"
    :order="$order"
    :summary="$orderSummary"
    badge="Order {{ $order['order_number'] }}"
    badge-tone="info"
    title="Order Details"
    description="Review order items, customization details, payment status, addresses, shipping method, invoice access, and production progress in one place."
    status-title="{{ \Illuminate\Support\Str::headline($order['status']) }}"
    status-subtitle="Placed {{ $order['placed_display'] }}"
>
    <x-slot:actions>
        <a href="{{ route('orders.track') }}" class="btn btn-red">Track Order</a>
        <a href="{{ route('orders.invoice.legacy') }}" class="btn btn-white">Invoice</a>
    </x-slot:actions>

    <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-card lg:p-7">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start">
            <div>
                <h2 class="text-2xl font-black text-brand-ink">Order Information</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Placed on {{ $order['placed_display'] }} · {{ $order['totals']['quantity'] }} total item(s)</p>
            </div>
            <x-storefront.order.status-badge :status="$order['status']" />
        </div>

        <div class="mt-6">
            <h3 class="text-lg font-black text-brand-ink">Items & Customization</h3>
            <div class="mt-4 grid gap-3">
                @foreach ($order['items'] as $item)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                        <div class="flex flex-col gap-4 sm:flex-row">
                            <img src="{{ $item['image'] }}" alt="{{ $item['alt'] }}" loading="lazy" class="h-24 w-24 rounded-xl object-cover">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col justify-between gap-2 sm:flex-row">
                                    <h4 class="text-base font-black text-brand-ink">{{ $item['title'] }}</h4>
                                    <strong class="text-brand-ink">${{ number_format($item['line_total'], 2) }}</strong>
                                </div>
                                <div class="mt-3 grid gap-2 text-sm font-semibold leading-6 text-slate-600 md:grid-cols-2">
                                    <p><strong class="text-slate-900">Quantity:</strong> {{ $item['quantity'] }}</p>
                                    <p><strong class="text-slate-900">Sizes:</strong> {{ $item['customization']['size_summary'] }}</p>
                                    <p><strong class="text-slate-900">Design:</strong> {{ $item['customization']['design_option'] }}</p>
                                    <p><strong class="text-slate-900">Artwork:</strong> {{ $item['customization']['artwork_status'] }}</p>
                                </div>
                                @if ($item['customization']['notes'] !== '')
                                    <p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm font-semibold leading-6 text-slate-600">{{ $item['customization']['notes'] }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        @php($shippingLines = app(\App\Services\Order\OrderExperienceService::class)->addressLines($order['shipping_address'] ?? []))
        @php($billingLines = (($order['billing_address']['same_as_shipping'] ?? false) === true) ? ['Same as shipping address'] : app(\App\Services\Order\OrderExperienceService::class)->addressLines($order['billing_address'] ?? []))

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <x-storefront.order.info-box title="Shipping Address">
                @foreach ($shippingLines as $line)
                    {{ $line }}<br>
                @endforeach
            </x-storefront.order.info-box>
            <x-storefront.order.info-box title="Billing Address">
                @foreach ($billingLines as $line)
                    {{ $line }}<br>
                @endforeach
            </x-storefront.order.info-box>
            <x-storefront.order.info-box title="Shipping Method">
                {{ $order['shipping_method']['title'] ?? 'Standard Shipping' }}<br>
                {{ $order['shipping_method']['eta'] ?? 'Estimated after production' }}
            </x-storefront.order.info-box>
            <x-storefront.order.info-box title="Payment Method">
                {{ $order['payment_method']['label'] ?? 'Secure payment provider' }}<br>
                Payment status: {{ \Illuminate\Support\Str::headline($order['payment_status']) }}
            </x-storefront.order.info-box>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('orders.track') }}" class="btn btn-red">Track Order</a>
            <a href="{{ route('orders.invoice.legacy') }}" class="btn btn-white">Download Invoice</a>
            <a href="{{ route('quote.request') }}" class="btn btn-white">Ask About Order</a>
        </div>
    </section>
</x-storefront.order.shell>
