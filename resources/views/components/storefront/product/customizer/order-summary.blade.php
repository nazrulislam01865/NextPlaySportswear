@props(['product', 'materialGroup' => null, 'sizeRangeLabel' => 'Configured sizes', 'isEditing' => false])

<aside class="np-custom-order-card" :class="mobileOrderSummaryOpen ? 'is-mobile-open' : ''" aria-label="Your Custom Order">
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

    <section class="np-custom-order-section np-custom-order-compact-row">
        <div class="np-custom-order-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/><path d="M10 11h4M12 9v4"/></svg>
        </div>
        <div>
            <h4>Player Names &amp; Numbers</h4>
            @if($attributes->has('playerActionLabel'))
                <strong x-text="rosterEnabled ? `${rosterRows.length} players` : 'Not added yet'"></strong>
            @else
                <strong x-text="rosterEnabled ? `${rosterRows.length} players` : 'Not added yet'"></strong>
            @endif
            <small x-text="rosterEnabled ? 'Names & numbers added' : 'Complete player details if required'"></small>
        </div>
        <button type="button" @click="openCustomizerStep(3)">Change <span aria-hidden="true">›</span></button>
    </section>

    <section class="np-custom-order-section np-custom-order-compact-row">
        <div class="np-custom-order-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
        </div>
        <div>
            <h4>Artwork</h4>
            <strong x-text="artworkFiles.length ? `${artworkFiles.length} file${artworkFiles.length === 1 ? '' : 's'} uploaded` : 'Not selected yet'"></strong>
            <small x-text="artworkFiles.length ? 'Design ready for production review' : 'Add artwork in Step 4'"></small>
        </div>
        <button type="button" @click="openCustomizerStep(4)">Change <span aria-hidden="true">›</span></button>
    </section>

    <section class="np-custom-order-section np-custom-order-compact-row">
        <div class="np-custom-order-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
        </div>
        <div>
            <h4>Production &amp; Shipping</h4>
            <span class="np-custom-order-method-lines">
                <span x-show="currentProductionOptions().length > 0" x-cloak><b x-text="speedLabel()"></b><small x-text="productionDaysOnlyLabel()"></small></span>
                <span><b x-text="shippingLabel()"></b><small x-text="shippingDaysOnlyLabel()"></small></span>
            </span>
        </div>
        <button type="button" @click="openCustomizerStep(5)">Change <span aria-hidden="true">›</span></button>
    </section>

    <section class="np-custom-order-pricing" aria-label="Estimated pricing">
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
        <div><span>Remote Area Surcharge</span><b x-text="money(remoteAreaSurchargeAmount())"></b></div>
        <div class="is-total"><span>Total <small>(Estimated)</small></span><b x-text="money(estimatedOrderTotal())"></b></div>
    </section>

    <div class="np-custom-order-actions">
        <button
            type="submit"
            form="np-product-config-form"
            class="btn btn-secondary btn-xl np-custom-order-add-to-cart"
            :disabled="!canAddToCart()"
            :aria-disabled="!canAddToCart() ? 'true' : 'false'"
            :title="canAddToCart() ? 'Add configured product to cart' : 'Complete all required customization steps first'"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l3-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg>
            {{ $isEditing ? 'Update Cart Item' : 'Add to Cart' }}
        </button>
        <a class="btn btn-outline btn-xl np-custom-order-save-quote" href="{{ route('quote.request', ['product' => $product['slug']]) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
            Save as Quote
        </a>
        <p class="np-custom-order-disabled-note" x-show="!canAddToCart()" x-cloak>Complete all required steps to enable Add to Cart.</p>
    </div>
    </div>
</aside>
