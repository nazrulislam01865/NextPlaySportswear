@php
    $hasTrackingResult = is_array($order)
        && filled($order['order_number'] ?? null)
        && ! $errors->any();
    $rawStatus = $hasTrackingResult
        ? \Illuminate\Support\Str::lower((string) ($order['status'] ?? 'pending_payment'))
        : null;

    $statusLabel = $hasTrackingResult
        ? (string) config('commerce.order_statuses.'.$rawStatus, \Illuminate\Support\Str::headline((string) $rawStatus)->toString())
        : '';

    $displayOrderNumber = $hasTrackingResult ? (string) $order['order_number'] : '';
    $dateLabel = $hasTrackingResult
        ? \Illuminate\Support\Str::before((string) ($order['placed_display'] ?? ''), ' ·')
        : '';

    $sourceItem = $hasTrackingResult ? (array) (($orderSummary['items'][0] ?? null) ?: []) : [];
    $sourceCustomization = (array) ($sourceItem['customization'] ?? []);

    $summaryItem = $hasTrackingResult
        ? [
            'title' => (string) ($sourceItem['title'] ?? 'Custom Product'),
            'image' => (string) ($sourceItem['image'] ?? asset('images/product-placeholder.svg')),
            'alt' => (string) ($sourceItem['alt'] ?? $sourceItem['title'] ?? 'Order item'),
            'size' => (string) ($sourceCustomization['size_summary'] ?? 'To be confirmed'),
            'quantity' => (int) ($sourceItem['quantity'] ?? 1),
            'personalisation' => trim((string) ($sourceCustomization['notes'] ?? ''))
                ?: (string) ($sourceCustomization['design_option'] ?? 'Custom details'),
        ]
        : [];

    $shipments = $hasTrackingResult ? collect((array) ($order['shipments'] ?? [])) : collect();
    $shipment = $shipments->first(fn ($item) => filled(data_get($item, 'tracking_number')))
        ?? $shipments->first();
    $tracking = is_array($shipment) ? $shipment : [];
@endphp

<x-layouts.storefront :seo="$seo">
    <div class="np-track-order-page">
        <section class="np-track-hero">
            <div class="site-container np-track-hero-inner">
                <p class="np-track-hero-eyebrow">NextPlay Sportswear</p>
                <h1>Track Your Order</h1>
                <p class="np-track-hero-subtitle">Check the latest status of your NextPlay order.</p>
            </div>
        </section>

        <section class="np-track-main">
            <div class="site-container">
                <div class="np-track-lookup-wrap">
                    <x-storefront.order.tracking.lookup-card :order="$order ?? []" />
                </div>

                @if ($hasTrackingResult)
                    <div class="np-track-divider" aria-hidden="true"></div>

                    <div class="np-track-result-intro">
                        <h2>Tracking Result</h2>
                        <p>Here is the latest information available for your verified order.</p>
                    </div>

                    <x-storefront.order.tracking.result-card
                        :display-order-number="$displayOrderNumber"
                        :status-label="$statusLabel"
                        :date-label="$dateLabel"
                        date-caption="Order date"
                        :steps="$trackingTimeline"
                        :item="$summaryItem"
                        :tracking="$tracking"
                        :details-href="route('orders.details', ['orderNumber' => $displayOrderNumber])"
                    />
                @endif
            </div>
        </section>
    </div>
</x-layouts.storefront>
