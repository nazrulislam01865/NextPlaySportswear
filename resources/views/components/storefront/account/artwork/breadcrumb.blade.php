@props(['order' => null, 'end' => null])
<nav class="np-artwork-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('account.dashboard') }}">Account</a>
    <x-storefront.account.icon name="chevron-right" :size="13" />
    @if($order)
        <a href="{{ route('account.artwork.index') }}">Artwork Approvals</a>
        <x-storefront.account.icon name="chevron-right" :size="13" />
        @if($end)
            <a href="{{ route('account.artwork.show', $order) }}">Order #{{ $order->order->order_number }}</a>
            <x-storefront.account.icon name="chevron-right" :size="13" />
            <span>{{ $end }}</span>
        @else
            <span>Order #{{ $order->order->order_number }}</span>
        @endif
    @else
        <span>Artwork Approvals</span>
    @endif
</nav>
