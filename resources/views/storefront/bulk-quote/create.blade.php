<x-layouts.storefront
    :seo="['title' => 'Request a Bulk Quote | ' . config('storefront.name'), 'description' => 'Request a bulk quote for customised team wear and promotional products.', 'schema_type' => 'ContactPage']"
    :structured-data="$structuredData ?? []"
>
    <main class="bulk-quote-page npq-page">
        <div class="site-container">
            <nav class="npq-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Bulk Quote</span>
            </nav>
            <header class="npq-intro">
                <h1>Request a Bulk Quote</h1>
                <p>Add one or more products to your request.</p>
            </header>
            <form id="bulk-quote-request-form" class="nps-form npq-form" method="POST"
                  action="{{ route('quote.request.store') }}" enctype="multipart/form-data" novalidate
                  data-search-url="{{ route('quote.products.search') }}"
                  data-details-url="{{ route('quote.products.show', ['product' => '__ID__']) }}"
                  data-server-errors="{{ $errors->any() ? '1' : '0' }}"
                  data-product-errors="{{ $errors->has('quote_products') || $errors->has('quote_products.*') || $errors->has('quote_artworks.*') ? '1' : '0' }}">
                @csrf
                <div class="npq-honeypot" aria-hidden="true"><label for="bulk-company">Company</label><input id="bulk-company" type="text" name="company" tabindex="-1" autocomplete="off" value=""></div>
                <input type="hidden" id="npq-products-data" name="quote_products_json" value="{{ old('quote_products_json', '[]') }}">
                @if($errors->any())
                    <div class="npq-validation-alert" role="alert" id="npq-server-errors">
                        Please review the highlighted information and try again.
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                <div class="npq-layout" id="npq-product-stage">
                    <section class="npq-panel npq-editor" aria-labelledby="npq-editor-heading">
                        <div class="npq-editor-heading">
                            <h2 id="npq-editor-heading">Add a product</h2>
                            <div class="npq-draft-status" id="npq-draft-status"><span class="npq-status-pill"><span class="npq-dot"></span> Not added yet</span></div>
                        </div>
                        <div class="npq-tabs" role="tablist" aria-label="Product type">
                            <button class="npq-tab is-active" type="button" id="npq-existing-tab" role="tab" aria-selected="true" aria-controls="npq-existing-fields" data-mode="existing">Existing product</button>
                            <button class="npq-tab" type="button" id="npq-new-tab" role="tab" aria-selected="false" aria-controls="npq-new-fields" data-mode="new">Product not listed</button>
                        </div>
                        <p class="npq-mode-help" id="npq-mode-help" hidden>Choose a product from our catalogue and configure your request.</p>
                        <div class="npq-existing-fields" id="npq-existing-fields" role="tabpanel" aria-labelledby="npq-existing-tab">
                            <div class="npq-field npq-search-field">
                                <label for="npq-catalog-search">Search for a product by name or SKU</label>
                                <div class="npq-search-wrap"><span aria-hidden="true"><x-storefront.bulk-quote.icon name="search" :size="17" /></span>
                                    <input id="npq-catalog-search" type="search" autocomplete="off" placeholder="Search for a product by name or SKU" role="combobox" aria-expanded="false" aria-controls="npq-search-results">
                                </div>
                                <div id="npq-search-results" role="listbox" class="npq-search-results" hidden></div>
                                <span class="npq-error" data-error="catalog" hidden></span>
                            </div>
                            <div id="npq-catalog-selection" class="npq-catalog-selection" hidden></div>
                            <div id="npq-existing-config" hidden>
                                <h3 class="npq-config-heading">Configure this product</h3>
                                <div id="npq-catalog-options" class="npq-catalog-options"></div>
                                <div class="npq-existing-sizes">
                                    <h4 id="npq-existing-size-heading">Quantity <span class="npq-required">*</span></h4>
                                    <div id="npq-catalog-sizes"></div>
                                    <span class="npq-error" data-error="existing-sizes" hidden></span>
                                </div>
                                <div class="npq-existing-extras npq-field-row">
                                    <div class="npq-field"><label for="npq-existing-customization">Customisation notes <span class="npq-optional">(optional)</span></label><div class="npq-textarea"><textarea id="npq-existing-customization" maxlength="500" rows="3" placeholder="Add placement, logo or design details..."></textarea><span class="npq-count"><span id="npq-existing-customization-count">0</span>/500</span></div></div>
                                    <div class="npq-field npq-artwork-field"><label for="npq-existing-artwork">Product artwork <span class="npq-optional">(optional)</span></label>
                                        <label class="npq-dropzone" id="npq-existing-dropzone" for="npq-existing-artwork">
                                            <input id="npq-existing-artwork" type="file" accept=".jpg,.jpeg,.png,.pdf">
                                            <span class="npq-upload-icon"><x-storefront.bulk-quote.icon name="upload" :size="20" /></span><span><strong>Upload artwork</strong><small>PDF, JPG or PNG (max 10MB)</small></span>
                                        </label>
                                        <div class="npq-file-preview" id="npq-existing-file-preview" hidden><img id="npq-existing-image-preview" alt="Selected artwork preview" hidden><span id="npq-existing-file-name"></span><small id="npq-existing-file-size"></small><button type="button" id="npq-existing-file-remove" aria-label="Remove selected artwork">×</button></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="npq-new-fields" role="tabpanel" aria-labelledby="npq-new-tab" hidden>
                            <div class="npq-field"><label for="npq-name">Product name / type <span class="npq-required">*</span></label><input id="npq-name" maxlength="190" placeholder="e.g. Custom Training Backpack" autocomplete="off"><span class="npq-error" data-error="name" hidden></span></div>
                            <div class="npq-field"><label for="npq-description">Product description <span class="npq-required">*</span></label><div class="npq-textarea"><textarea id="npq-description" rows="3" maxlength="500" placeholder="Describe the product and its features..."></textarea><span class="npq-count"><span id="npq-description-count">0</span>/500</span></div><span class="npq-error" data-error="description" hidden></span></div>
                            <div class="npq-field-row npq-two">
                                <div class="npq-field"><label for="npq-material">Material / fabric <span class="npq-required">*</span></label><input id="npq-material" maxlength="190" placeholder="e.g. Water-resistant polyester" required><span class="npq-error" data-error="material" hidden></span></div>
                                <div class="npq-field"><label for="npq-colors">Colours <span class="npq-required">*</span></label><input id="npq-colors" maxlength="190" placeholder="e.g. Navy and orange" required><span class="npq-error" data-error="colors" hidden></span></div>
                                <div class="npq-field"><label for="npq-sizes">Size / dimensions <span class="npq-required">*</span></label><input id="npq-sizes" maxlength="190" placeholder="e.g. 45 × 30 × 18 cm" required><span class="npq-error" data-error="sizes" hidden></span></div>
                                <div class="npq-field"><label for="npq-quantity">Quantity <span class="npq-required">*</span></label><input id="npq-quantity" type="number" min="1" max="99999" step="1" inputmode="numeric" placeholder="e.g. 80" required><span class="npq-error" data-error="quantity" hidden></span></div>
                                <div class="npq-field"><label for="npq-budget">Budget <span class="npq-optional">(optional)</span></label><input id="npq-budget" maxlength="190" placeholder="e.g. USD 10–15 per piece"></div>
                            </div>
                            <fieldset class="npq-customizations"><legend>Customisation options <span class="npq-optional">(optional)</span></legend><p>Select all that apply</p><div class="npq-checkbox-row">
                                @foreach(['logo' => 'Logo', 'names-numbers' => 'Names & numbers', 'embroidery' => 'Embroidery', 'sublimation' => 'Sublimation', 'full-custom-design' => 'Full custom design'] as $key => $name)
                                    <label class="npq-choice"><input type="checkbox" name="npq_choice[]" value="{{ $key }}"><span class="npq-choice-box" aria-hidden="true"></span>{{ $name }}</label>
                                @endforeach
                            </div></fieldset>
                            <div class="npq-new-extras npq-field-row"><div class="npq-field"><label for="npq-customization">Customisation notes <span class="npq-optional">(optional)</span></label><div class="npq-textarea"><textarea id="npq-customization" maxlength="500" rows="3" placeholder="Add placement, logo or design details..."></textarea><span class="npq-count"><span id="npq-customization-count">0</span>/500</span></div></div>
                            <div class="npq-field npq-artwork-field"><label for="npq-artwork">Product artwork <span class="npq-optional">(optional)</span></label>
                                <label class="npq-dropzone" id="npq-dropzone" for="npq-artwork"><input id="npq-artwork" type="file" accept=".jpg,.jpeg,.png,.pdf"><span class="npq-upload-icon"><x-storefront.bulk-quote.icon name="upload" :size="20" /></span><span><strong>Upload artwork</strong><small>PDF, JPG or PNG (max 10MB)</small></span></label>
                                <div class="npq-file-preview" id="npq-file-preview" hidden><img id="npq-image-preview" alt="Selected artwork preview" hidden><span id="npq-file-name"></span><small id="npq-file-size"></small><button type="button" id="npq-file-remove" aria-label="Remove selected artwork">×</button></div>
                            </div></div>
                        </div>
                        <span class="npq-error" data-error="artwork" hidden></span>
                        <div class="npq-editor-actions"><button type="button" class="npq-cancel" id="npq-cancel">Cancel</button><button type="button" class="npq-primary npq-quote-cta" id="npq-add">Add Product to Request <span aria-hidden="true">＋</span></button></div>
                        <div class="npq-shared-details" id="npq-contact-stage">
                            @include('storefront.bulk-quote.partials.contact-delivery')
                        </div>
                    </section>
                    <aside class="npq-panel npq-added" aria-labelledby="npq-added-heading">
                        <div class="npq-added-heading"><h2 id="npq-added-heading">Added products (<span id="npq-added-count">0</span>)</h2><button type="button" id="npq-another" class="npq-add-another">＋ Add another product</button></div>
                        <div id="npq-added-list" class="npq-added-list" aria-live="polite"><p class="npq-empty">No products added yet. Complete the form on the left to add your first product.</p></div>
                        <div class="npq-summary"><h3>Request summary</h3><div class="npq-summary-totals"><strong><span id="npq-summary-count">0</span> products</strong><strong><span id="npq-summary-quantity">0</span> pieces</strong></div><p><span aria-hidden="true">ⓘ</span> The product currently being edited is not added yet and is not included in this total.</p></div>
                        <p class="npq-error npq-request-error" id="npq-products-error" hidden></p>
                    </aside>
                </div>
                <div id="npq-upload-inputs" hidden></div>
            </form>
        </div>
        @php
            // Build the state before encoding; Blade directives cannot reliably parse nested expressions.
            $quoteState = [
                'products' => json_decode(old('quote_products_json', '[]'), true) ?: [],
                'selected' => $selectedProduct,
            ];
        @endphp
        <script id="npq-state" type="application/json">@json($quoteState)</script>
        <script src="{{ asset('js/nextplay-quote-builder.js') }}?v=20261031v7" defer></script>
        <script src="{{ asset('js/bulk-quote-selects.js') }}?v=20261031v3" defer></script>
        <script src="{{ asset('js/bulk-quote-form-validation.js') }}?v=20261030c" defer></script>
    </main>
</x-layouts.storefront>
