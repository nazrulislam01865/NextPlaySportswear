@props(['steps' => []])

<div class="np-track-timeline" aria-label="Order progress">
    @foreach ($steps as $step)
        @php($state = $step['state'] ?? 'pending')
        <div class="np-track-timeline-step np-track-timeline-step--{{ $state }}">
            <div class="np-track-timeline-marker" aria-hidden="true">
                @if ($state === 'done')
                    <svg viewBox="0 0 24 24"><path d="m7.6 12.3 2.8 2.8 6-6" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @endif
            </div>
            <div class="np-track-timeline-copy">
                <h3>{{ $step['title'] }}</h3>
                <p>{{ $step['description'] }}</p>
            </div>
        </div>
    @endforeach
</div>
