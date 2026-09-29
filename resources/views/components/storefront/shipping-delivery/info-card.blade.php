@props(['card'])
<article class="np-shipping-delivery-info-card">
    <div class="np-shipping-delivery-info-card__icon">
        <x-storefront.shipping-delivery.icon :icon-url="data_get($card, 'icon_url')" :fallback="data_get($card, 'fallback_icon')" :alt="data_get($card, 'icon_alt')" :size="52" />
    </div>
    <div class="np-shipping-delivery-info-card__copy">
        <h3>{{ data_get($card, 'title') }}</h3>
        <p>{{ data_get($card, 'description') }}</p>
    </div>
</article>
