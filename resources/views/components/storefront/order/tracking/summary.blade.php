@props([
    'item' => [],
    'detailsHref' => '#',
])

<aside class="np-track-result-summary" aria-label="Order summary">
    <h3>Order Summary</h3>

    <div class="np-track-summary-item">
        <div class="np-track-summary-image-wrap">
            <img
                src="{{ $item['image'] ?? asset('images/product-placeholder.svg') }}"
                alt="{{ $item['alt'] ?? ($item['title'] ?? 'Order item') }}"
                class="np-track-summary-image"
            >
        </div>
        <div class="np-track-summary-copy">
            <h4>{{ $item['title'] ?? 'Custom Product' }}</h4>
            <p><span>Size:</span> {{ $item['size'] ?? 'To be confirmed' }}</p>
            <p><span>Quantity:</span> {{ $item['quantity'] ?? 1 }}</p>
            <p><span>Personalisation:</span></p>
            <p>{{ $item['personalisation'] ?? 'Custom details confirmed with your order.' }}</p>
        </div>
    </div>

    <div class="np-track-summary-link-row">
        <a href="{{ $detailsHref }}">View order details</a>
    </div>

    <div class="np-track-summary-help">
        <strong>Need help with your order?</strong>
        <a href="{{ route('contact') }}">
            <span>Contact us</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</aside>
