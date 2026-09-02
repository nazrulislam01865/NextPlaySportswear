@props(['product'])

@php
    $sampleAvailable = filter_var(
        old('sample_available', $product->sample_available ?? false),
        FILTER_VALIDATE_BOOL
    );
    $sampleCharge = old('sample_charge', $product->sample_charge ?? 0);
    $currency = old('currency', $product->currency ?: 'USD');
@endphp

<article class="np-sample-order-card">
    <div class="np-sample-order-card__top">
        <span class="np-option-tiles__icon purple" aria-hidden="true">◈</span>

        <div class="np-sample-order-card__copy">
            <strong>Sample Order</strong>
            <small>Let buyers request a paid sample before placing their main order.</small>
        </div>

        <label class="np-sample-toggle" title="Enable or disable sample ordering for this product">
            <input type="hidden" name="sample_available" value="0">
            <input
                class="np-sample-toggle__input"
                type="checkbox"
                name="sample_available"
                value="1"
                @checked($sampleAvailable)
            >
            <span class="np-sample-toggle__track" aria-hidden="true">
                <span class="np-sample-toggle__thumb"></span>
            </span>
            <span class="np-sample-toggle__status">
                <span class="np-sample-toggle__off">Not available</span>
                <span class="np-sample-toggle__on">Available</span>
            </span>
        </label>
    </div>

    @error('sample_available')
        <p class="mt-3 text-xs font-bold text-red-600">{{ $message }}</p>
    @enderror

    <div class="np-sample-order-card__settings">
        <div class="np-sample-order-card__setting-copy">
            <strong>Sample pricing</strong>
            <small>The charge is added once when the buyer selects “I need a sample” on the storefront.</small>
        </div>

        <label class="admin-label np-sample-charge-field">
            Extra sample charge ({{ $currency }})
            <input
                class="admin-input"
                type="number"
                min="0"
                step="0.01"
                name="sample_charge"
                inputmode="decimal"
                value="{{ $sampleCharge }}"
                placeholder="0.00"
            >
            @error('sample_charge')
                <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>
            @enderror
        </label>

        <div class="np-sample-order-card__preview">
            <span>Storefront behavior</span>
            <strong>Sample available</strong>
            <small>Buyer sees “I need a sample”. When selected, this product's sample charge is added once to the calculated total.</small>
        </div>
    </div>
</article>
