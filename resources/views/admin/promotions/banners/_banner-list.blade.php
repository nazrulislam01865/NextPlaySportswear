<x-admin.section-card
    :title="'Banners ('.$banners->count().')'"
    description="Drag to reorder banners. Banners are shown on your store according to their placement and schedule."
>
    <div class="np-banner-list" data-sale-banner-sort-list data-reorder-url="{{ route('admin.promotions.banners.reorder') }}">
        @forelse($banners as $banner)
            @php
                $status = $bannerStatuses[$banner->id] ?? 'Draft';
                $statusClass = match ($status) {
                    'Active' => 'is-active',
                    'Scheduled' => 'is-scheduled',
                    'Ended' => 'is-ended',
                    'Paused today' => 'is-scheduled',
                    default => 'is-draft',
                };
                $campaign = $banner->campaign;
                $scheduleStart = $banner->inherit_campaign_schedule ? $campaign?->starts_at : $banner->starts_at;
                $scheduleEnd = $banner->inherit_campaign_schedule ? $campaign?->ends_at : $banner->ends_at;
                $scheduleTimezone = $bannerScheduleTimezones[$banner->id] ?? 'UTC';
                $placementSummary = collect((array) $banner->placements)
                    ->map(fn ($placement): string => $placementLabels[$placement] ?? (string) $placement)
                    ->filter()
                    ->implode(', ');
            @endphp
            <article
                class="np-banner-list-item {{ $selected?->id === $banner->id ? 'is-selected' : '' }}"
                draggable="true"
                data-sale-banner-id="{{ $banner->id }}"
            >
                <button type="button" class="np-banner-drag-handle" aria-label="Drag {{ $banner->name }} to reorder" title="Drag to reorder">⠿</button>
                <div class="np-banner-list-thumb">
                    <img src="{{ \App\Support\PublicMedia::storedPathUrl($banner->desktop_image_path) }}" alt="">
                </div>
                <div class="np-banner-list-copy">
                    <strong>{{ $banner->name }}</strong>
                    <span>Placement: {{ $placementSummary !== '' ? $placementSummary : 'No active placement' }}</span>
                    <span>Linked campaign: {{ $campaign?->name ?? 'None' }}</span>
                    <div class="np-banner-list-meta">
                        @if($scheduleStart && $scheduleEnd)
                            <span>▣ {{ $scheduleStart->copy()->timezone($scheduleTimezone ?: 'UTC')->format('j M Y') }} – {{ $scheduleEnd->copy()->timezone($scheduleTimezone ?: 'UTC')->format('j M Y') }}</span>
                        @else
                            <span>▣ No schedule</span>
                        @endif
                        <span class="np-banner-status {{ $statusClass }}"><i></i>{{ $status }}</span>
                    </div>
                </div>
                <div class="np-banner-list-actions">
                    <a class="btn btn-white np-banner-edit-button" href="{{ route('admin.promotions.banners.index', ['banner' => $banner->id]) }}">Edit</a>
                    <span class="np-banner-more" aria-hidden="true">⋮</span>
                </div>
            </article>
        @empty
            <div class="np-banner-empty-state">
                <strong>No sale banners yet.</strong>
                <span>Add your first banner to start placing campaign artwork on storefront listing pages.</span>
            </div>
        @endforelse
    </div>
</x-admin.section-card>
