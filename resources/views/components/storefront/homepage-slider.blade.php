@props([
    'slides' => [],
])

@if(count($slides) > 0)
    <section class="promo-slider" aria-label="Featured sportswear promotions">
        <div class="promo-track" id="promoSlider">
            @foreach($slides as $index => $slide)
                <article class="promo-slide {{ $index === 0 ? 'active' : '' }}">
                    <img
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
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
                                    <h2>{{ $slide['title'] }}</h2>
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

            @if(count($slides) > 1)
                <button class="promo-arrow promo-prev" type="button" aria-label="Previous slide">‹</button>
                <button class="promo-arrow promo-next" type="button" aria-label="Next slide">›</button>
                <div class="promo-dots" aria-label="Slider controls">
                    @foreach($slides as $index => $slide)
                        <button class="promo-dot {{ $index === 0 ? 'active' : '' }}" type="button" aria-label="Go to slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
