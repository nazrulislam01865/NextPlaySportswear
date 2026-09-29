@props(['item'])
<article class="np-shipping-delivery-checklist-row">
    <div class="np-shipping-delivery-checklist-row__icon">
        <x-storefront.shipping-delivery.icon :icon-url="data_get($item, 'icon_url')" :fallback="data_get($item, 'fallback_icon')" :alt="data_get($item, 'icon_alt')" :size="34" />
    </div>
    <div class="np-shipping-delivery-checklist-row__copy">
        <h3>{{ data_get($item, 'title') }}</h3>
        <p>{{ data_get($item, 'description') }}</p>
    </div>
</article>
