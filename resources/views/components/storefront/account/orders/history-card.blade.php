@props(['order'])

@php
    $items = $order->items;
    $visibleItems = $items->take(2);
    $extraItems = max(0, $items->count() - $visibleItems->count());
@endphp

<article class="np-order-history-card">
    <div class="np-order-history-card__media" aria-label="Order product previews">
        @foreach($visibleItems as $item)
            <img src="{{ \App\Support\PublicMedia::url(null, $item->image_url, '/images/product-placeholder.svg') }}" alt="{{ $item->product_name }}" loading="lazy">
        @endforeach
        @if($extraItems > 0)
            <span>+{{ $extraItems }}</span>
        @endif
    </div>

    <div class="np-order-history-card__identity">
        <a href="{{ route('account.orders.show', $order) }}">Order {{ $order->order_number }}</a>
        <p>Placed {{ $order->placed_at?->format('M d, Y') ?? $order->created_at?->format('M d, Y') }} <span>•</span> {{ $order->total_quantity }} item{{ (int) $order->total_quantity === 1 ? '' : 's' }}</p>
    </div>

    <div class="np-order-history-card__statuses">
        <x-storefront.account.orders.history-status-pill :status="$order->status" />
        <x-storefront.account.orders.history-status-pill :status="$order->payment_status" type="payment" />
    </div>

    <div class="np-order-history-card__total">
        <span>Order Total</span>
        <strong>${{ number_format((float) $order->grand_total, 2) }}</strong>
    </div>

    <div class="np-order-history-card__actions">
        @if($order->canPay())
            <a class="btn btn-primary btn-sm" href="{{ route('account.orders.pay', $order) }}">Pay Now</a>
        @endif
        <a class="btn btn-outline btn-sm" href="{{ route('account.orders.show', $order) }}">View Details</a>
    </div>

    <a class="np-order-history-card__chevron" href="{{ route('account.orders.show', $order) }}" aria-label="View order {{ $order->order_number }} details">
        <x-storefront.account.icon name="chevron-right" :size="18" />
    </a>
</article>
