@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();

    $defaultHeroSlides = [
        [
            'image' => asset('images/storefront/home/hero-slide-real-team-gear.webp'),
            'heading' => 'Built for Your Team',
            'text' => 'Custom colors, names & numbers',
            'label' => 'Real Team Gear',
            'alt' => 'Custom team jerseys displayed in navy, white, and red colors',
        ],
        [
            'image' => asset('images/storefront/home/hero-slide-uniform-detail.webp'),
            'heading' => 'Uniforms Ready to Customize',
            'text' => 'Choose styles, fabrics, colors, and player details.',
            'label' => 'Jersey Options',
            'alt' => 'Custom jersey product images showing personalization options',
        ],
        [
            'image' => asset('images/storefront/home/hero-slide-product-lineup.webp'),
            'heading' => 'One Store for Team Gear',
            'text' => 'Jerseys, caps, bags, training gear, and event merchandise in one place.',
            'label' => 'Bulk & Team Orders',
            'alt' => 'Custom sportswear product lineup with jerseys and team apparel',
        ],
    ];

    $configuredHeroSlides = collect(data_get($section, 'hero_slides', data_get($section, 'slides', [])))
        ->filter(fn ($slide) => filled(data_get($slide, 'image')))
        ->map(fn ($slide) => [
            'image' => (string) data_get($slide, 'image'),
            'heading' => (string) data_get($slide, 'heading', data_get($slide, 'title', 'Custom Team Gear')),
            'text' => (string) data_get($slide, 'text', data_get($slide, 'description', 'Custom sportswear made for your team.')),
            'label' => (string) data_get($slide, 'label', data_get($slide, 'badge', 'Team Gear')),
            'alt' => (string) data_get($slide, 'alt', data_get($slide, 'image_alt', data_get($slide, 'heading', 'Custom team sportswear'))),
        ])
        ->values();

    $heroSlides = $configuredHeroSlides->isNotEmpty() ? $configuredHeroSlides : collect($defaultHeroSlides);
@endphp

<section class="hero" aria-labelledby="hero-title">
    <div class="container">
        <div>
            <div class="eyebrow">{{ $text('eyebrow', 'Custom sportswear USA') }}</div>
            <h1 id="hero-title">{{ $text('title', 'Custom Sportswear for Teams, Schools, Events, and Fans') }}</h1>
            <p>{{ $text('description', 'Design your own jerseys, uniforms, hoodies, caps, bags, and sports gear. Order online for regular items or request a custom quote for team and bulk orders.') }}</p>
            @if($items->isNotEmpty())
                <ul class="checklist" aria-label="Key custom sportswear services">
                    @foreach($items as $item)
                        <li><span class="tick" aria-hidden="true"></span>{{ data_get($item, 'title') }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="hero-ctas">
                @if(filled($text('primary_label')))
                    <a class="btn btn-red" href="{{ $text('primary_url', '#jersey') }}">{{ $text('primary_label', 'Start Your Order') }}</a>
                @endif
                @if(filled($text('secondary_label')))
                    <a class="btn btn-light" href="{{ $text('secondary_url', '#bulk') }}">{{ $text('secondary_label', 'Request Bulk Quote') }}</a>
                @endif
            </div>
            <div class="trustline">Serving teams, clubs, businesses, and event organizers across the USA.</div>
            <div class="badges" aria-label="Trust badges">
                <div class="badge"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21a9 9 0 1 0-9-9c0 1.7.5 3.3 1.3 4.6L3 21l4.4-1.3A9 9 0 0 0 12 21Z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg> Custom Design Support</div>
                <div class="badge"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Bulk Pricing Available</div>
                <div class="badge"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1V9h4l3 4v4h-2"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg> USA Shipping</div>
            </div>
        </div>
        <div class="hero-card">
            <div class="hero-frame">
                <div class="hero-carousel hero-card-slider" data-hero-card-carousel tabindex="0" role="region" aria-roledescription="carousel" aria-label="Featured custom sportswear photos">
                    <div class="hero-carousel__viewport" data-hero-card-viewport>
                        <div class="hero-carousel__track" data-hero-card-track>
                            @foreach($heroSlides as $index => $slide)
                                <article
                                    class="hero-carousel__slide{{ $index === 0 ? ' is-active' : '' }}"
                                    data-hero-card-slide
                                    data-slide-index="{{ $index }}"
                                    aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                                >
                                    <img
                                        src="{{ data_get($slide, 'image') }}"
                                        data-fallback-src="{{ asset('images/storefront/home/hero.webp') }}"
                                        alt="{{ data_get($slide, 'alt') }}"
                                        width="1200"
                                        height="820"
                                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                        fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                                        decoding="async"
                                    >
                                    @if(filled(data_get($slide, 'label')))
                                        <span class="hero-carousel__label">{{ data_get($slide, 'label') }}</span>
                                    @endif
                                    <div class="hero-carousel__copy">
                                        <h2>{{ data_get($slide, 'heading') }}</h2>
                                        <p>{{ data_get($slide, 'text') }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    @if($heroSlides->count() > 1)
                        <button class="hero-carousel__nav hero-carousel__nav--prev" type="button" data-hero-card-prev aria-label="Previous slide">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button class="hero-carousel__nav hero-carousel__nav--next" type="button" data-hero-card-next aria-label="Next slide">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                        <div class="hero-carousel__dots" role="tablist" aria-label="Choose hero slide">
                            @foreach($heroSlides as $index => $slide)
                                <button
                                    type="button"
                                    class="hero-carousel__dot{{ $index === 0 ? ' is-active' : '' }}"
                                    data-hero-card-dot
                                    role="tab"
                                    aria-label="Show slide {{ $index + 1 }}"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                    <span class="sr-only" data-hero-card-status aria-live="polite">Showing slide 1 of {{ $heroSlides->count() }}</span>
                </div>
            </div>
            <div class="hero-stat"><strong>500+ Teams</strong><span>Trusted across the USA</span></div>
        </div>
    </div>
</section>
