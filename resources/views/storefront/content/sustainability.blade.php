<x-layouts.storefront :seo="$seo">
    <main class="sustainability-page">
        <section class="sustainability-page__hero" aria-labelledby="sustainability-title">
            <div class="sustainability-page__container">
                <p class="sustainability-page__eyebrow">{{ data_get($sustainability, 'hero.eyebrow') }}</p>
                <h1 id="sustainability-title">{{ data_get($sustainability, 'hero.title') }}</h1>
                <p class="sustainability-page__hero-subtitle">{{ data_get($sustainability, 'hero.subtitle') }}</p>
            </div>
        </section>

        <section class="sustainability-page__body">
            <div class="sustainability-page__container">
                <header class="sustainability-page__approach">
                    <h2>{{ data_get($sustainability, 'approach.title') }}</h2>
                    <p>{{ data_get($sustainability, 'approach.description') }}</p>
                </header>

                <div class="sustainability-page__features">
                    @foreach(data_get($sustainability, 'features', []) as $feature)
                        <x-storefront.sustainability.feature-row :feature="$feature" :reverse="data_get($feature, 'id') === 'people_partners'" />
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sustainability-page__informed" aria-labelledby="sustainability-informed-title">
            <div class="sustainability-page__container">
                <header class="sustainability-page__informed-header">
                    <h2 id="sustainability-informed-title">{{ data_get($sustainability, 'informed.title') }}</h2>
                    <p>{{ data_get($sustainability, 'informed.subtitle') }}</p>
                </header>
                <div class="sustainability-page__informed-grid">
                    @foreach(data_get($sustainability, 'informed.cards', []) as $card)
                        <x-storefront.sustainability.info-card :card="$card" />
                    @endforeach
                </div>
            </div>
        </section>

        <section class="sustainability-page__cta" aria-labelledby="sustainability-cta-title">
            <div class="sustainability-page__container sustainability-page__cta-inner">
                <div class="sustainability-page__cta-copy">
                    <h2 id="sustainability-cta-title">{{ data_get($sustainability, 'cta.title') }}</h2>
                    <p>{{ data_get($sustainability, 'cta.description') }}</p>
                </div>
                <div class="sustainability-page__cta-actions">
                    <a href="{{ data_get($sustainability, 'cta.primary_url') }}" class="btn btn-secondary btn-lg sustainability-page__cta-button">
                        <span>{{ data_get($sustainability, 'cta.primary_label') }}</span><span aria-hidden="true">›</span>
                    </a>
                    <a href="{{ data_get($sustainability, 'cta.secondary_url') }}" class="btn btn-outline btn-lg sustainability-page__cta-button">
                        <span>{{ data_get($sustainability, 'cta.secondary_label') }}</span><span aria-hidden="true">›</span>
                    </a>
                </div>
            </div>
        </section>
    </main>
</x-layouts.storefront>
