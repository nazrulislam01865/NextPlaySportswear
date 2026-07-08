@props(['steps' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $steps = collect($steps)->filter(fn ($step) => filled(data_get($step, 'title')))->values();
@endphp

<section id="process" class="process-section" aria-labelledby="process-heading">
    <div class="container">
        <div class="process-intro">
            <span class="small-red">{{ $text('eyebrow', 'How it works') }}</span>
            <h2 id="process-heading">{{ $text('title', 'Simple Ordering Process') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'A clear process from product selection to delivery.') }}</p>@endif
        </div>

        <div class="process" aria-label="Ordering process steps">
            @foreach($steps as $step)
                <article class="process-step">
                    <span class="process-number">{{ $loop->iteration }}</span>

                    <div class="process-card">
                        <div class="process-illustration" aria-hidden="true">
                            <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="48" cy="48" r="34" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                <path d="M32 50 43 61 66 36" stroke="#e91d33" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <h3>{{ data_get($step, 'title') }}</h3>
                        <span class="process-card-divider" aria-hidden="true"></span>
                        <p>{{ data_get($step, 'description') }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        @if(filled($text('primary_label')))
            <p class="home-center-action">
                <a class="btn btn-red process-cta" href="{{ $text('primary_url', '#products') }}">{{ $text('primary_label', 'Start Your Order') }} <span aria-hidden="true">→</span></a>
            </p>
        @endif
    </div>
</section>
