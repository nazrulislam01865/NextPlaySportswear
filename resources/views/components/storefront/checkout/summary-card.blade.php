@props(['summary' => [], 'currentStep' => 'information', 'orderIdempotencyKey' => null])
@php
    $items = $summary['items'] ?? [];
    $rural = (float) ($summary['rural_surcharge'] ?? 0);
    $addressComplete = filled(data_get(session('nextplay_checkout', []), 'shipping_address.address.postal_code'));
    $total = (float) ($summary['total'] ?? 0);
    // Reuse the same quantity-based production and selected shipping windows as cart configurator.
    $lines = collect($summary['fulfillment_lines'] ?? []);
    $etaMin = (int) $lines->max('estimated_minimum_days');
    $etaMax = (int) $lines->max('estimated_maximum_days');
    $eta = ($etaMin > 0 && $etaMax >= $etaMin)
        ? $etaMin.'–'.$etaMax.' business days'
        : 'Calculated from selected production & shipping';
@endphp
<aside class="np-checkout__card np-checkout__summary" aria-label="Order summary">
    <div class="np-checkout__summary-head">
        <h2>Order summary</h2>
        <span>{{ $summary['quantity'] ?? 0 }} items</span>
        <a href="{{ route('cart.index') }}">Edit cart</a>
    </div>
    @foreach($items as $item)
        <div class="np-checkout__summary-item">
            <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['alt'] ?? $item['product']['title'] ?? 'Product' }}" loading="lazy">
            <div><strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Product' }}</strong>
                <p>{{ $item['customization']['design_option'] ?? 'Custom design' }}</p>
                <p>Qty {{ $item['quantity'] ?? 1 }} × ${{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</p>
            </div>
            <strong>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</strong>
        </div>
    @endforeach
    <dl class="np-checkout__prices">
        <div><dt>Merchandise subtotal</dt><dd>${{ number_format((float) ($summary['subtotal'] ?? 0), 2) }}</dd></div>
        <div><dt>Customization</dt><dd>${{ number_format((float) ($summary['customization_total'] ?? 0), 2) }}</dd></div>
        @if((float) ($summary['discount'] ?? 0) > 0)<div><dt>Discount</dt><dd>−${{ number_format((float) $summary['discount'], 2) }}</dd></div>
@endif
        <div><dt>Shipping{{ data_get($summary, 'shipping_method.title') ? ' ('.data_get($summary, 'shipping_method.title').')' : '' }}</dt><dd>${{ number_format((float) ($summary['shipping_base'] ?? 0), 2) }}</dd></div>
        <div><dt>Remote-area charge</dt><dd>
            @if($currentStep === 'shipping' && ! $addressComplete)
                Calculated on continue
            @else
                ${{ number_format($rural, 2) }}
            @endif
        </dd></div>
        <div><dt>Estimated tax</dt><dd>${{ number_format((float) ($summary['tax'] ?? 0), 2) }}</dd></div>
    </dl>
    <div class="np-checkout__summary-total">
        <strong>{{ $currentStep === 'shipping' && ! $addressComplete ? 'Estimated total (USD)' : 'Total (USD)' }}</strong>
        <strong>${{ number_format($total, 2) }}</strong>
    </div>
    @if(($summary['remote_price_missing'] ?? false))<p class="nps-error" role="alert">The remote-area charge is not configured for this quantity. Please contact support before paying.</p>
@endif
    @if($currentStep === 'shipping' && ! $addressComplete)<p class="np-checkout__fine">Excludes any remote-area charge until your shipping address is saved.</p>
@endif
    <div class="np-checkout__notice np-checkout__delivery">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 7h12v11H2zM14 10h4l4 4v4h-8zM2 11h12"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>
        <div><strong>Estimated production &amp; delivery</strong><b>{{ $eta }}</b><p>Production starts after artwork approval.</p></div>
    </div>
    @if($currentStep === 'review')
        <form class="nps-form np-checkout__final-payment" method="POST" action="{{ route('checkout.place-order.submit') }}" data-single-submit x-data="{ accepted: false, paymentBlocked: @js((bool) ($summary['remote_price_missing'] ?? false)) }">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
            <input type="hidden" name="confirm_details" value="1">
            <label class="np-checkout__check">
                <input type="checkbox" name="terms" value="1" x-model="accepted" required>
                <span>I agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms and Conditions</a> and have read the <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
            </label>
            @error('terms')
                <p class="nps-error" role="alert">{{ $message }}</p>
            @enderror
            <button class="np-checkout__button np-checkout__full" type="submit" :disabled="!accepted || paymentBlocked">Place order &amp; pay ${{ number_format($total, 2) }}</button>
            <p class="np-checkout__fine np-checkout__center">Secure payment via Stripe</p>
        </form>
    @endif
    <p class="np-checkout__secure-footer">&#128274; Secure checkout</p>
</aside>
