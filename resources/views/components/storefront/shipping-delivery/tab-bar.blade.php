@props(['tabs' => []])
<nav class="np-shipping-delivery-tabs" aria-label="Order information sections">
    @foreach($tabs as $tab)
        @if(data_get($tab, 'id') === 'delivery')
            <span class="np-shipping-delivery-tab is-active" aria-current="page">{{ data_get($tab, 'label') }}</span>
        @else
            <button type="button" class="np-shipping-delivery-tab" aria-disabled="true" disabled>{{ data_get($tab, 'label') }}</button>
        @endif
    @endforeach
</nav>
