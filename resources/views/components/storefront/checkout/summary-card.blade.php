@props(['summary' => [], 'currentStep' => 'information', 'orderIdempotencyKey' => null])
@php
    $items = $summary['items'] ?? [];
    $lines = $summary['fulfillment_lines'] ?? [];
    $quantity = (int) ($summary['quantity'] ?? 0);
    $itemCount = count($items);
    $shipping = (float) ($summary['shipping_base'] ?? 0);
    $rural = (float) ($summary['rural_surcharge'] ?? 0);
    $total = (float) ($summary['total'] ?? 0);
    $saleSavings = round((float) ($summary['sale_savings'] ?? collect($items)->sum('offer_savings')), 2);
    $totalDiscount = round((float) ($summary['total_discount'] ?? ($saleSavings + (float) ($summary['discount'] ?? 0))), 2);
    $isFirst = $currentStep === 'information';
    $isFinal = $currentStep === 'review';
@endphp
<aside class="np-checkout__card np-checkout__summary" aria-label="Order summary">
    <div class="np-checkout__summary-head"><h2>Order summary</h2><span>{{ $isFirst ? $itemCount.' products / '.$quantity.' pieces' : $itemCount.' items' }}</span><a href="{{ route('cart.index') }}">Edit cart</a></div>
    <div class="np-checkout__summary-items">
        @foreach($items as $index => $item)
            @php
                // Cart line_total already includes allocated shipping. Separate it
                // visually so shipping is never presented as merchandise twice.
                $lineMerchandise = round((float) ($item['line_subtotal'] ?? 0) + (float) ($item['customization_total'] ?? 0), 2);
                $lineSale = round(max(0, (float) ($item['offer_savings'] ?? 0)), 2);
                $lineShipping = round((float) ($item['product_shipping_total'] ?? data_get($lines, $index.'.shipping.amount', 0)), 2);
                $lineTotal = round($lineMerchandise + $lineShipping, 2);
                $method = data_get($lines, $index.'.shipping.label') ?: data_get($item, 'customization.fulfillment.shipping.label', 'Configured shipping');
                $qty = max(1, (int) ($item['quantity'] ?? 1));
            @endphp
            <article class="np-checkout__summary-product" data-checkout-product="{{ $item['key'] ?? $index }}">
                <div class="np-checkout__summary-item">
                    <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['alt'] ?? $item['product']['title'] ?? 'Custom product' }}" loading="lazy">
                    <div><strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Custom product' }}</strong><p>{{ $item['customization']['fabric'] ?? $item['customization']['design_option'] ?? 'Custom design' }}</p><p>Qty {{ $qty }} × ${{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</p></div>
                    <strong>${{ number_format($lineMerchandise, 2) }}</strong>
                </div>
                @unless($isFirst)
                    <dl class="np-checkout__product-breakdown">
                        <div><dt>Product merchandise @if($lineSale > 0)(before sale)@endif</dt><dd>${{ number_format((float) ($item['line_subtotal'] ?? 0) + $lineSale, 2) }}</dd></div>
                        @if($lineSale > 0)<div class="np-checkout__product-offer"><dt>Product discount</dt><dd>−${{ number_format($lineSale, 2) }}</dd></div>@endif
                        @if((float) ($item['customization_total'] ?? 0) > 0)
                            <div><dt>Customization</dt><dd>${{ number_format((float) $item['customization_total'], 2) }}</dd></div>
                        @endif
                        <div><dt>{{ $method }} <span>(configured shipping)</span></dt><dd>${{ number_format($lineShipping, 2) }}</dd></div>
                        <div class="np-checkout__product-line-total"><dt>Product total @if((float) ($summary['discount'] ?? 0) > 0)<small>(before order-wide discounts)</small>@endif</dt><dd>${{ number_format($lineTotal, 2) }}</dd></div>
                    </dl>
                @endunless
            </article>
        @endforeach
    </div>
    <dl class="np-checkout__prices">
        <div><dt>Merchandise subtotal @if($saleSavings > 0)<small>(before offers)</small>@endif</dt><dd>${{ number_format((float) ($summary['subtotal'] ?? 0) + $saleSavings, 2) }}</dd></div>
        @if((float) ($summary['customization_total'] ?? 0) > 0)<div><dt>Customization</dt><dd>${{ number_format((float) $summary['customization_total'], 2) }}</dd></div>@endif
        @if($totalDiscount > 0)<div class="np-checkout__discount-total"><dt>Total discounts</dt><dd>−${{ number_format($totalDiscount, 2) }}</dd></div>@endif
        @php
            $productShippingTotal = round((float) ($summary['product_shipping_total'] ?? 0), 2);
            $additionalShipping = round(max(0, $shipping - $productShippingTotal), 2);
        @endphp
        @if($isFirst)
            <div><dt>Shipping</dt><dd>${{ number_format((float) ($summary['shipping'] ?? $shipping), 2) }}</dd></div>
        @else
            @if($productShippingTotal > 0 || $additionalShipping <= 0)
                <div><dt>Product shipping <small>(itemized above)</small></dt><dd>${{ number_format($productShippingTotal, 2) }}</dd></div>
            @endif
            @if($additionalShipping > 0)
                <div><dt>Additional order shipping</dt><dd>${{ number_format($additionalShipping, 2) }}</dd></div>
            @endif
            @if($rural > 0)<div><dt>Remote-area charge</dt><dd>${{ number_format($rural, 2) }}</dd></div>@endif
        @endif
        <div><dt>Estimated tax</dt><dd>${{ number_format((float) ($summary['tax'] ?? 0), 2) }}</dd></div>
    </dl>
    <div class="np-checkout__summary-total"><strong>Total (USD)</strong><strong>${{ number_format($total, 2) }}</strong></div>
    @if(($summary['remote_price_missing'] ?? false))
        <div class="np-checkout__form-alert" role="alert">The remote-area delivery charge is missing from the price table. <a href="{{ route('checkout.shipping-address') }}">Review your address</a> or contact support.</div>
    @endif
    @if($isFirst)
        <div class="np-checkout__summary-information">
            <div class="np-checkout__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 7h12v10H2zM14 10h4l4 4v3h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg><div><strong>Production starts after separate artwork approval for each product.</strong><p>Dates are estimates and may change.</p><hr><strong>This is one parent order with a single payment.</strong><p>Each product is a separate sub-order with its own tracking once dispatched.</p></div></div>
        </div>
    @elseif($currentStep === 'shipping')
        <div class="np-checkout__summary-information"><div class="np-checkout__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 7h12v10H2zM14 10h4l4 4v3h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg><div><strong>Production starts after artwork approval</strong><p>Production for each product starts after separate artwork approval. Estimated dates may change.</p></div></div></div>
        <div class="np-checkout__info-item np-checkout__summary-extra"><span aria-hidden="true">☷</span><div><strong>One order, one payment</strong><p>This is a single order with one combined payment. Each product has its own production schedule and shipment tracking.</p></div></div>
        <div class="np-checkout__info-item np-checkout__summary-extra"><span aria-hidden="true">ⓘ</span><div><strong>Main contact receives all updates</strong><p>The main contact will receive all order updates. Each recipient will receive their own shipment updates.</p></div></div>
    @elseif($isFinal)
        <div class="np-checkout__summary-information"><div class="np-checkout__info-item"><span aria-hidden="true">▣</span><div><strong>One payment for the entire order.</strong><p>This single payment covers all products in your order. Each product has its own artwork approval and shipment tracking. Production starts after separate artwork approval for each product.</p></div></div></div>
        <div class="np-checkout__summary-warning"><span aria-hidden="true">◷</span><div><strong>Estimated dates (approval dependent)</strong><p>Production starts after separate artwork approval for each product. Estimated dispatch and arrival dates are shown for each item and may change.</p></div></div>
    @endif
</aside>
