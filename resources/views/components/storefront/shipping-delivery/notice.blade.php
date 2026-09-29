@props(['notice'])
<div class="np-shipping-delivery-notice">
    <x-storefront.shipping-delivery.icon :icon-url="data_get($notice, 'icon_url')" :fallback="data_get($notice, 'fallback_icon')" :alt="data_get($notice, 'icon_alt')" :size="31" />
    <p>{{ data_get($notice, 'text') }}</p>
</div>
