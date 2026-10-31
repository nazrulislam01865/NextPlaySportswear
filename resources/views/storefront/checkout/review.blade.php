@php
    $info = $state['information'] ?? [];
    $ship = $state['shipping_address']['address'] ?? [];
    $bill = $state['billing_address']['address'] ?? [];
    $same = $state['billing_address']['same_as_shipping'] ?? true;
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary" :order-idempotency-key="$orderIdempotencyKey">
    <x-storefront.checkout.panel title="Review your order" description="Check your details before placing your order.">
        <div class="np-checkout__review">
            <div class="np-checkout__review-group">
                <div class="np-checkout__review-head"><strong>Items</strong><a href="{{ route('cart.index') }}">Edit cart</a></div>
                @foreach(($summary['items'] ?? []) as $item)
                    <div class="np-checkout__review-item">
                        <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['title'] ?? 'Product' }}" loading="lazy">
                        <div><strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Product' }}</strong><p>{{ $item['customization']['design_option'] ?? 'Custom design' }}</p><p>Quantity: {{ $item['quantity'] ?? 1 }} &nbsp; Unit price: ${{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</p><details><summary>View customisation details</summary><p>{{ $item['customization']['size_summary'] ?? 'No size details selected' }}</p></details></div>
                        <strong>${{ number_format((float) ($item['line_total'] ?? 0), 2) }}</strong>
                    </div>
                @endforeach
            </div>
            <div class="np-checkout__review-grid">
                <div class="np-checkout__review-group"><div class="np-checkout__review-head"><strong>Contact</strong><a href="{{ route('checkout.information') }}">Edit</a></div><p>{{ trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? '')) }}<br>{{ $info['email'] ?? '' }}<br>{{ $info['phone'] ?? '' }}</p></div>
                <div class="np-checkout__review-group"><div class="np-checkout__review-head"><strong>Shipping address</strong><a href="{{ route('checkout.shipping-address') }}">Edit</a></div><p>{{ trim(($ship['first_name'] ?? '').' '.($ship['last_name'] ?? '')) }}<br>{{ $ship['address_line_1'] ?? '' }}<br>{{ $ship['city'] ?? '' }}, {{ $ship['postal_code'] ?? '' }}<br>{{ $ship['country'] ?? '' }}</p></div>
                <div class="np-checkout__review-group"><div class="np-checkout__review-head"><strong>Billing address</strong><a href="{{ route('checkout.billing-address') }}">Edit</a></div>
@if($same)<p>Same as shipping address</p>
@else
<p>{{ $bill['address_line_1'] ?? '' }}<br>{{ $bill['city'] ?? '' }}, {{ $bill['postal_code'] ?? '' }}<br>{{ $bill['country'] ?? '' }}</p>
@endif @if(!empty($state['billing_address']['invoice_details']['company_name']))<p>{{ $state['billing_address']['invoice_details']['company_name'] }}</p>
@endif @if(!empty($state['billing_address']['invoice_details']['tax_number']))<p>{{ $state['billing_address']['invoice_details']['tax_number'] }}</p>
@endif
</div>
            </div>
            <div class="np-checkout__review-group"><div class="np-checkout__review-head"><strong>Delivery</strong><a href="{{ route('cart.index') }}">Edit</a></div><p>{{ data_get($summary, 'shipping_method.title', 'Configured shipping') }}<br>
@if((float) ($summary['rural_surcharge'] ?? 0) > 0)Remote-area delivery charge: ${{ number_format((float) $summary['rural_surcharge'], 2) }}
@else Remote-area check completed: no additional charge.
@endif
</p></div>
            <div class="np-checkout__notice np-checkout__info"><span>ⓘ</span><div><strong>Artwork approval before production</strong><p>We'll send your artwork for approval before production begins.</p></div></div>
            <div class="np-checkout__review-group"><div class="np-checkout__review-head"><strong>Order note</strong><a href="{{ route('checkout.information') }}">Edit</a></div><p>{{ filled($info['order_note'] ?? null) ? $info['order_note'] : 'No note added' }}</p></div>
        </div>
        <div class="np-checkout__actions"><a class="np-checkout__back" href="{{ route('checkout.billing-address') }}">← &nbsp; Back to billing</a></div>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
