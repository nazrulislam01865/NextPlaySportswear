@props(['steps' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $steps = collect($steps)->filter(fn ($step) => filled(data_get($step, 'title')))->values();
@endphp

<section id="process" class="process-section np-ordering-section" aria-labelledby="process-heading">
    <div class="container">
        <x-storefront.home.section-heading
            class="process-intro"
            title-id="process-heading"
            :title="$text('title', 'Simple Ordering Process')"
            :description="filled($text('description')) ? $text('description') : null"
        />

        <div class="np-ordering-grid" aria-label="Ordering process steps">
            @foreach($steps as $step)
                <x-storefront.home.order-process-card :step="$step" :number="$loop->iteration" :last="$loop->last" />
            @endforeach
        </div>

        @if(filled($text('primary_label')))
            <p class="home-center-action">
                <a class="btn btn-primary process-cta np-home-action" href="{{ $text('primary_url', '#products') }}">{{ $text('primary_label') }} <span aria-hidden="true">→</span></a>
            </p>
        @endif
    </div>
</section>
