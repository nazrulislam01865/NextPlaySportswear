@props(['product', 'materialGroup' => null, 'sizeRangeLabel' => 'Configured sizes'])

<aside class="np-custom-order-card" aria-label="Your Custom Order">
    <div class="np-custom-order-card__head"><h3>Your Custom Order</h3></div>

    <div class="np-custom-order-product">
        <img src="{{ $product['image'] }}" alt="">
        <div>
            <strong>{{ $product['title'] }}</strong>
            @if(filled($product['sku'] ?? null))<small>SKU: {{ $product['sku'] }}</small>@endif
        </div>
    </div>

    @if($materialGroup)
        <div class="np-custom-order-section">
            <div class="np-custom-order-section__title">
                <h4>Selected Fabric</h4>
                <button type="button" @click="openCustomizerStep(1)">Change</button>
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
                    <strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured fabric'"></strong>
                </div>
            </div>
        </div>
    @endif

    <div class="np-custom-order-section" x-show="activeCustomizerStep >= 2" x-cloak>
        <div class="np-custom-order-section__title">
            <h4>Selected Sizes &amp; Quantities</h4>
            <button type="button" @click="openCustomizerStep(2)">Change</button>
        </div>
        <div class="np-custom-order-size-list">
            @if(!empty($product['size_groups']))
                @foreach($product['size_groups'] as $group)
                    @foreach($group['sizes'] as $size)
                        @php $key = $group['id'].':'.$size['code']; @endphp
                        <div x-show="Number(quantities[@js($key)] || 0) > 0">
                            <strong>{{ $size['label'] }}</strong>
                            <span>× <b x-text="quantities[@js($key)]"></b></span>
                        </div>
                    @endforeach
                @endforeach
            @endif
            <div class="is-total"><span>Total Pieces</span><strong x-text="totalQuantity()"></strong></div>
        </div>
    </div>

    <div class="np-custom-order-section np-custom-order-facts">
        <div x-show="activeCustomizerStep === 1" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg>
            <span>Sizes</span><strong>{{ $sizeRangeLabel }}</strong>
        </div>
        <div x-show="activeCustomizerStep === 2" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg>
            <span>Sizes Available</span><strong>{{ $sizeRangeLabel }}</strong>
        </div>
        <div x-show="activeCustomizerStep === 3" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg>
            <span>Total Pieces</span><strong x-text="totalQuantity()"></strong>
        </div>
        <div x-show="activeCustomizerStep === 3" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <span>Players to Add</span><strong x-text="rosterEnabled ? rosterRows.length : totalQuantity()"></strong>
        </div>
        <div x-show="activeCustomizerStep <= 3" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/></svg>
            <span>Minimum Order Quantity</span><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} Piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong>
        </div>
        <div x-show="activeCustomizerStep <= 4" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span>Production Lead Time</span><strong x-text="productionDaysOnlyLabel()"></strong>
        </div>
        <div x-show="activeCustomizerStep <= 4" x-cloak>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/></svg>
            <span>Shipping</span><strong x-text="shippingLabel()"></strong>
        </div>
    </div>

    <div class="np-custom-order-section np-custom-order-compact-row" x-show="rosterEnabled && ((activeCustomizerStep === 4 && artworkMode === 'help') || activeCustomizerStep === 6)" x-cloak>
        <div class="np-custom-order-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg></div>
        <div>
            <h4 x-text="activeCustomizerStep === 6 ? 'Players' : 'Player Names & Numbers'"></h4>
            <strong x-text="`${rosterRows.length} players`"></strong>
        </div>
        <button type="button" @click="openCustomizerStep(3)">{{ $attributes->get('playerActionLabel', 'Change') }}</button>
    </div>

    <div class="np-custom-order-section np-custom-order-compact-row" x-show="activeCustomizerStep >= 4" x-cloak>
        <div class="np-custom-order-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg></div>
        <div><h4>Artwork</h4><strong x-text="artworkFiles.length ? `${artworkFiles.length} files uploaded` : 'Not selected yet'"></strong></div>
        <button type="button" @click="openCustomizerStep(4)">Change</button>
    </div>

    <div class="np-custom-order-section np-custom-order-compact-row" x-show="activeCustomizerStep >= 5" x-cloak>
        <div class="np-custom-order-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></div>
        <div>
            <h4>Production &amp; Shipping</h4>
            <span class="np-custom-order-method-lines">
                <span x-show="currentProductionOptions().length > 0"><b x-text="speedLabel()"></b><small x-text="productionDaysOnlyLabel()"></small></span>
                <span><b x-text="shippingLabel()"></b><small x-text="shippingDaysOnlyLabel()"></small></span>
            </span>
        </div>
        <button type="button" @click="openCustomizerStep(5)">Change</button>
    </div>

    <div class="np-custom-order-section np-custom-order-compact-row np-custom-order-delivery" x-show="activeCustomizerStep === 5" x-cloak>
        <div class="np-custom-order-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></div>
        <div><h4>Estimated Delivery</h4><small>(After order confirmation)</small></div>
        <strong x-text="totalDeliveryDaysLabel()"></strong>
    </div>

    <div class="np-custom-order-section np-custom-order-estimate" x-show="activeCustomizerStep === 6" x-cloak>
        <div class="np-custom-order-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M12 11h2M8 15h2M12 15h2"/></svg></div>
        <div><h4>Estimated Total</h4><small>Final price will be calculated after review and can be adjusted if needed.</small></div>
        <strong x-text="money(totalPrice())"></strong>
    </div>

    <div class="np-custom-order-note" x-show="activeCustomizerStep === 1" x-cloak>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p>The final price will be calculated based on your selected sizes, quantities and artwork details.</p>
    </div>
</aside>
