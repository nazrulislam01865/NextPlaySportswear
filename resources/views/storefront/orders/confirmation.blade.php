@php
    $order = $order ?? [];
    $items = $order['items'] ?? [];
    $totals = $order['totals'] ?? [];
    $paymentPaid = ($order['payment_status'] ?? '') === 'paid';
    $isDemo = (bool) ($order['is_demo'] ?? false);
    $lines = (array) data_get($order, 'shipping_method.lines', []);
    $shipments = (array) data_get($order, 'shipping_address.shipments', []);
    $sameShipment = data_get($order, 'shipping_address.shipment_plan', 'separate') === 'together';
    $placed = !empty($order['placed_at']) ? \Illuminate\Support\Carbon::parse($order['placed_at']) : now();
    $orderNumber = (string) ($order['order_number'] ?? '');
    $orderId = (int) ($order['id'] ?? 0);
@endphp
<x-layouts.storefront :seo="$seo">
    <section class="np-order-confirmation">
        <div class="site-container np-order-confirmation__container">
            <nav class="np-checkout__breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('cart.index') }}">Cart</a><span>/</span><span aria-current="page">Checkout</span></nav>
            <div class="np-order-confirmation__hero"><span class="np-order-confirmation__check" aria-hidden="true">✓</span><div><h1>{{ $isDemo ? 'Order confirmation preview' : 'Thank you. Your order is confirmed.' }}</h1><p>{{ $paymentPaid ? "We've received your payment and your order is now being processed." : 'Your order has been received. Payment confirmation is being verified securely.' }}</p></div></div>
            @if($isDemo)<div class="np-order-confirmation__preview">This is a design preview, not a real order or payment receipt.</div>@endif
            <div class="np-order-confirmation__columns">
                <div class="np-order-confirmation__left">
                    <div class="np-order-confirmation__order-overview np-order-confirmation__card">
                        <div><span>Order number</span><strong>{{ $orderNumber }}</strong><p>Placed on {{ $placed->format('j M Y') }}</p></div>
                        <div><span>Payment status</span><span class="np-order-confirmation__paid {{ $paymentPaid ? 'is-paid' : '' }}">{{ $paymentPaid ? '✓ Paid' : '◷ Pending verification' }}</span><strong>USD {{ number_format((float) ($totals['total'] ?? 0), 2) }}</strong><p>{{ $paymentPaid ? 'Single payment received' : 'Status updates after secure verification' }}</p></div>
                        <div><span>Main contact</span><strong class="np-order-confirmation__email">{{ $order['customer_email'] ?? data_get($order, 'information.email', '') }}</strong></div>
                    </div>
                    <div class="np-order-confirmation__items-heading"><h2>Order items ({{ count($items) }})</h2><p>Each product has its own production, artwork and shipment tracking within this order.</p></div>
                    @foreach($items as $index => $item)
                        @php
                            $line = $lines[$index] ?? [];
                            $recipient = data_get($item, 'customization.shipping_recipient.address')
                                ?: data_get($shipments, ($sameShipment ? 0 : $index).'.address', data_get($order, 'shipping_address.address', []));
                            $startDays = max(0, (int) data_get($line, 'production.minimum_days', 0));
                            $minDays = max(0, (int) ($line['estimated_minimum_days'] ?? 0));
                            $maxDays = max($minDays, (int) ($line['estimated_maximum_days'] ?? 0));
                        @endphp
                        <article class="np-order-confirmation__item np-order-confirmation__card">
                            <div class="np-order-confirmation__item-reference"><span>Sub-order {{ $index + 1 }} of {{ count($items) }}</span><strong>{{ $orderNumber }}-{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</strong></div>
                            <div class="np-order-confirmation__item-content"><img src="{{ $item['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['title'] ?? 'Custom product' }}" loading="lazy">
                                <div class="np-order-confirmation__item-description"><strong>{{ $item['title'] ?? 'Custom product' }}</strong><p>{{ data_get($item, 'customization.design_option', 'Custom design') }}</p><p>Qty: {{ (int) ($item['quantity'] ?? 1) }} pieces</p><p>Recipient: {{ trim(($recipient['first_name'] ?? '').' '.($recipient['last_name'] ?? '')) ?: ($order['customer_name'] ?? '') }}{{ !empty($recipient['city']) ? ', '.$recipient['city'] : '' }}</p></div>
                                <div class="np-order-confirmation__item-timeline"><span class="np-order-confirmation__awaiting">◷ &nbsp; Awaiting artwork approval</span><dl><div><dt>Estimated dispatch</dt><dd>{{ $placed->copy()->addWeekdays($startDays)->format('j M Y') }}</dd></div><div><dt>Estimated arrival</dt><dd>{{ $placed->copy()->addWeekdays($minDays)->format('j M') }}–{{ $placed->copy()->addWeekdays($maxDays)->format('j M Y') }}</dd></div></dl><p>Production starts after artwork approval. Dates may change.</p></div>
                                <div class="np-order-confirmation__item-actions"><a class="np-order-confirmation__primary" href="{{ route('account.artwork.index') }}">Review Artwork</a><a class="np-order-confirmation__secondary" href="{{ ($orderId ? route('account.orders.show', ['order' => $orderId]) : route('orders.details.legacy')) }}">View order <span aria-hidden="true">→</span></a></div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <aside class="np-order-confirmation__payment np-order-confirmation__card">
                    <div class="np-order-confirmation__payment-heading"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="19" rx="1"/><path d="M8 8h8m-8 4h8"/></svg><h2>Payment summary</h2></div>
                    <dl class="np-order-confirmation__payment-prices"><div><dt>Merchandise subtotal</dt><dd>${{ number_format((float) ($totals['subtotal'] ?? 0), 2) }}</dd></div>
                        @if((float) ($totals['customization_total'] ?? 0) > 0)<div><dt>Customization</dt><dd>${{ number_format((float) $totals['customization_total'], 2) }}</dd></div>@endif
                        @if((float) ($totals['discount'] ?? 0) > 0)<div><dt>Discount</dt><dd>−${{ number_format((float) $totals['discount'], 2) }}</dd></div>@endif
                        @foreach($lines as $line)
                            <div><dt>Shipping – {{ $line['product'] ?? 'Product' }}</dt><dd>${{ number_format((float) data_get($line, 'shipping.amount', 0), 2) }}</dd></div>
                        @endforeach
                        @php($visibleShipping = collect($lines)->sum(fn ($line) => (float) data_get($line, 'shipping.amount', 0)))
                        @if((float) ($totals['shipping'] ?? 0) - $visibleShipping > 0.009)<div><dt>Other shipping / remote-area charge</dt><dd>${{ number_format((float) ($totals['shipping'] ?? 0) - $visibleShipping, 2) }}</dd></div>@endif
                        <div><dt>Estimated tax</dt><dd>${{ number_format((float) ($totals['tax'] ?? 0), 2) }}</dd></div></dl>
                    <div class="np-order-confirmation__payment-total"><strong>Total (USD)</strong><strong>${{ number_format((float) ($totals['total'] ?? 0), 2) }}</strong></div>
                    <div class="np-order-confirmation__payment-status {{ $paymentPaid ? 'is-paid' : '' }}"><span aria-hidden="true">{{ $paymentPaid ? '✓' : '◷' }}</span><div><strong>{{ $paymentPaid ? 'Paid' : 'Payment verification pending' }}</strong><p>{{ $paymentPaid ? 'Single payment of USD '.number_format((float) ($totals['total'] ?? 0), 2).' received.' : 'Order received. Paid status appears only after secure provider verification.' }}</p></div></div>
                    <a class="np-order-confirmation__primary np-order-confirmation__full" href="{{ ($orderId ? \Illuminate\Support\Facades\URL::signedRoute('account.orders.invoice.download', ['order' => $orderId]) : route('orders.invoice.legacy')) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 2h9l4 4v16H6zM15 2v5h4M9 13h7m-7 4h7"/></svg>Download Invoice</a>
                    <a class="np-order-confirmation__secondary np-order-confirmation__full" href="{{ $orderId ? route('account.orders.shipments', ['order' => $orderId]) : route('orders.track') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 7h12v10H2zM14 10h4l4 4v3h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>Track All Items</a>
                    <div class="np-order-confirmation__notice"><span aria-hidden="true">ⓘ</span><div><strong>Each product progresses independently.</strong><p>Tracking appears when its shipment is dispatched. Production and delivery estimates depend on artwork approval.</p></div></div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.storefront>
