<x-layouts.storefront :seo="$seo">
    <div class="np-about-page">
        <section class="np-about-hero" aria-labelledby="about-nextplay-title">
            <div class="np-about-container">
                <p class="np-about-eyebrow np-about-eyebrow--inverse">{{ data_get($about, 'hero.eyebrow') }}</p>
                <h1 id="about-nextplay-title">{{ data_get($about, 'hero.title') }}</h1>
                <p class="np-about-hero__description">{{ data_get($about, 'hero.description') }}</p>
            </div>
        </section>

        <section class="np-about-intro" aria-labelledby="about-intro-title">
            <div class="np-about-container np-about-intro__grid">
                <div class="np-about-intro__copy">
                    <h2 id="about-intro-title">{{ data_get($about, 'introduction.title') }}</h2>
                    <p>{{ data_get($about, 'introduction.description') }}</p>
                </div>
                <figure class="np-about-intro__media">
                    <img
                        src="{{ data_get($about, 'introduction.image_url') }}"
                        width="520"
                        height="258"
                        alt="{{ data_get($about, 'introduction.image_alt') }}"
                        decoding="async"
                        fetchpriority="high"
                    >
                </figure>
            </div>
        </section>

        <section class="np-about-section np-about-section--soft" aria-labelledby="about-services-title">
            <div class="np-about-container">
                <h2 id="about-services-title" class="np-about-section-title">{{ data_get($about, 'what_we_do.title') }}</h2>
                <div class="np-about-services-grid">
                    @foreach(data_get($about, 'what_we_do.cards', []) as $card)
                        <x-storefront.about.service-card
                            :icon="data_get($card, 'fallback_icon')"
                            :icon-url="data_get($card, 'icon_url')"
                            :icon-alt="data_get($card, 'icon_alt', '')"
                            :title="data_get($card, 'title')"
                            :description="data_get($card, 'description')"
                        />
                    @endforeach
                </div>

                <h2 id="about-process-title" class="np-about-section-title np-about-section-title--process">{{ data_get($about, 'how_we_work.title') }}</h2>
                <div class="np-about-process-grid" aria-labelledby="about-process-title">
                    @foreach(data_get($about, 'how_we_work.steps', []) as $index => $step)
                        <x-storefront.about.process-step
                            :number="data_get($step, 'number')"
                            :icon="data_get($step, 'fallback_icon')"
                            :icon-url="data_get($step, 'icon_url')"
                            :icon-alt="data_get($step, 'icon_alt', '')"
                            :title="data_get($step, 'title')"
                            :description="data_get($step, 'description')"
                            :last="$index === 3"
                        />
                    @endforeach
                </div>
            </div>
        </section>

        <section class="np-about-gallery" aria-label="NextPlay sportswear in action">
            <div class="np-about-gallery__grid">
                @foreach(data_get($about, 'gallery.items', []) as $item)
                    <figure class="np-about-gallery__item np-about-gallery__item--{{ data_get($item, 'id') }}">
                        <img
                            src="{{ data_get($item, 'image_url') }}"
                            width="{{ data_get($item, 'id') === 'team' ? 310 : (data_get($item, 'id') === 'fabric' ? 174 : (data_get($item, 'id') === 'number' ? 208 : 239)) }}"
                            height="174"
                            loading="lazy"
                            decoding="async"
                            alt="{{ data_get($item, 'image_alt') }}"
                        >
                    </figure>
                @endforeach
            </div>
        </section>

        <section class="np-about-cta" aria-labelledby="about-cta-title">
            <div class="np-about-container np-about-cta__inner">
                <div class="np-about-cta__copy">
                    <p class="np-about-eyebrow">{{ data_get($about, 'cta.eyebrow') }}</p>
                    <h2 id="about-cta-title">{{ data_get($about, 'cta.title') }}</h2>
                </div>
                <div class="np-about-cta__actions">
                    <a href="{{ data_get($about, 'cta.primary_url') }}" class="btn btn-secondary btn-lg np-about-cta__button">
                        <span>{{ data_get($about, 'cta.primary_label') }}</span>
                        <x-storefront.about.icon name="chevron-right" :size="18" />
                    </a>
                    <a href="{{ data_get($about, 'cta.secondary_url') }}" class="btn btn-outline btn-lg np-about-cta__button np-about-cta__button--outline">
                        <span>{{ data_get($about, 'cta.secondary_label') }}</span>
                        <x-storefront.about.icon name="chevron-right" :size="18" />
                    </a>
                </div>
            </div>
        </section>

        <section class="np-about-help" aria-labelledby="about-help-title">
            <div class="np-about-container np-about-help__inner">
                @if(filled(data_get($about, 'help.icon_url')))
                    <span class="np-about-icon np-about-help__icon">
                        <img src="{{ data_get($about, 'help.icon_url') }}" alt="{{ data_get($about, 'help.icon_alt') }}" loading="lazy" decoding="async" style="display:block;width:48px;height:48px;object-fit:contain;">
                    </span>
                @else
                    <x-storefront.about.icon :name="data_get($about, 'help.fallback_icon')" :size="48" class="np-about-help__icon" />
                @endif
                <div class="np-about-help__copy">
                    <h2 id="about-help-title">{{ data_get($about, 'help.title') }}</h2>
                    <p>{{ data_get($about, 'help.description') }}</p>
                </div>
                <a href="{{ data_get($about, 'help.button_url') }}" class="btn btn-outline btn-sm np-about-help__button">
                    <span>{{ data_get($about, 'help.button_label') }}</span>
                    <x-storefront.about.icon name="chevron-right" :size="16" />
                </a>
            </div>
        </section>
    </div>
</x-layouts.storefront>
