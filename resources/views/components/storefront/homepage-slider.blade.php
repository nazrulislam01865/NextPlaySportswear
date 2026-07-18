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
        'show_eyebrow' => true,
        'eyebrow' => 'Custom sportswear USA',
        'show_title' => true,
        'title' => 'Custom jerseys, uniforms, and team gear',
        'show_description' => true,
        'description' => 'Shop custom sportswear or request a bulk quote for teams, schools, clubs, businesses, and events.',
        'show_primary_button' => true,
        'primary_label' => 'Shop Products',
        'primary_url' => route('products.index'),
        'primary_target' => '_self',
        'show_secondary_button' => true,
        'secondary_label' => 'Request Quote',
        'secondary_url' => route('quote.request'),
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
                    $showEyebrow = $isEnabled('show_eyebrow');
                    $showTitle = $isEnabled('show_title');
                    $showDescription = $isEnabled('show_description');
                    $showPrimaryButton = $isEnabled('show_primary_button');
                    $showSecondaryButton = $isEnabled('show_secondary_button');
                    $eyebrow = trim((string) ($slide['eyebrow'] ?? ''));
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
                        ($showEyebrow && filled($eyebrow))
                        || ($showTitle && filled($title))
                        || ($showDescription && filled($description))
                        || ($showPrimaryButton && filled($primaryLabel))
                        || ($showSecondaryButton && filled($secondaryLabel))
                    );
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
                            @if($showEyebrow && filled($eyebrow))
                                <span class="promo-eyebrow">{{ $eyebrow }}</span>
                            @endif

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
                                            class="btn btn-red"
                                        >{{ $primaryLabel }}</a>
                                    @endif

                                    @if($showSecondaryButton && filled($secondaryLabel))
                                        <a
                                            href="{{ $secondaryUrl }}"
                                            target="{{ $secondaryTarget }}"
                                            @if($secondaryTarget === '_blank') rel="noopener noreferrer" @endif
                                            class="btn btn-white"
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
            <button class="promo-arrow promo-prev" type="button" aria-label="Previous slide">‹</button>
            <button class="promo-arrow promo-next" type="button" aria-label="Next slide">›</button>
            <div class="promo-dots" aria-label="Slider controls">
                @foreach($renderSlides as $index => $slide)
                    <button class="promo-dot {{ $index === 0 ? 'active' : '' }}" type="button" aria-label="Go to slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
