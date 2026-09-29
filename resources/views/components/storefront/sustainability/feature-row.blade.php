@props(['feature' => [], 'reverse' => false])
<article class="sustainability-page__feature-row {{ $reverse ? 'sustainability-page__feature-row--reverse' : '' }}">
    <figure class="sustainability-page__feature-media">
        <img src="{{ data_get($feature, 'image_url') }}" alt="{{ data_get($feature, 'image_alt') }}" loading="lazy" decoding="async">
    </figure>
    <div class="sustainability-page__feature-copy">
        <h2>{{ data_get($feature, 'heading') }}</h2>
        <p>{{ data_get($feature, 'description') }}</p>
    </div>
</article>
