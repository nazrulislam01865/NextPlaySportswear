@props(['selectedProduct' => null])

    <div
        class="bulk-quote-product-picker"
        id="bulk-quote-product-picker"
        data-prototype-product-search
        data-search-url="{{ route('quote.products.search') }}"
        data-detail-url="{{ route('quote.products.show', ['product' => '__ID__']) }}"
    >
        <div class="bulk-quote-product-search-row">
            <label for="bulk-quote-product-search" class="bulk-quote-product-label">Search for a product</label>
            <span class="bulk-quote-product-hint">Optional</span>
        </div>
        <div class="bulk-quote-product-search-area">
            <span class="bulk-quote-product-search-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="search" :size="18" /></span>
            <input
                id="bulk-quote-product-search"
                type="search"
                placeholder="Search by product name or SKU"
                autocomplete="off"
                role="combobox"
                aria-expanded="false"
                aria-controls="bulk-quote-product-results"
                aria-autocomplete="list"
            >
            <div id="bulk-quote-product-results" class="bulk-quote-product-results" role="listbox" hidden></div>
        </div>
        <p class="bulk-quote-product-feedback" id="bulk-quote-product-feedback" role="status" aria-live="polite" hidden></p>
        <x-storefront.bulk-quote.field-error name="product_information" />
        <x-storefront.bulk-quote.field-error name="product_information.product_id" />
        <x-storefront.bulk-quote.field-error name="product_information.options" />
        <x-storefront.bulk-quote.field-error name="product_information.sizes" />

        <input id="bulk-quote-selected-product-id" type="hidden" name="product_information[product_id]" disabled>

        <div class="bulk-quote-selected-product" id="bulk-quote-selected-product" hidden>
            <img class="bulk-quote-selected-image" id="bulk-quote-selected-image" src="{{ asset('images/product-placeholder.svg') }}" alt="" loading="lazy" decoding="async">
            <div class="bulk-quote-selected-details">
                <strong id="bulk-quote-selected-name"></strong>
                <span id="bulk-quote-selected-sku"></span>
                <p id="bulk-quote-selected-summary"></p>
            </div>
            <div class="bulk-quote-selected-actions">
                <button class="bulk-quote-product-change" id="bulk-quote-product-change" type="button">
                    <x-storefront.bulk-quote.icon name="change" :size="17" /> Change
                </button>
                <button class="bulk-quote-product-clear" id="bulk-quote-product-clear" type="button">
                    <x-storefront.bulk-quote.icon name="trash" :size="16" /> Clear
                </button>
            </div>
        </div>
        <div class="bulk-quote-product-fields" id="bulk-quote-product-fields" hidden>
            <div id="bulk-quote-product-options" class="bulk-quote-product-options"></div>
            <div class="bulk-quote-product-bottom" id="bulk-quote-product-bottom">
                <div id="bulk-quote-product-sizes" class="bulk-quote-product-sizes"></div>
                <div class="bulk-quote-field bulk-quote-product-notes">
                    <label for="bulk-quote-product-notes">Additional Product Notes <span class="bulk-quote-inline-optional">(Optional)</span></label>
                    <div class="bulk-quote-control-wrap">
                        <textarea id="bulk-quote-product-notes" name="product_information[notes]" maxlength="500" placeholder="e.g., logo placement, special design requests, etc." disabled></textarea>
                        <div class="bulk-quote-counter" aria-hidden="true"><span id="bulk-quote-product-notes-count">0</span>/500</div>
                    </div>
                    <x-storefront.bulk-quote.field-error name="product_information.notes" />
                </div>
            </div>
        </div>
    </div>
    <script type="application/json" id="bulk-quote-product-initial">@json(['product' => $selectedProduct, 'selection' => old('product_information', [])])</script>
    <script src="{{ asset('js/bulk-quote-product.js') }}?v=20261028b" defer></script>
