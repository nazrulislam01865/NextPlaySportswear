@props([
    'slides' => [],
])

@php
    $fallbackSlide = [[
        'image' => asset('storage/storefront/home/hero.webp'),
        'mobile_image' => asset('storage/storefront/home/hero.webp'),
        'alt' => 'Custom sportswear and team uniforms',
        'image_focal_position' => 'center',
        'overlay_rgba' => 'rgba(13,37,69,.72)',
        'show_content' => true,
        'show_title' => true,
        'title' => 'Made for the Beautiful Game.',
        'show_description' => true,
        'description' => 'Personalised football kits for clubs and teams.',
        'show_primary_button' => true,
        'primary_label' => 'Shop Football Kits',
        'primary_url' => route('products.index'),
        'primary_target' => '_self',
        'show_secondary_button' => true,
        'secondary_label' => 'View Shop',
        'secondary_url' => route('products.index'),
        'secondary_target' => '_self',
        'content_position' => 'left',
        'text_alignment' => 'left',
        'text_theme' => 'light',
    ]];

    $renderSlides = count($slides) > 0 ? $slides : $fallbackSlide;
@endphp

<section class="promo-slider" aria-label="Featured sportswear promotions">
    <div class="promo-track" id="promoSlider" data-storefront-slider>
        @foreach($renderSlides as $index => $slide)
            <article class="promo-slide {{ $index === 0 ? 'active' : '' }}">
                @php
                    $desktopImage = $slide['image'] ?? asset('storage/storefront/home/hero.webp');
                    $mobileImage = $slide['mobile_image'] ?? $desktopImage;
                    $imageAlt = $slide['alt'] ?? 'Homepage banner';
                    $imagePosition = str_replace('-', ' ', $slide['image_focal_position'] ?? 'center');
                    $isEnabled = static fn (string $key): bool => filter_var($slide[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $showContent = $isEnabled('show_content');
                    $showTitle = $isEnabled('show_title');
                    $showDescription = $isEnabled('show_description');
                    $showPrimaryButton = $isEnabled('show_primary_button');
                    $showSecondaryButton = $isEnabled('show_secondary_button');
                    $title = trim((string) ($slide['title'] ?? ''));
                    $description = trim((string) ($slide['description'] ?? ''));
                    $primaryLabel = trim((string) ($slide['primary_label'] ?? ''));
                    $secondaryLabel = trim((string) ($slide['secondary_label'] ?? ''));
                    $primaryUrl = $slide['primary_url'] ?? '#';
                    $secondaryUrl = $slide['secondary_url'] ?? '#';
                    $primaryTarget = $slide['primary_target'] ?? '_self';
                    $secondaryTarget = $slide['secondary_target'] ?? '_self';
                    $contentPosition = $slide['content_position'] ?? 'left';
                    $textAlignment = $slide['text_alignment'] ?? 'left';
                    $textTheme = $slide['text_theme'] ?? 'light';
                    $hasVisibleContent = $showContent && (
                        ($showTitle && filled($title))
                        || ($showDescription && filled($description))
                        || ($showPrimaryButton && filled($primaryLabel))
                        || ($showSecondaryButton && filled($secondaryLabel))
                    );
                    $cleanPrimaryLabel = preg_replace('/\s*(?:→|->|&rarr;|\x{2192})\s*$/u', '', $primaryLabel);
                @endphp
                <picture>
                    <source media="(max-width: 767px)" srcset="{{ $mobileImage }}" sizes="100vw">
                    <source media="(min-width: 768px)" srcset="{{ $desktopImage }}" sizes="100vw">
                    <img
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                        @if($index === 0) fetchpriority="high" @endif
                        decoding="async"
                        src="{{ $desktopImage }}"
                        alt="{{ $imageAlt }}"
                        style="object-position: {{ $imagePosition }}"
                        width="2560"
                        height="960"
                    >
                </picture>
                @if($hasVisibleContent)
                    <div class="promo-overlay" style="background: {{ $slide['overlay_rgba'] ?? 'rgba(13,37,69,.72)' }}"></div>

                    <div class="promo-content">
                        <div class="promo-copy position-{{ $contentPosition }} align-{{ $textAlignment }} theme-{{ $textTheme }}">

                            @if($showTitle && filled($title))
                                <h1>{{ $title }}</h1>
                            @endif

                            @if($showDescription && filled($description))
                                <p>{{ $description }}</p>
                            @endif

                            @if(($showPrimaryButton && filled($primaryLabel)) || ($showSecondaryButton && filled($secondaryLabel)))
                                <div class="promo-actions">
                                    @if($showPrimaryButton && filled($primaryLabel))
                                        <a
                                            href="{{ $primaryUrl }}"
                                            target="{{ $primaryTarget }}"
                                            @if($primaryTarget === '_blank') rel="noopener noreferrer" @endif
                                            class="btn btn-primary np-home-action promo-btn-primary"
                                        >
                                            <span class="promo-btn-text">{{ $cleanPrimaryLabel }}</span>
                                            <svg class="promo-btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </a>
                                    @endif

                                    @if($showSecondaryButton && filled($secondaryLabel))
                                        <a
                                            href="{{ $secondaryUrl }}"
                                            target="{{ $secondaryTarget }}"
                                            @if($secondaryTarget === '_blank') rel="noopener noreferrer" @endif
                                            class="btn btn-outline np-home-action promo-btn-secondary"
                                        >{{ $secondaryLabel }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </article>
        @endforeach

        @if(count($renderSlides) > 1)
            <div class="promo-controls" aria-label="Slider controls">
                <div class="promo-dots" role="tablist" aria-label="Slider count indicators">
                    @foreach($renderSlides as $index => $slide)
                        <button
                            class="promo-dot {{ $index === 0 ? 'active' : '' }}"
                            type="button"
                            role="tab"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-label="Go to slide {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
                <div class="promo-nav-arrows" aria-label="Slider navigation arrows">
                    <button class="promo-arrow promo-prev" type="button" aria-label="Previous slide">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button class="promo-arrow promo-next" type="button" aria-label="Next slide">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</section>
