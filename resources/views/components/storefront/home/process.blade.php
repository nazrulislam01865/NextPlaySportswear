@props(['steps' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $steps = collect($steps)->filter(fn ($step) => filled(data_get($step, 'title')))->values();
@endphp

<section id="process" class="process-section" aria-labelledby="process-heading">
    <div class="container">
        <x-storefront.home.section-heading
            class="process-intro"
            title-id="process-heading"
            :title="$text('title', 'Simple Ordering Process')"
            :description="filled($text('description')) ? $text('description', 'A clear process from product selection to delivery.') : null"
        />

        <div class="process" aria-label="Ordering process steps">
            @foreach($steps as $step)
                <article class="process-step">
                    <span class="process-number">{{ $loop->iteration }}</span>

                    @php
                        $stepImage = filled(data_get($step, 'image')) ? (string) data_get($step, 'image') : null;
                    @endphp

                    <div class="process-card{{ $stepImage ? ' process-card--has-image' : '' }}">
                        @if($stepImage)
                            <div class="process-illustration process-illustration--image">
                                <img
                                    src="{{ $stepImage }}"
                                    alt="{{ data_get($step, 'image_alt') ?: data_get($step, 'title') }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        @else
                            <div class="process-illustration process-illustration--fallback" aria-hidden="true">
                                <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="48" cy="48" r="34" fill="#ffffff" stroke="var(--np-color-navy-dark)" stroke-width="4"/>
                                    <path d="M32 50 43 61 66 36" stroke="var(--np-color-primary)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        @endif

                        <h3>{{ data_get($step, 'title') }}</h3>
                        <span class="process-card-divider" aria-hidden="true"></span>
                        <p>{{ data_get($step, 'description') }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        @if(filled($text('primary_label')))
            <p class="home-center-action">
                <a class="btn btn-primary process-cta np-home-action" href="{{ $text('primary_url', '#products') }}">{{ $text('primary_label', 'Start Your Order') }} <span aria-hidden="true">→</span></a>
            </p>
        @endif
    </div>
</section>
