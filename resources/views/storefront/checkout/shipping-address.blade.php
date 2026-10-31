@php
    $info = $state['information'] ?? [];
    $saved = $state['shipping_address'] ?? [];
    $together = ($info['shipment_plan'] ?? 'separate') === 'together';
    $items = $summary['items'] ?? [];
    $shipments = $saved['shipments'] ?? [];
    $count = $together ? 1 : count($items);
    $countries = app(\App\Services\Storefront\AddressGeography::class)->countries();
    $callingCodes = $countries;
    $savedShippingAddresses = $savedShippingAddresses ?? [];

@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <form id="np-checkout-shipment-form" class="nps-form np-checkout__form np-checkout__stage-form np-checkout__shipment-form" method="POST" novalidate data-single-submit data-contact-name="{{ trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? '')) }}" data-contact-email="{{ $info['email'] ?? '' }}" data-contact-phone="{{ $info['phone'] ?? '' }}" data-remote-status-url="{{ route('checkout.remote-area-status') }}" action="{{ route('checkout.shipping-address.store') }}">
        @csrf
        @if($errors->any())<div class="np-checkout__form-alert" role="alert">Please correct the highlighted delivery details to continue.</div>@endif
        @foreach($items as $index => $item)
            @continue($together && $index > 0)
            @php
                $renderItem = $item;
                $renderLine = $summary['fulfillment_lines'][$index] ?? [];
                if ($together) {
                    $renderItem['quantity'] = $summary['quantity'] ?? $item['quantity'] ?? 0;
                    $allLines = collect($summary['fulfillment_lines'] ?? []);
                    $renderLine['production']['minimum_days'] = (int) $allLines->max(fn ($line) => (int) data_get($line, 'production.minimum_days', 0));
                    $renderLine['estimated_minimum_days'] = (int) $allLines->max(fn ($line) => (int) ($line['estimated_minimum_days'] ?? 0));
                    $renderLine['estimated_maximum_days'] = (int) $allLines->max(fn ($line) => (int) ($line['estimated_maximum_days'] ?? 0));
                    $renderLine['shipping'] = [
                        'label' => 'Shipping configured per product',
                        'amount' => (float) ($summary['product_shipping_total'] ?? 0),
                        'display_amount' => '$'.number_format((float) ($summary['product_shipping_total'] ?? 0), 2),
                    ];
                }
            @endphp
            <x-storefront.checkout.shipment-card :item="$renderItem" :line="$renderLine" :index="$index" :count="$count" :info="$info" :address="$shipments[$index] ?? ($index === 0 ? $saved : [])" :countries="$countries" :calling-codes="$callingCodes" :saved-addresses="$savedShippingAddresses" :selected-saved-address-id="$index === 0 ? ($saved['saved_address_id'] ?? null) : null" :together="$together" :shipping-lines="$summary['fulfillment_lines'] ?? []" />
        @endforeach
        @error('shipments')<p class="nps-error" role="alert">{{ $message }}</p>@enderror
        <div class="np-checkout__form-end np-checkout__stage-actions">
            <a class="np-checkout__back" href="{{ route('checkout.information') }}">← Back to contact &amp; plan</a>
            <button type="submit" class="np-checkout__button np-checkout__button--navy">Continue to review <span aria-hidden="true">→</span></button>
        </div>

    </form>
</x-storefront.checkout.shell>
