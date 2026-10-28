@props(['product', 'materialGroup' => null, 'sizeRangeLabel' => 'Configured sizes', 'isEditing' => false, 'rosterStep' => null, 'artworkStep' => 4, 'productionStep' => 5, 'ui' => []])

<aside class="np-custom-order-card" :class="mobileOrderSummaryOpen ? 'is-mobile-open' : ''" aria-label="Your Custom Order">
    <div class="np-custom-order-mobile-bar">
        <div class="np-custom-order-mobile-capsule">
        <button
            type="button"
            class="np-custom-order-mobile-capsule__summary"
            @click="toggleMobileOrderSummary()"
            :aria-expanded="mobileOrderSummaryOpen ? 'true' : 'false'"
            aria-controls="np-mobile-custom-order-panel"
            aria-label="Toggle Your Custom Order summary"
        >
            <span><small>Subtotal</small><strong x-text="money(productPriceAmount())"></strong></span>
            <span class="np-custom-order-mobile-capsule__chevron" :class="mobileOrderSummaryOpen ? 'is-open' : ''" aria-hidden="true">⌃</span>
        </button>
        </div>
    </div>

    <button type="button" class="np-custom-order-mobile-backdrop" x-show="mobileOrderSummaryOpen" x-cloak @click="closeMobileOrderSummary()" aria-label="Close custom order summary"></button>

    <div id="np-mobile-custom-order-panel" class="np-custom-order-panel">
    <div class="np-custom-order-card__head">
        <h3>Your Custom Order</h3>
    </div>

    <div class="np-custom-order-product">
        <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}">
        <div>
            <strong>{{ $product['title'] }}</strong>
            @if(filled($product['sku'] ?? null))
                <small>SKU: {{ $product['sku'] }}</small>
            @endif
        </div>
    </div>

    @if($materialGroup)
        <section class="np-custom-order-section">
            <div class="np-custom-order-section__title">
                <h4>Selected Fabric</h4>
                <button type="button" @click="openCustomizerStep(1)">Change <span aria-hidden="true">›</span></button>
            </div>
            <div class="np-custom-order-selection">
                <span class="np-custom-order-fabric-preview">
                    <img
                        x-show="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image"
                        :src="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image"
                        alt=""
                    >
                </span>
                <div>
                    <strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Not selected'"></strong>
                    <small x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.description || ''"></small>
                </div>
            </div>
        </section>
    @endif

    <section class="np-custom-order-section">
        <div class="np-custom-order-section__title">
            <h4>Selected Sizes &amp; Quantities</h4>
            <button type="button" @click="openCustomizerStep(2)">Change <span aria-hidden="true">›</span></button>
        </div>
        <div class="np-custom-order-sizes">
            <div class="np-custom-order-size-chips">
                @if(!empty($product['size_groups']))
                    @foreach($product['size_groups'] as $group)
                        @foreach($group['sizes'] as $size)
                            @php $key = $group['id'].':'.$size['code']; @endphp
                            <div class="np-custom-order-size-chip" x-show="Number(quantities[@js($key)] || 0) > 0" x-cloak>
                                <strong>{{ $size['label'] }}</strong>
                                <span><b x-text="quantities[@js($key)]"></b> pcs</span>
                            </div>
                        @endforeach
                    @endforeach
                @endif
            </div>
            <div class="np-custom-order-size-total">
                <span>Total Pieces</span>
                <strong x-text="totalQuantity()"></strong>
                <small>Minimum order: {{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</small>
            </div>
        </div>
    </section>

    @if($rosterStep)
    <section class="np-custom-order-section np-custom-order-compact-row">
        <div>
            <h4>{{ ($product['roster']['template_id'] ?? null) ? ($product['roster']['title'] ?? 'Personalization') : 'Player Names & Numbers' }}</h4>
            @if($attributes->has('playerActionLabel'))
                <strong x-text="rosterEnabled ? `${rosterRows.length} {{ ($product['roster']['template_id'] ?? null) ? 'entries' : 'players' }}` : 'Not added yet'"></strong>
            @else
                <strong x-text="rosterEnabled ? `${rosterRows.length} {{ ($product['roster']['template_id'] ?? null) ? 'entries' : 'players' }}` : 'Not added yet'"></strong>
            @endif
            <small x-text="rosterEnabled ? @js(($product['roster']['template_id'] ?? null) ? 'Personalization added' : 'Names & numbers added') : @js(($product['roster']['template_id'] ?? null) ? 'Complete personalization if required' : 'Complete player details if required')"></small>
        </div>
        <button type="button" @click="openCustomizerStep({{ $rosterStep }})">Change <span aria-hidden="true">›</span></button>
    </section>
    @endif

    <section class="np-custom-order-section np-custom-order-compact-row">
        <div>
            <h4>Artwork</h4>
            <strong x-text="artworkFiles.length ? `${artworkFiles.length} file${artworkFiles.length === 1 ? '' : 's'} uploaded` : 'Not selected yet'"></strong>
            <small x-text="artworkFiles.length ? 'Design ready for production review' : 'Add artwork in Step {{ $artworkStep }}'"></small>
        </div>
        <button type="button" @click="openCustomizerStep({{ $artworkStep }})">Change <span aria-hidden="true">›</span></button>
    </section>

    <section class="np-custom-order-section np-custom-order-compact-row">
        <div>
            <h4>Production &amp; Shipping</h4>
            <span class="np-custom-order-method-lines">
                <span x-show="currentProductionOptions().length > 0" x-cloak><b>Production time</b><small x-text="productionDaysOnlyLabel()"></small></span>
                <span><b x-text="shippingLabel()"></b><small x-text="shippingDaysOnlyLabel()"></small></span>
            </span>
        </div>
        <button type="button" @click="openCustomizerStep({{ $productionStep }})">Change <span aria-hidden="true">›</span></button>
    </section>

    <section class="np-custom-order-pricing" aria-label="Estimated pricing">
        <div><span>Unit Price</span><b x-text="money(unitPrice())"></b></div>
        <div>
            <span>
                <strong>Product Price (<span x-text="totalQuantity()"></span> pcs)</strong>
                <small>
                    @if($materialGroup)
                        Based on selected fabric and options
                    @endif
                </small>
            </span>
            <b x-text="money(productPriceAmount())"></b>
        </div>
        <div><span>Shipping (Estimated)</span><b x-text="money(shippingEstimatedAmount())"></b></div>
        <div x-show="productionExtraChargeAmount() > 0" x-cloak><span>Production Extra Charge</span><b x-text="money(productionExtraChargeAmount())"></b></div>
        <div x-show="shippingExtraChargeAmount() > 0" x-cloak><span>Shipping Extra Charge</span><b x-text="money(shippingExtraChargeAmount())"></b></div>
        <div class="is-total"><span>Total <small>(Estimated)</small></span><b x-text="money(estimatedOrderTotal())"></b></div>
    </section>

    <x-storefront.product.customizer.order-actions :product="$product" :is-editing="$isEditing" />
    </div>
</aside>
