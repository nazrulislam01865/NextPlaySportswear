@props([
    'displayOrderNumber',
    'statusLabel',
    'dateLabel',
    'dateCaption' => 'Order date',
    'steps' => [],
    'item' => [],
    'tracking' => [],
    'detailsHref' => '#',
])

<section class="np-track-result-card" aria-labelledby="track-result-order-number">
    <header class="np-track-result-header">
        <div class="np-track-result-heading-wrap">
            <div class="np-track-result-title-row">
                <h2 id="track-result-order-number">Order #{{ $displayOrderNumber }}</h2>
                <span class="np-track-status-pill">Status: {{ $statusLabel }}</span>
            </div>
            <p>Here’s the latest update on your order.</p>
        </div>
        <div class="np-track-result-date">
            <span>{{ $dateCaption }}:</span>
            <strong>{{ $dateLabel }}</strong>
        </div>
    </header>

    <div class="np-track-result-body">
        <div class="np-track-result-progress">
            <x-storefront.order.tracking.timeline :steps="$steps" />

            <div class="np-track-info-box">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 10.5V17M12 7.25h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <div>
                    @if (filled($tracking['tracking_number'] ?? null))
                        <strong>Tracking number: {{ $tracking['tracking_number'] }}</strong>
                        <p>
                            @if (filled($tracking['carrier'] ?? null))
                                {{ $tracking['carrier'] }}@if (filled($tracking['service'] ?? null)) · {{ $tracking['service'] }}@endif
                            @else
                                {{ $tracking['status_label'] ?? 'Shipment tracking is available.' }}
                            @endif

                            @if (filled($tracking['carrier'] ?? null) && filled($tracking['status_label'] ?? null))
                                · {{ $tracking['status_label'] }}
                            @endif

                            @if (filled($tracking['tracking_url'] ?? null))
                                · <a href="{{ $tracking['tracking_url'] }}" target="_blank" rel="noopener noreferrer">Track shipment</a>
                            @endif
                        </p>
                    @else
                        <strong>Tracking number will appear once your order is shipped.</strong>
                        <p>You’ll be able to view tracking details here when your order is on the way.</p>
                    @endif
                </div>
            </div>
        </div>

        <x-storefront.order.tracking.summary :item="$item" :details-href="$detailsHref" />
    </div>
</section>
