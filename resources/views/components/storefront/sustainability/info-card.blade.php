@props(['card' => []])
<article class="sustainability-page__info-card">
    <x-storefront.sustainability.icon :icon-url="data_get($card, 'icon_url')" :fallback="data_get($card, 'fallback_icon')" :alt="data_get($card, 'icon_alt')" :size="58" />
    <div class="sustainability-page__info-card-copy">
        <h3>{{ data_get($card, 'title') }}</h3>
        <span class="sustainability-page__info-divider" aria-hidden="true"></span>
        <p>{{ data_get($card, 'description') }}</p>
    </div>
</article>
