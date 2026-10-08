@props([
    'displayOrderNumber',
    'statusLabel',
    'dateLabel',
    'dateCaption' => 'Order date',
    'steps' => [],
    'item' => [],
    'tracking' => [],
    'order' => [],
    'detailsHref' => '#',
])

@php
    $holidayReason = $order['holiday_adjustment_reason'] ?? ($tracking['holiday_reason'] ?? null);
    $isHolidayAdjusted = (bool) ($order['holiday_adjustment_applied'] ?? filled($holidayReason));
    $estimatedDelivery = $order['estimated_delivery'] ?? ($tracking['estimated_delivery_at'] ?? null);
@endphp

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

    @if ($isHolidayAdjusted && filled($holidayReason))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 mx-5 mt-4 flex items-start gap-3 shadow-sm">
            <span class="grid h-5 w-5 place-items-center rounded-full bg-amber-200 text-xs font-black text-amber-900 shrink-0">ⓘ</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-black uppercase tracking-wider text-[10px] bg-amber-200/90 text-amber-900 px-2 py-0.5 rounded-full">Holiday Notice</span>
                    @if (filled($estimatedDelivery))
                        <span class="font-extrabold text-amber-900">Estimated Delivery: {{ $estimatedDelivery }}</span>
                    @endif
                </div>
                <p class="font-medium text-amber-800 leading-relaxed">{{ $holidayReason }}</p>
            </div>
        </div>
    @elseif (filled($estimatedDelivery))
        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-3 text-xs text-brand-navy mx-5 mt-4 flex items-center justify-between">
            <span class="font-bold text-slate-600">Estimated Delivery:</span>
            <strong class="font-extrabold text-brand-ink">{{ $estimatedDelivery }}</strong>
        </div>
    @endif

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
