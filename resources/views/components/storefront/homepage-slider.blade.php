@props([
    'slides' => [],
])

@php
    $fallbackSlide = [[
        'image' => asset('storage/storefront/home/hero.webp'),
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
                <img
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    @if($index === 0) fetchpriority="high" @endif
                    decoding="async"
                    src="{{ $slide['image'] }}"
                    alt="{{ $slide['alt'] }}"
                    style="object-position: {{ str_replace('-', ' ', $slide['image_focal_position']) }}"
                    width="1600"
                    height="700"
                >
                <div class="promo-overlay" style="background: {{ $slide['overlay_rgba'] }}"></div>

                @if($slide['show_content'])
                    <div class="promo-content">
                        <div class="promo-copy position-{{ $slide['content_position'] }} align-{{ $slide['text_alignment'] }} theme-{{ $slide['text_theme'] }}">
                            @if($slide['show_eyebrow'] && $slide['eyebrow'] !== '')
                                <span class="promo-eyebrow">{{ $slide['eyebrow'] }}</span>
                            @endif

                            @if($slide['show_title'] && $slide['title'] !== '')
                                <h1>{{ $slide['title'] }}</h1>
                            @endif

                            @if($slide['show_description'] && $slide['description'] !== '')
                                <p>{{ $slide['description'] }}</p>
                            @endif

                            @if(($slide['show_primary_button'] && $slide['primary_label'] !== '') || ($slide['show_secondary_button'] && $slide['secondary_label'] !== ''))
                                <div class="promo-actions">
                                    @if($slide['show_primary_button'] && $slide['primary_label'] !== '')
                                        <a
                                            href="{{ $slide['primary_url'] }}"
                                            target="{{ $slide['primary_target'] }}"
                                            @if($slide['primary_target'] === '_blank') rel="noopener noreferrer" @endif
                                            class="btn btn-red"
                                        >{{ $slide['primary_label'] }}</a>
                                    @endif

                                    @if($slide['show_secondary_button'] && $slide['secondary_label'] !== '')
                                        <a
                                            href="{{ $slide['secondary_url'] }}"
                                            target="{{ $slide['secondary_target'] }}"
                                            @if($slide['secondary_target'] === '_blank') rel="noopener noreferrer" @endif
                                            class="btn btn-white"
                                        >{{ $slide['secondary_label'] }}</a>
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
