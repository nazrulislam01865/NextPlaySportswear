@props(['step'])
<article class="np-shipping-delivery-timeline-step">
    <div class="np-shipping-delivery-timeline-step__number">{{ data_get($step, 'number') }}</div>
    <h3>{{ data_get($step, 'title') }}</h3>
    <p>{{ data_get($step, 'description') }}</p>
</article>
