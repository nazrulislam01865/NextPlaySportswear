@props(['product', 'editItem' => null, 'social' => []])

@php
    $isEditing = is_array($editItem) && filled($editItem['key'] ?? null);
    $editCustomization = $isEditing ? (array) ($editItem['customization'] ?? []) : [];
    $editConfiguration = $isEditing ? (array) ($editCustomization['configuration'] ?? []) : [];
    $existingArtwork = collect($editCustomization['artwork_files'] ?? [])
        ->filter(fn ($file) => is_array($file) && filled($file['path'] ?? null))
        ->values()
        ->map(function (array $file, int $index) use ($editItem): array {
            $size = max(0, (int) ($file['size'] ?? 0));
            $name = (string) ($file['original_name'] ?? 'Artwork file');
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $previewable = in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true);

            return [
                'key' => 'existing:'.sha1((string) $file['path']),
                'path' => (string) $file['path'],
                'name' => $name,
                'size' => $size,
                'sizeLabel' => $size >= 1024 * 1024
                    ? number_format($size / (1024 * 1024), 2).' MB'
                    : max(1, (int) round($size / 1024)).' KB',
                'existing' => true,
                'extension' => $extension,
                'previewable' => $previewable,
                'url' => route('cart.items.artwork.show', [
                    'cartItem' => (string) ($editItem['key'] ?? ''),
                    'artworkIndex' => $index,
                ]),
            ];
        })
        ->all();

    $builderConfig = [
        'title' => $product['title'],
        'currency' => $product['currency'] ?? 'USD',
        'base_price' => $product['base_price'],
        'minimum_quantity' => $product['minimum_quantity'] ?? 1,
        'maximum_quantity' => $product['maximum_quantity'] ?? 999,
        'gallery' => $product['gallery'],
        'product_profile' => $product['product_profile'] ?? 'standard',
        'option_groups' => $product['option_groups'] ?? [],
        'size_groups' => $product['size_groups'] ?? [],
        'artwork_upload' => $product['artwork_upload'] ?? ['enabled' => false],
        'production_speeds' => $product['production_speeds'] ?? [],
        'shipping_methods' => $product['shipping_methods'] ?? [],
        'roster' => \App\Support\ProductRoster::settings($product),
        'jersey_roster' => \App\Support\ProductRoster::settings($product),
        'sample' => $product['sample'] ?? ['available' => false, 'charge' => 0, 'charge_type' => 'fixed_order'],
        'price_tiers' => $product['price_tiers'] ?? [],
        'price_table' => $product['price_table'] ?? [],
        'fabric_price_tables' => $product['fabric_price_tables'] ?? [],
        'sale_campaigns' => $product['sale_campaigns'] ?? [],
        'social' => $social,
        'edit_mode' => $isEditing,
        'edit_item_key' => $isEditing ? (string) $editItem['key'] : null,
        'initial_state' => $isEditing ? [
            'selections' => (array) ($editConfiguration['selections'] ?? []),
            'multi_selections' => (array) ($editConfiguration['multi_selections'] ?? []),
            'inputs' => (array) ($editConfiguration['inputs'] ?? []),
            'quantities' => (array) ($editConfiguration['quantities'] ?? []),
            'order_quantity' => (int) ($editItem['quantity'] ?? ($product['minimum_quantity'] ?? 1)),
            'production_speed' => $editConfiguration['production_speed'] ?? null,
            'shipping_method' => $editConfiguration['shipping_method'] ?? null,
            'roster_enabled' => (bool) ($editConfiguration['roster_enabled'] ?? false),
            'roster' => array_values((array) ($editConfiguration['roster'] ?? [])),
            'sample_requested' => (bool) ($editConfiguration['sample_requested'] ?? false),
            'artwork_files' => $existingArtwork,
        ] : null,
    ];

    $allGroups = collect($product['option_groups'] ?? []);
    $fixedGroups = $allGroups->where('display_mode', 'fixed');
    $customerOptionGroups = $allGroups->where('display_mode', 'customer');
    $materialGroup = $customerOptionGroups
        ->filter(fn ($group) => in_array($group['type'] ?? '', ['image', 'swatch', 'buttons', 'select'], true))
        ->first(function ($group) {
            $label = strtolower((string) ($group['label'] ?? ''));
            $hasFabricPricing = collect($group['values'] ?? [])->contains(fn ($value) => ! empty(data_get($value, 'fabric_price_table.price_tiers')));
            return $hasFabricPricing || str_contains($label, 'fabric') || str_contains($label, 'material');
        });
    $nonMaterialOptionGroups = $customerOptionGroups
        ->reject(fn ($group) => $materialGroup && (string) ($group['id'] ?? '') === (string) ($materialGroup['id'] ?? ''))
        ->values();
    $reviewOptionGroups = $allGroups
        ->filter(fn ($group) => ($group['display_mode'] ?? 'customer') !== 'hidden')
        ->reject(fn ($group) => $materialGroup && (string) ($group['id'] ?? '') === (string) ($materialGroup['id'] ?? ''))
        ->values();
    $sizeGroupsWithCharts = collect($product['size_groups'] ?? [])->filter(fn ($group) => (bool) data_get($group, 'chart.enabled'));
    $firstChartGroup = $sizeGroupsWithCharts->first();
    $roster = \App\Support\ProductRoster::settings($product);
    $rosterEnabled = (bool) ($roster['enabled'] ?? false);
    $enabledRosterFields = collect($roster['fields'] ?? [])->filter(fn ($field) => (bool) ($field['enabled'] ?? true))->values();
    $artworkUpload = $product['artwork_upload'] ?? ['enabled' => false];
    $sample = $product['sample'] ?? ['available' => false, 'charge' => 0, 'charge_type' => 'fixed_order'];
    $productHighlights = collect($product['features'] ?? [])
        ->map(fn ($feature) => trim((string) $feature))
        ->filter()
        ->take(4)
        ->values();
    $sizeRangeItems = collect($product['size_groups'] ?? [])->map(function ($group) {
        $labels = collect($group['sizes'] ?? [])->pluck('label')->filter()->values();
        $range = $labels->isNotEmpty()
            ? ($labels->count() === 1 ? $labels->first() : $labels->first().' – '.$labels->last())
            : 'Configured sizes';
        $groupLabel = trim((string) ($group['label'] ?? ''));

        return $groupLabel !== '' ? $groupLabel.': '.$range : $range;
    })->filter()->values();
    $sizeRangeLabel = $sizeRangeItems->isNotEmpty()
        ? $sizeRangeItems->implode(' · ')
        : 'Configured sizes';
    $hasProductionOptions = ! empty($product['production_speeds'] ?? []);
    $productionStepDescription = $hasProductionOptions
        ? 'Choose your production timeline and shipping method based on your schedule.'
        : 'Choose your shipping method for this order.';
    $customizerSteps = [
        1 => ['title' => 'Price & Fabric', 'description' => 'Choose your product, fabric and options to get started.'],
        2 => ['title' => 'Sizes & Quantities', 'description' => 'Set the sizes and quantities for your order.'],
        3 => ['title' => 'Player Names & Numbers', 'description' => 'Add player names, numbers and configured item details.'],
        4 => ['title' => 'Upload Artwork', 'description' => 'Add your design files, choose from existing designs, or request our design support.'],
        5 => ['title' => 'Production & Shipping', 'description' => 'Choose your production timeline and shipping method.'],
        6 => ['title' => 'Review & Add to Cart', 'description' => 'Review your selections and add to cart.'],
    ];
@endphp

@once
<script>
window.productBuilderFabricPricing = function (config = {}) {
    const baseFactory = window.productBuilder;
    const builder = baseFactory ? baseFactory(config) : {};
    const baseSync = builder.sync || function () {};

    return Object.assign(builder, {
        activeCustomizerStep: 1,
        openCustomizerSteps: { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true },
        mobileOrderSummaryOpen: false,
        completedCustomizerStep: Object.keys(config.initial_state || {}).length ? 5 : 0,
        skuCopied: false,
        async copySku(value) {
            const text = String(value || '').trim();
            if (!text) return;
            this.skuCopied = false;
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.setAttribute('readonly', '');
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    const focusedElement = document.activeElement;
                    try {
                        area.select();
                        if (!document.execCommand('copy')) throw new Error('Copy failed');
                    } finally {
                        area.remove();
                        focusedElement?.focus({ preventScroll: true });
                    }
                }
                this.skuCopied = true;
                window.setTimeout(() => { this.skuCopied = false; }, 1600);
            } catch (error) {
                this.skuCopied = false;
                window.showStorefrontToast?.({
                    type: 'error',
                    title: 'Could not copy SKU',
                    message: 'Please select and copy the SKU manually.',
                });
            }
        },
        artworkMode: 'upload',
        artworkHelpNotes: '',
        artworkHelpStyle: 'modern',
        artworkHelpColor: '',
        scrollCustomizerStepIntoView(step, behavior = 'smooth') {
            const next = Math.max(1, Math.min(6, Number(step || 1)));
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    const panel = document.getElementById(`np-product-step-panel-${next}`);
                    const card = panel?.closest('.np-proto-step-card');
                    if (!card) return;

                    const header = document.querySelector('.np-site-header');
                    const headerHeight = header?.getBoundingClientRect().height || 0;
                    const top = window.scrollY + card.getBoundingClientRect().top - headerHeight - 12;
                    window.scrollTo({ top: Math.max(0, top), behavior });
                });
            });
        },
        isCustomizerStepOpen(step) {
            const next = Math.max(1, Math.min(6, Number(step || 1)));
            return this.openCustomizerSteps?.[next] !== false;
        },
        toggleCustomizerStep(step) {
            const next = Math.max(1, Math.min(6, Number(step || 1)));
            if (!this.openCustomizerSteps) this.openCustomizerSteps = { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true };
            this.openCustomizerSteps[next] = !this.isCustomizerStepOpen(next);
            if (this.openCustomizerSteps[next]) {
                this.activeCustomizerStep = next;
                this.scrollCustomizerStepIntoView(next);
            }
        },
        openCustomizerStep(step) {
            const next = Math.max(1, Math.min(6, Number(step || 1)));
            if (!this.openCustomizerSteps) this.openCustomizerSteps = { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true };
            this.openCustomizerSteps[next] = true;
            this.activeCustomizerStep = next;
            this.mobileOrderSummaryOpen = false;
            this.scrollCustomizerStepIntoView(next);
        },
        toggleMobileOrderSummary() {
            this.mobileOrderSummaryOpen = !this.mobileOrderSummaryOpen;
        },
        closeMobileOrderSummary() {
            this.mobileOrderSummaryOpen = false;
        },
        advanceCustomizerStep(step) {
            const current = Math.max(1, Math.min(6, Number(this.activeCustomizerStep || 1)));
            const next = Math.max(1, Math.min(6, Number(step || current)));
            if (next === current + 1) {
                this.completedCustomizerStep = Math.max(Number(this.completedCustomizerStep || 0), current);
                if (!this.openCustomizerSteps) this.openCustomizerSteps = { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true };
                this.openCustomizerSteps[current] = false;
            }
            this.openCustomizerStep(next);
        },
        setArtworkMode(mode) {
            if (!['upload', 'existing', 'help'].includes(mode)) return;
            this.artworkMode = mode;
        },
        clearRosterRow(rowIndex) {
            const row = this.rosterRows?.[Number(rowIndex)];
            if (!row) return;
            Object.keys(row.values || {}).forEach((key) => { row.values[key] = ''; });
            this.sync();
        },
        clearRosterRows() {
            (this.rosterRows || []).forEach((row) => {
                Object.keys(row.values || {}).forEach((key) => { row.values[key] = ''; });
            });
            Object.keys(this.rosterSharedValues || {}).forEach((key) => { this.rosterSharedValues[key] = ''; });
            this.sync();
        },
        startCustomizing() {
            this.openCustomizerStep(1);
        },
        hasFabricPriceTableData(table) {
            return Boolean(table && (
                (Array.isArray(table.rows) && table.rows.length)
                || (Array.isArray(table.price_tiers) && table.price_tiers.length)
            ));
        },
        normalizeFabricPriceIdentity(value) {
            return String(value || '')
                .trim()
                .toLowerCase()
                .replace(/^master:/, '')
                .replace(/^code:/, '')
                .replace(/[^a-z0-9]+/g, '');
        },
        fabricPriceCellMatchesValue(cell, value) {
            const cellIdentity = this.normalizeFabricPriceIdentity(cell);
            if (!cellIdentity) return false;

            const identities = [value?.id, value?.code, value?.label]
                .map((candidate) => this.normalizeFabricPriceIdentity(candidate))
                .filter((candidate) => candidate.length >= 2);

            return identities.some((identity) => (
                cellIdentity === identity
                || cellIdentity.includes(identity)
                || (identity.length >= 5 && identity.includes(cellIdentity))
            ));
        },
        derivedFabricPriceTableFromDefault(value) {
            const source = config.price_table || {};
            const headers = Array.isArray(source.headers) ? source.headers.slice() : [];
            const rows = Array.isArray(source.rows) ? source.rows.filter(Array.isArray) : [];
            if (!value || headers.length < 2 || !rows.length) return null;

            let fabricColumn = -1;
            let bestMatches = 0;
            headers.forEach((header, columnIndex) => {
                const matches = rows.reduce((count, row) => count + (this.fabricPriceCellMatchesValue(row?.[columnIndex], value) ? 1 : 0), 0);
                const headerText = String(header || '').toLowerCase();
                const looksLikeFabricColumn = /(fabric|material|price\s*table)/i.test(headerText);
                const weightedMatches = matches + (looksLikeFabricColumn && matches > 0 ? 0.25 : 0);
                if (weightedMatches > bestMatches) {
                    bestMatches = weightedMatches;
                    fabricColumn = columnIndex;
                }
            });

            if (fabricColumn < 0 || bestMatches <= 0) return null;
            const filteredRows = rows.filter((row) => this.fabricPriceCellMatchesValue(row?.[fabricColumn], value));
            if (!filteredRows.length) return null;

            const normalizedHeader = (header) => String(header || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
            const quantityColumn = headers.findIndex((header) => /\b(qty|quantity)\b/.test(normalizedHeader(header)));
            let productPriceColumn = headers.findIndex((header, index) => {
                if (index === fabricColumn) return false;
                const text = normalizedHeader(header);
                const isShipping = /\b(shipping|shipment|delivery|freight|surcharge)\b/.test(text);
                return !isShipping && (
                    /\b(product|unit|item) price\b/.test(text)
                    || /\bprice (product|unit|item)\b/.test(text)
                    || text === 'unit price'
                );
            });

            if (productPriceColumn < 0) {
                const highlighted = Number(source.highlight_column);
                if (Number.isInteger(highlighted) && highlighted >= 0 && highlighted < headers.length && highlighted !== fabricColumn) {
                    productPriceColumn = highlighted;
                }
            }

            const outputHeaders = headers.filter((_, index) => index !== fabricColumn);
            const outputRows = filteredRows.map((row) => row.filter((_, index) => index !== fabricColumn));
            const outputPriceColumn = productPriceColumn < 0 ? -1 : productPriceColumn - (productPriceColumn > fabricColumn ? 1 : 0);
            const outputQuantityColumn = quantityColumn < 0 ? 0 : quantityColumn - (quantityColumn > fabricColumn ? 1 : 0);
            const parseRange = (raw) => {
                const text = String(raw || '').replace(/[–—−]/g, '-').replace(/,/g, '').trim();
                let match = text.match(/^(\d+)\s*(?:-|to)\s*(\d+)$/i);
                if (match) return { min: Number(match[1]), max: Number(match[2]) };
                match = text.match(/^(\d+)\s*(?:\+|plus)?$/i);
                return match ? { min: Number(match[1]), max: null } : { min: null, max: null };
            };
            const parseMoney = (raw) => {
                const match = String(raw ?? '').match(/-?\d[\d,]*(?:\.\d+)?/);
                return match ? Number(match[0].replace(/,/g, '')) : null;
            };
            const priceTiers = outputPriceColumn < 0 ? [] : outputRows.map((row) => {
                const label = String(row?.[outputQuantityColumn] ?? '').trim();
                const range = parseRange(label);
                const unit = parseMoney(row?.[outputPriceColumn]);
                if (!Number.isFinite(range.min) || !Number.isFinite(unit)) return null;
                return { label, min: range.min, max: range.max, unit, compare_at: null, savings_label: null };
            }).filter(Boolean);
            const identity = this.normalizeFabricPriceIdentity(value.id || value.code || value.label) || 'selected';

            return {
                key: `derived:${identity}`,
                fabric_code: value.id || value.code || null,
                label: value.label || value.id || 'Selected fabric',
                headers: outputHeaders,
                rows: outputRows,
                highlight_column: outputPriceColumn >= 0 ? outputPriceColumn : Number(source.highlight_column || 1),
                note: source.note || null,
                price_tiers: priceTiers,
                derived_from_default: true,
            };
        },
        fabricPriceTableForValue(value) {
            if (!value) return null;
            if (this.hasFabricPriceTableData(value.fabric_price_table)) return value.fabric_price_table;

            const identities = [value.id, value.code, value.label]
                .map((candidate) => this.normalizeFabricPriceIdentity(candidate))
                .filter(Boolean);
            const configuredTable = (config.fabric_price_tables || []).find((table) => {
                if (!this.hasFabricPriceTableData(table)) return false;
                const tableIdentities = [table.key, table.fabric_code, table.label]
                    .map((candidate) => this.normalizeFabricPriceIdentity(candidate))
                    .filter(Boolean);
                return tableIdentities.some((candidate) => identities.includes(candidate));
            });

            return configuredTable || this.derivedFabricPriceTableFromDefault(value);
        },
        selectedFabricPriceValue() {
            for (const group of (config.option_groups || [])) {
                if ((group.display_mode || 'customer') === 'hidden') continue;
                const selectedValues = group.type === 'checkbox'
                    ? (this.multiSelections?.[group.id] || []).map((valueId) => (group.values || []).find((candidate) => String(candidate.id) === String(valueId)))
                    : [this.optionValue ? this.optionValue(group, this.selections?.[group.id]) : null];

                for (const value of selectedValues.filter(Boolean)) {
                    const table = this.fabricPriceTableForValue(value);
                    if (table) return { ...value, fabric_price_table: table };
                }
            }
            return null;
        },
        selectedFabricPriceTable() {
            return this.selectedFabricPriceValue()?.fabric_price_table || null;
        },
        selectedPricedFabricValue() {
            if (typeof this.selectedFabricPriceValue === 'function') {
                return this.selectedFabricPriceValue();
            }

            for (const group of (config.option_groups || [])) {
                if ((group.display_mode || 'customer') === 'hidden') continue;

                const selectedValues = group.type === 'checkbox'
                    ? (this.multiSelections?.[group.id] || []).map((valueId) => (group.values || []).find((candidate) => String(candidate.id) === String(valueId)))
                    : [this.optionValue ? this.optionValue(group, this.selections?.[group.id]) : null];

                for (const value of selectedValues.filter(Boolean)) {
                    const table = value?.fabric_price_table;
                    if (table && ((table.rows || []).length || (table.price_tiers || []).length)) return value;
                }
            }

            return null;
        },
        activePriceTable() {
            const fabricTable = typeof this.selectedFabricPriceTable === 'function'
                ? this.selectedFabricPriceTable()
                : this.selectedPricedFabricValue()?.fabric_price_table;

            if (fabricTable && (fabricTable.rows || []).length) return fabricTable;
            return config.price_table || null;
        },
        activePriceTiers() {
            const fabricTable = typeof this.selectedFabricPriceTable === 'function'
                ? this.selectedFabricPriceTable()
                : this.selectedPricedFabricValue()?.fabric_price_table;
            if (fabricTable?.price_tiers?.length) return fabricTable.price_tiers;
            return config.price_tiers || [];
        },
        priceTableSourceLabel() {
            const fabricValue = this.selectedPricedFabricValue();
            return fabricValue?.fabric_price_table?.label ? `${fabricValue.fabric_price_table.label} fabric price` : '';
        },
        priceTableSourceKey() {
            return this.selectedPricedFabricValue()?.fabric_price_table?.key || '';
        },
        notifyPriceTableChange() {
            window.dispatchEvent(new CustomEvent('product-price-table-updated', {
                detail: {
                    table: this.activePriceTable() || config.price_table || {},
                    label: this.priceTableSourceLabel(),
                    key: this.priceTableSourceKey(),
                },
            }));
        },
        campaignPrice(originalPrice) {
            const price = Math.max(0, Number(originalPrice || 0));
            const campaigns = Array.isArray(config.sale_campaigns) ? config.sale_campaigns : [];
            if (!campaigns.length || price <= 0) return price;

            let bestPrice = price;
            for (const campaign of campaigns) {
                const value = Math.max(0, Number(campaign?.discount_value || 0));
                let discount = campaign?.discount_type === 'fixed' ? value : price * (value / 100);
                const maximum = Number(campaign?.maximum_discount || 0);
                if (maximum > 0) discount = Math.min(discount, maximum);
                discount = Math.min(price, Math.max(0, discount));
                bestPrice = Math.min(bestPrice, Math.max(0, price - discount));
            }
            return Number(bestPrice.toFixed(2));
        },
        tierPrice() {
            const quantity = Math.max(this.totalQuantity ? this.totalQuantity() : 1, Number(config.minimum_quantity || 1));
            const tier = (this.activePriceTiers() || []).find((candidate) => {
                return quantity >= Number(candidate.min || 1) && (candidate.max === null || candidate.max === undefined || quantity <= Number(candidate.max));
            });
            return this.campaignPrice(Number(tier?.unit ?? config.base_price ?? 0));
        },
        canAddToCart() {
            return this.activeCustomizerStep === 6;
        },
        optionGroupSummary(group) {
            if (!group) return 'Not selected';
            if (group.type === 'checkbox') {
                const labels = (this.multiSelections?.[group.id] || [])
                    .map((id) => this.optionValue?.(group, id)?.label)
                    .filter(Boolean);
                return labels.length ? labels.join(', ') : 'Not selected';
            }
            if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                return this.optionValue?.(group, this.selections?.[group.id])?.label || 'Not selected';
            }
            const value = this.inputs?.[group.id];
            return String(value || '').trim() || 'Not provided';
        },
        shippingEstimatedAmount() {
            const quantity = Math.max(0, Number(this.totalQuantity?.() || 0));
            const breakdown = this.priceAdjustments?.() || {};
            return Math.max(0, Number(breakdown.shippingPerUnit || 0) * quantity + Number(breakdown.shippingFixed || 0));
        },
        productPriceAmount() {
            return Math.max(0, Number(this.totalPrice?.() || 0) - this.shippingEstimatedAmount());
        },
        remoteAreaSurchargeAmount() {
            return 0;
        },
        estimatedOrderTotal() {
            return Math.max(0, Number(this.totalPrice?.() || 0) + this.remoteAreaSurchargeAmount());
        },
        sync() {
            baseSync.call(this);
            this.notifyPriceTableChange();
        },
    });
};
</script>
@endonce

{{-- NEXTPLAY_PRODUCT_DETAIL_PROTOTYPE --}}
<section class="np-product-detail-prototype" x-data="productBuilderFabricPricing(@js($builderConfig))" x-init="init()" @keydown.escape.window="closeSizeChart()">
    <div class="site-container">
        <section class="np-product-detail-hero" aria-labelledby="product-detail-title">
            <div class="np-product-hero-grid">
                <x-storefront.product.gallery :gallery="$product['gallery']" :badge="($product['sale_badge_label'] ?? null) ?: $product['tag']" />

                <article class="np-product-hero-content">
                    <div class="np-product-category-trail">
                        @if(filled($product['category'] ?? null))<span>{{ $product['category'] }}</span>@endif
                        @if(filled($product['subcategory'] ?? null))<span aria-hidden="true">/</span><span>{{ $product['subcategory'] }}</span>@endif
                    </div>

                    <h1 id="product-detail-title" class="np-product-title">{{ $product['title'] }}</h1>

                    <div class="np-product-title-meta">
                        <x-storefront.product.purchase-signals :product="$product" />
                    </div>

                    @if(filled($product['summary']))
                        <p class="np-product-summary">{{ $product['summary'] }}</p>
                    @endif

                    @if($productHighlights->isNotEmpty())
                        <div class="np-product-feature-strip" aria-label="Product highlights">
                            @foreach($productHighlights as $index => $feature)
                                <div class="np-product-feature-item">
                                    <span class="np-product-feature-icon" aria-hidden="true">
                                        @if($index % 4 === 0)
                                            <svg viewBox="0 0 24 24"><path d="M4 8h16M4 16h16M8 4v16M16 4v16"/></svg>
                                        @elseif($index % 4 === 1)
                                            <svg viewBox="0 0 24 24"><path d="M12 3s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11Z"/></svg>
                                        @elseif($index % 4 === 2)
                                            <svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18 3 3 0 0 0 3-3c0-1.7-1.3-3-3-3h-1a2 2 0 0 1-2-2c0-1.1.9-2 2-2h4a3 3 0 0 0 0-6h-3Z"/></svg>
                                        @endif
                                    </span>
                                    <strong>{{ $feature }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($materialGroup)
                        <div class="np-product-material-options">
                            <div class="np-product-section-label-row">
                                <h2>Material Option</h2>
                            </div>
                            <div class="np-product-material-grid">
                                @foreach($materialGroup['values'] as $value)
                                    @php
                                        $valueImages = collect($value['images'] ?? [])->map(fn ($image) => is_array($image) ? ($image['url'] ?? null) : $image)->filter()->values();
                                        $preview = $valueImages->first() ?: ($value['image'] ?? null);
                                    @endphp
                                    <button
                                        type="button"
                                        class="np-product-material-card"
                                        :class="selections[@js($materialGroup['id'])] === @js($value['id']) ? 'is-selected' : ''"
                                        @click="choose(@js($materialGroup), @js($value['id']))"
                                    >
                                        <span class="np-product-material-media">
                                            @if($preview)
                                                <img src="{{ $preview }}" alt="{{ $value['label'] }}" loading="lazy" decoding="async">
                                            @elseif(!empty($value['color']))
                                                <span class="np-product-material-swatch" style="background-color: {{ $value['color'] }}"></span>
                                            @else
                                                <span class="np-product-material-placeholder" aria-hidden="true"></span>
                                            @endif
                                        </span>
                                        <strong>{{ $value['label'] }}</strong>
                                        @if(filled($value['description'] ?? null))<small>{{ $value['description'] }}</small>@endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="np-product-order-facts">
                        <div>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg>
                            <span><small>Sizes</small><strong>{{ $sizeRangeLabel }}</strong></span>
                        </div>
                        <div>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/></svg>
                            <span><small>Minimum Order Quantity</small><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} Piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong></span>
                        </div>
                        @if($firstChartGroup)
                            <button type="button" @click="openSizeChart(@js($firstChartGroup['id']))">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
                                <strong>View Size Guide</strong><span aria-hidden="true">›</span>
                            </button>
                        @endif
                    </div>

                    <div class="np-product-hero-actions">
                        <button type="button" class="btn btn-secondary btn-xl" @click="startCustomizing()">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 4 6 6-10 10H4v-6L14 4Z"/><path d="m12 6 6 6"/></svg>
                            Start Customizing
                            <span aria-hidden="true">›</span>
                        </button>
                        <a class="btn btn-outline btn-xl" href="{{ route('quote.request', ['product' => $product['slug']]) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6"/></svg>
                            Request Bulk Quote
                        </a>
                    </div>
                    <p class="np-product-hero-help">Choose sizes, quantities, player details &amp; artwork in the next steps.</p>
                </article>
            </div>
        </section>

        <section id="configure-product" class="np-product-customizer" aria-labelledby="configure-product-heading">
            <h2 id="configure-product-heading" class="sr-only">Configure {{ $product['title'] }}</h2>

            @if($isEditing)
                <div class="np-product-edit-notice">
                    <div><strong>Editing the saved cart item</strong><p>Your saved selections are loaded. Update only what you need.</p></div>
                    <a href="{{ route('cart.index') }}" class="btn btn-outline">Cancel Editing</a>
                </div>
            @endif

            @if($errors->any())
                <div class="np-product-config-errors" role="alert">
                    <strong>Please review the product configuration.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="np-product-customizer-grid">
                <form
                    id="np-product-config-form"
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ $isEditing ? route('cart.items.options.update', $editItem['key']) : route('cart.items.store') }}"
                    class="np-product-config-form"
                    @submit="if(!validate()) $event.preventDefault()"
                >
                    @csrf
                    @if($isEditing)
                        @method('PATCH')
                        <input type="hidden" name="retained_artwork_json" :value="retainedArtworkJson()">
                    @endif
                    <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
                    <input type="hidden" name="quantity" :value="totalQuantity()">
                    <input type="hidden" name="design_option" :value="(selectionSummary() || 'Configured product').slice(0, 80)">
                    <input type="hidden" name="delivery_preference" :value="deliveryLabel()">
                    <input type="hidden" name="size_summary" :value="sizeSummary()">
                    <input type="hidden" name="artwork_status" :value="artworkLabel()">
                    <input type="hidden" name="notes" :value="`${selectionSummary()}${artworkMode === 'help' && artworkHelpNotes ? ` | Artwork help: ${artworkHelpNotes}` : ''}${artworkMode === 'help' ? ` | Design style: ${artworkHelpStyle}` : ''}${artworkMode === 'help' && artworkHelpColor ? ` | Color preference: ${artworkHelpColor}` : ''}`.slice(0, 1000)">
                    <input type="hidden" name="configuration_json" :value="configurationJson">

                    {{-- STEP 1: PRICE & FABRIC --}}
                    <section class="np-proto-step-card">
                        <x-storefront.product.customizer.step-header
                            :number="1"
                            :title="$customizerSteps[1]['title']"
                            :description="$customizerSteps[1]['description']"
                        />

                        <div id="np-product-step-panel-1" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(1)" x-cloak>
                            <div class="np-proto-step-content">
                            @if($materialGroup)
                                <div class="np-proto-option-grid np-proto-fabric-grid" role="radiogroup" aria-label="Fabric options">
                                    @foreach($materialGroup['values'] as $value)
                                        <x-storefront.product.customizer.option-choice :group="$materialGroup" :value="$value" />
                                    @endforeach
                                </div>
                            @endif

                            @if($nonMaterialOptionGroups->isNotEmpty())
                                <div class="np-proto-extra-options">
                                    @foreach($nonMaterialOptionGroups as $group)<x-storefront.product.option-group :group="$group" />@endforeach
                                </div>
                            @endif

                            <x-storefront.product.customizer.price-table />
                        </div>

                        <x-storefront.product.customizer.navigation
                            :step="1"
                            :back-to-product="true"
                            back-label="Back to Product"
                            :next-step="2"
                            next-label="Next: Sizes & Quantities"
                        />
                        </div>
                    </section>

                    {{-- STEP 2: SIZES & QUANTITIES --}}
                    <section class="np-proto-step-card" id="size-quantity">
                        <x-storefront.product.customizer.step-header
                            :number="2"
                            :title="$customizerSteps[2]['title']"
                            :description="$customizerSteps[2]['description']"
                        >
                            @if($firstChartGroup)
                                <x-slot:action>
                                    <button type="button" class="btn btn-outline np-proto-inline-action" @click="openSizeChart(@js($firstChartGroup['id']))">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
                                        View Size Guide
                                    </button>
                                </x-slot:action>
                            @endif
                        </x-storefront.product.customizer.step-header>

                        <div id="np-product-step-panel-2" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(2)" x-cloak>
                            <div class="np-proto-step-content">
                            <div class="np-proto-size-summary-row">
                                @if($materialGroup)
                                    <div class="np-proto-size-summary-fabric">
                                        <span class="np-custom-order-fabric-preview"><img x-show="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image" :src="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image" alt=""></span>
                                        <span><small>Selected Fabric</small><strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured fabric'"></strong></span>
                                        <button type="button" @click="openCustomizerStep(1)">Change</button>
                                    </div>
                                @endif
                                <div class="np-proto-size-summary-fact"><svg viewBox="0 0 24 24"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg><span><small>Sizes Available</small><strong>{{ $sizeRangeLabel }}</strong></span></div>
                                <div class="np-proto-size-summary-fact"><svg viewBox="0 0 24 24"><path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/></svg><span><small>Minimum Order Quantity</small><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} Piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong></span></div>
                                <div class="np-proto-size-summary-note"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg><span>You can add multiple sizes. Final price will be calculated based on total quantity.</span></div>
                            </div>

                            @if(!empty($product['size_groups']))
                                @foreach($product['size_groups'] as $group)
                                    <div class="np-proto-size-table-wrap">
                                        @if(count($product['size_groups']) > 1)<h4 class="np-proto-subsection-title">{{ $group['label'] }}</h4>@endif
                                        <div class="touch-scroll-x">
                                            <table class="np-proto-data-table np-proto-size-table">
                                                <thead><tr><th>Size</th><th>Sample Image</th><th>Your Quantity</th></tr></thead>
                                                <tbody>
                                                    @foreach($group['sizes'] as $size)
                                                        @php $key = $group['id'].':'.$size['code']; @endphp
                                                        <tr>
                                                            <td class="is-quantity" data-label="Size">{{ $size['label'] }}</td>
                                                            <td data-label="Sample Image"><img class="np-proto-size-sample np-proto-size-image" src="{{ $product['image'] }}" alt=""></td>
                                                            <td data-label="Your Quantity">
                                                                <div class="np-size-counter np-proto-size-counter">
                                                                    <button type="button" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)-1)" aria-label="Decrease {{ $size['label'] }} quantity">−</button>
                                                                    <input type="number" min="0" :max="config.maximum_quantity || 999" :value="quantities[@js($key)]" @change="changeQuantity(@js($key), $event.target.value)" aria-label="{{ $size['label'] }} quantity">
                                                                    <button type="button" @click="changeQuantity(@js($key), Number(quantities[@js($key)] || 0)+1)" aria-label="Increase {{ $size['label'] }} quantity">+</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="np-product-single-quantity">
                                    <div><strong>Order quantity</strong><small>Minimum {{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</small></div>
                                    <div class="np-size-counter"><button type="button" @click="setOrderQuantity(Number(orderQuantity || 0)-1)">−</button><input type="number" min="0" :max="config.maximum_quantity || 999" :value="orderQuantity" @change="setOrderQuantity($event.target.value)" aria-label="Order quantity"><button type="button" @click="setOrderQuantity(Number(orderQuantity || 0)+1)">+</button></div>
                                </div>
                            @endif
                        </div>

                        <x-storefront.product.customizer.navigation :step="2" :back-step="1" back-label="Back: Price & Fabric" :next-step="3" next-label="Next: Player Names & Numbers" />
                        </div>
                    </section>

                    {{-- STEP 3: PLAYER NAMES & NUMBERS --}}
                    <section class="np-proto-step-card" id="product-roster">
                        <x-storefront.product.customizer.step-header
                            :number="3"
                            :title="$customizerSteps[3]['title']"
                            :description="$customizerSteps[3]['description']"
                        >
                            <x-slot:action><button type="button" class="btn btn-outline np-proto-inline-action" @click="clearRosterRows()"><span aria-hidden="true">⌫</span> Clear All</button></x-slot:action>
                        </x-storefront.product.customizer.step-header>

                        <div id="np-product-step-panel-3" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(3)" x-cloak>
                            <div class="np-proto-step-content">
                            @if($rosterEnabled)
                                <div
                                    class="np-proto-empty-state np-proto-player-quantity-message"
                                    x-show="totalQuantity() <= 0"
                                    x-cloak
                                    role="status"
                                >
                                    <strong>Select a quantity from the Sizes &amp; Quantities section to add player names and numbers.</strong>
                                </div>
                                <div x-show="rosterEnabled && totalQuantity() > 0" x-cloak>
                                    <div class="np-roster-same-for-all" x-show="rosterRows.length > 0" x-cloak>
                                        <label class="np-roster-same-toggle">
                                            <input type="checkbox" :checked="rosterSameForAll" @change="setRosterSameForAll($event.target.checked)">
                                            <span><strong>Use the same name &amp; number for all items</strong><small>Enter the shared details once and apply them to every selected piece.</small></span>
                                        </label>
                                        <div class="np-roster-shared-fields" x-show="rosterSameForAll" x-cloak>
                                            @foreach($enabledRosterFields as $field)
                                                <label>
                                                    <span>{{ $field['label'] }}</span>
                                                    <input
                                                        type="text"
                                                        @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif
                                                        maxlength="{{ min(120, max(1, (int) ($field['max_length'] ?? 60))) }}"
                                                        :value="rosterSharedValues[@js($field['key'])] || ''"
                                                        @input="updateRosterSharedField(@js($field), $event.target.value)"
                                                        placeholder="{{ $field['label'] }}"
                                                    >
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="np-proto-player-summary-row">
                                        <div class="np-proto-player-product"><img src="{{ $product['image'] }}" alt=""><span><small>Selected Fabric</small>@if($materialGroup)<strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured fabric'"></strong>@else<strong>Configured fabric</strong>@endif</span><button type="button" @click="openCustomizerStep(1)">Change</button></div>
                                        <div class="np-proto-player-stat"><svg viewBox="0 0 24 24"><path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/></svg><span><small>Total Pieces</small><strong x-text="totalQuantity()"></strong></span></div>
                                        <div class="np-proto-player-stat"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span><small>Players</small><strong><span x-text="rosterRows.length"></span> entries required</strong></span></div>
                                    </div>

                                    <div class="touch-scroll-x np-proto-roster-table-wrap">
                                        <table class="np-proto-data-table np-proto-roster-table">
                                            <thead><tr><th>#</th><th>Size</th>@foreach($enabledRosterFields as $field)<th>{{ $field['label'] }}@if(($field['max_length'] ?? null))<small>(Max {{ (int) $field['max_length'] }} characters)</small>@endif</th>@endforeach<th></th></tr></thead>
                                            <tbody>
                                                <template x-for="(row, rowIndex) in rosterRows" :key="`${row.size_key || 'item'}:${rowIndex}`">
                                                    <tr>
                                                        <td data-label="#" x-text="rowIndex + 1"></td>
                                                        <td class="is-quantity" data-label="Size" x-text="row.size_label || '—'"></td>
                                                        @foreach($enabledRosterFields as $field)
                                                            <td data-label="{{ $field['label'] }}"><input class="np-proto-table-input" type="text" @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif maxlength="{{ min(120, max(1, (int) ($field['max_length'] ?? 60))) }}" x-model="row.values[@js($field['key'])]" :disabled="rosterSameForAll" @input="sync()" @change="commitRosterField(rowIndex, @js($field), $event.target.value)" placeholder="{{ $field['label'] }}"></td>
                                                        @endforeach
                                                        <td data-label="Action"><button type="button" class="np-roster-remove-button" @click="clearRosterRow(rowIndex)" aria-label="Remove player details"><span aria-hidden="true">×</span><span>Remove</span></button></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="np-proto-empty-state"><strong>Player names and numbers are not required for this product.</strong></div>
                            @endif
                        </div>

                        <x-storefront.product.customizer.navigation :step="3" :back-step="2" back-label="Back: Sizes & Quantities" :next-step="4" next-label="Next: Upload Artwork" />
                        </div>
                    </section>

                    {{-- STEP 4: ARTWORK --}}
                    <section class="np-proto-step-card" id="artwork-upload">
                        <x-storefront.product.customizer.step-header
                            :number="4"
                            :title="$customizerSteps[4]['title']"
                            :description="$customizerSteps[4]['description']"
                        />

                        <div id="np-product-step-panel-4" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(4)" x-cloak>
                            <div class="np-proto-step-content">
                            <div class="np-proto-artwork-tabs" role="tablist" aria-label="Artwork options">
                                <button type="button" :class="artworkMode === 'upload' ? 'is-active' : ''" @click="setArtworkMode('upload')"><svg viewBox="0 0 24 24"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M5 14a4 4 0 0 0 1 8h12a4 4 0 0 0 1-8"/></svg><span><strong>Upload New Artwork</strong><small>Upload your files for us to review.</small></span></button>
                                <button type="button" :class="artworkMode === 'existing' ? 'is-active' : ''" @click="setArtworkMode('existing')"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m6 16 4-4 3 3 2-2 3 3"/></svg><span><strong>Use Existing Design</strong><small>Choose from your previously uploaded designs.</small></span></button>
                                <button type="button" :class="artworkMode === 'help' ? 'is-active' : ''" @click="setArtworkMode('help')"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/></svg><span><strong>Need Help with Artwork?</strong><small>Our design team will assist you.</small></span></button>
                            </div>

                            <div x-show="artworkMode === 'upload'" x-cloak>
                                @if((bool) ($artworkUpload['enabled'] ?? false))
                                    <div class="np-proto-upload-grid">
                                        <label class="np-proto-upload-dropzone">
                                            <svg viewBox="0 0 24 24"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M5 14a4 4 0 0 0 1 8h12a4 4 0 0 0 1-8"/></svg>
                                            <strong>Drag &amp; drop files here</strong>
                                            <span>or click to browse</span>
                                            <input x-ref="artworkInput" type="file" name="artwork_files[]" multiple accept="{{ collect($artworkUpload['accepted_types'] ?? [])->map(fn ($type) => '.'.ltrim($type, '.'))->implode(',') }}" @change="handleArtworkFiles($event)" @if(($artworkUpload['required'] ?? false) && empty($existingArtwork)) required @endif>
                                        </label>
                                        <div class="np-proto-upload-specs"><strong>Supported file formats</strong><span>{{ collect($artworkUpload['accepted_types'] ?? [])->map(fn ($type) => strtoupper($type))->implode(', ') ?: 'PDF, SVG, PNG, JPG' }}</span><strong>Max file size</strong><span>{{ (int) ($artworkUpload['max_file_size_mb'] ?? 15) }} MB per file</span><small>You can upload multiple files.</small></div>
                                    </div>

                                    <div class="np-proto-uploaded-files" x-show="artworkFiles.length" x-cloak>
                                        <div class="np-proto-uploaded-files__head"><h4>Uploaded Files (<span x-text="artworkFiles.length"></span>)</h4><button type="button" @click="while(artworkFiles.length) removeArtworkFile(artworkFiles.length - 1)">Remove All</button></div>
                                        <template x-for="(file, fileIndex) in artworkFiles" :key="file.key">
                                            <div class="np-proto-file-row">
                                                <span class="np-proto-file-thumb"><img x-show="file.previewUrl || file.url" :src="file.previewUrl || file.url" alt=""><svg x-show="!file.previewUrl && !file.url" viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 12h6M9 16h6"/></svg></span>
                                                <strong x-text="file.name"></strong>
                                                <small x-text="file.sizeLabel"></small>
                                                <span class="np-proto-file-status">● <span>Uploaded</span></span>
                                                <a class="np-proto-icon-button" :href="file.previewUrl || file.url || null" target="_blank" rel="noopener" aria-label="Preview artwork"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></a>
                                                <a class="np-proto-icon-button" :href="file.previewUrl || file.url || null" :download="file.name" aria-label="Download artwork"><svg viewBox="0 0 24 24"><path d="M12 4v11M8 11l4 4 4-4"/><path d="M5 19h14"/></svg></a>
                                                <button type="button" class="np-proto-icon-button" @click="removeArtworkFile(fileIndex)" aria-label="Remove artwork file"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg></button>
                                            </div>
                                        </template>
                                    </div>
                                @else
                                    <div class="np-proto-empty-state"><strong>Artwork upload is not required for this product.</strong></div>
                                @endif

                                <div class="np-proto-info-panel"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg><div><strong>What happens next?</strong><ul><li>We will review your artwork for print readiness.</li><li>If any adjustments are needed, our team will contact you.</li><li>You can continue to the next step now, or save and come back later.</li></ul></div></div>
                            </div>

                            <div x-show="artworkMode === 'existing'" x-cloak>
                                <div class="np-proto-existing-header"><div><h4>My Designs</h4><p>Choose from designs already attached to this saved order.</p></div></div>
                                <div class="np-proto-design-grid" x-show="artworkFiles.filter(file => file.existing).length">
                                    <template x-for="file in artworkFiles.filter(file => file.existing)" :key="file.key">
                                        <article class="np-proto-design-card is-selected"><span class="np-proto-design-check">✓</span><div class="np-proto-design-media"><img x-show="file.url" :src="file.url" alt=""></div><strong x-text="file.name"></strong><small>Saved artwork</small></article>
                                    </template>
                                </div>
                                <div class="np-proto-empty-state" x-show="!artworkFiles.filter(file => file.existing).length"><strong>No saved designs are attached to this order yet.</strong><span>Upload artwork to make it available here while editing the cart item.</span></div>
                            </div>

                            <div x-show="artworkMode === 'help'" x-cloak>
                                <div class="np-proto-help-grid">
                                    <div class="np-proto-help-form">
                                        <h4>Share Your Design Ideas</h4><p>Tell us what you have in mind. Our professional design team will create the artwork for your approval.</p>
                                        <label><strong>Describe Your Ideas <span class="np-required">*</span></strong><textarea maxlength="500" x-model="artworkHelpNotes" placeholder="Example: I want a design similar to this image, with our logo and player numbers."></textarea><small><span x-text="artworkHelpNotes.length"></span>/500</small></label>
                                        <div class="np-proto-help-upload"><strong>Reference Images <span>(Optional)</span></strong><label class="np-proto-upload-dropzone is-compact"><svg viewBox="0 0 24 24"><path d="M12 16V4M8 8l4-4 4 4"/><path d="M5 14a4 4 0 0 0 1 8h12a4 4 0 0 0 1-8"/></svg><strong>Drag &amp; drop images here</strong><span>or click to browse</span><input type="file" name="artwork_files[]" multiple accept="image/png,image/jpeg,image/webp" @change="handleArtworkFiles($event)"></label></div>
                                        <div class="np-proto-help-preferences">
                                            <div class="np-proto-color-picker"><strong>Color Preference <span>(Optional)</span></strong><div><template x-for="color in ['#082b63','#ef233c','#111827','#ffffff','#f5c518','#0f9f8f','#1665d8','#ef70a1','#ff6b1a']"><button type="button" :class="artworkHelpColor === color ? 'is-selected' : ''" :style="`--swatch:${color}`" @click="artworkHelpColor = color" :aria-label="`Choose ${color} color preference`"></button></template><button type="button" class="is-more" @click="artworkHelpColor = ''" aria-label="Clear color preference">+</button></div></div>
                                            <div class="np-proto-style-picker"><strong>Design Style <span>(Optional)</span></strong><div><template x-for="style in ['modern','classic','bold','minimal','other']"><button type="button" :class="artworkHelpStyle === style ? 'is-selected' : ''" @click="artworkHelpStyle = style" x-text="style.charAt(0).toUpperCase()+style.slice(1)"></button></template></div></div>
                                        </div>
                                    </div>
                                    <aside class="np-proto-inspiration"><h4>Design Inspiration</h4><p>Use your product images as references while describing the design direction.</p><div class="np-proto-inspiration-grid">@foreach(collect($product['gallery'] ?? [])->take(6) as $image)<figure><img src="{{ $image['url'] ?? '' }}" alt=""><figcaption>Style Example {{ $loop->iteration }}</figcaption></figure>@endforeach</div></aside>
                                </div>
                                <div class="np-proto-info-panel"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg><div><strong>What happens next?</strong><ul><li>Our design team will prepare options based on your requirements.</li><li>We will send the artwork to you for approval through the normal order communication process.</li><li>You can continue to the next step now.</li></ul></div></div>
                            </div>
                        </div>

                        <x-storefront.product.customizer.navigation :step="4" :back-step="3" back-label="Back: Player Names & Numbers" :next-step="5" next-label="Next: Production & Shipping" />
                        </div>
                    </section>

                    {{-- STEP 5: PRODUCTION & SHIPPING --}}
                    <section class="np-proto-step-card" id="production-shipping">
                        <x-storefront.product.customizer.step-header
                            :number="5"
                            title="Production & Shipping"
                            :description="$productionStepDescription"
                        />

                        <div id="np-product-step-panel-5" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(5)" x-cloak>
                            <div class="np-proto-step-content">
                            <div x-show="currentProductionOptions().length > 0" class="np-proto-choice-section">
                                <h4 class="np-proto-section-title"><svg viewBox="0 0 24 24"><path d="M5 21V10l4 4V8l4 4V3h3v18H5Z"/></svg> Production Lead Time</h4>
                                <div class="np-proto-choice-grid">
                                    <template x-for="option in currentProductionOptions()" :key="option.id">
                                        <button type="button" class="np-proto-choice-card" :class="productionSpeed === option.id ? 'is-selected' : ''" @click="chooseProductionSpeed(option.id)">
                                            <span class="np-proto-choice-radio"></span>
                                            <span class="np-proto-choice-copy"><strong x-text="option.label"></strong><b x-text="productionDaysOnlyLabel(option)"></b><small x-text="option.description || chargeLabel(option)"></small></span>
                                            <span class="np-proto-choice-media">
                                                <template x-if="option.image"><img :src="option.image" :alt="option.label" loading="lazy" decoding="async"></template>
                                                <svg x-show="!option.image" viewBox="0 0 48 48"><path d="M8 34h32M12 34V17l9 6v-9l10 7V10h5v24"/><path d="M17 39h2M29 39h2"/></svg>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            @if(!empty($product['shipping_methods']))
                                <div class="np-proto-choice-section">
                                    <h4 class="np-proto-section-title"><svg viewBox="0 0 24 24"><path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg> Shipping Method</h4>
                                    <div class="np-proto-choice-grid">
                                        @foreach($product['shipping_methods'] as $method)
                                            @php $methodJson = json_encode($method, JSON_THROW_ON_ERROR); @endphp
                                            <button type="button" class="np-proto-choice-card" :class="shippingMethod === @js($method['id']) ? 'is-selected' : ''" @click="chooseShippingMethod(@js($method['id']))">
                                                <span class="np-proto-choice-radio"></span>
                                                <span class="np-proto-choice-copy"><strong>{{ $method['label'] }}</strong><b x-text="shippingDaysOnlyLabel({{ $methodJson }})"></b><small>{{ $method['description'] ?? 'Worldwide delivery option.' }}</small></span>
                                                <span class="np-proto-choice-media">
                                                    @if(filled($method['image'] ?? null))
                                                        <img src="{{ $method['image'] }}" alt="{{ $method['label'] }}" loading="lazy" decoding="async">
                                                    @else
                                                        <svg viewBox="0 0 48 48"><path d="M7 29h23V15H7z"/><path d="M30 21h7l5 6v2H30z"/><circle cx="15" cy="34" r="4"/><circle cx="35" cy="34" r="4"/></svg>
                                                    @endif
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="np-proto-delivery-panel" x-show="currentProductionOptions().length > 0 && (config.shipping_methods || []).length > 0" x-cloak>
                                <h4 class="np-proto-section-title"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg> Estimated Delivery</h4>
                                <div class="np-proto-delivery-equation"><div><svg viewBox="0 0 24 24"><path d="M5 21V10l4 4V8l4 4V3h3v18H5Z"/></svg><span><small>Production Time</small><strong x-text="productionDaysOnlyLabel()"></strong></span></div><b>+</b><div><svg viewBox="0 0 24 24"><path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/></svg><span><small>Shipping Time</small><strong x-text="shippingDaysOnlyLabel()"></strong></span></div><b>=</b><div class="is-estimate"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg><span><small>Estimated Delivery</small><strong x-text="totalDeliveryDaysLabel()"></strong><em>(After order confirmation)</em></span></div></div>
                            </div>

                            <div class="np-proto-worldwide-panel" x-show="currentProductionOptions().length === 0 && (config.shipping_methods || []).length > 0" x-cloak><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9M12 3c-3 3-4 6-4 9s1 6 4 9"/></svg><div><strong>Worldwide Shipping</strong><p>We ship worldwide including USA. Final shipping cost is calculated from the selected shipping method and order configuration.</p></div></div>

                            <div class="np-proto-important-notes"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg><div><strong>Important Notes</strong><ul><li>Production starts after artwork approval and payment confirmation.</li><li>Delivery time may vary based on order quantity, destination and customs clearance.</li><li>You will receive tracking information once your order ships.</li></ul></div></div>
                        </div>

                        <x-storefront.product.customizer.navigation :step="5" :back-step="4" back-label="Back: Upload Artwork" :next-step="6" next-label="Next: Review & Add to Cart" />
                        </div>
                    </section>

                    {{-- STEP 6: REVIEW & ADD TO CART --}}
                    <section class="np-proto-step-card" id="review-add-to-cart">
                        <x-storefront.product.customizer.step-header
                            :number="6"
                            :title="$customizerSteps[6]['title']"
                            :description="$customizerSteps[6]['description']"
                        />
                        <div id="np-product-step-panel-6" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(6)" x-cloak>
                            <div class="np-proto-step-content">
                                <div class="np-product-review-card">
                                    <div class="np-product-review-summary">
                                        <div class="np-product-review-card-title">
                                            <h3>Order Summary</h3>
                                            <button type="button" @click="openCustomizerStep(1)">Edit</button>
                                        </div>

                                        <div class="np-product-review-product">
                                            <img src="{{ $product['image'] }}" alt="{{ $product['alt'] ?? $product['title'] }}">
                                            <div>
                                                <strong>{{ $product['title'] }}</strong>
                                                @if(filled($product['sku'] ?? null))
                                                    <small>SKU: {{ $product['sku'] }}</small>
                                                @endif
                                                @if($materialGroup)
                                                    <small x-text="`Fabric: ${optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured'}`"></small>
                                                @endif
                                                <small><span x-text="totalQuantity()"></span> total pieces</small>
                                            </div>
                                        </div>

                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Selected Options</h4>
                                                <button type="button" @click="openCustomizerStep(1)">Edit</button>
                                            </div>
                                            <div class="np-review-detail-list">
                                                @if($materialGroup)
                                                    <div>
                                                        <span>{{ $materialGroup['label'] ?? 'Fabric' }}</span>
                                                        <strong x-text="optionGroupSummary(@js($materialGroup))"></strong>
                                                    </div>
                                                @endif
                                                @foreach($reviewOptionGroups as $group)
                                                    <div>
                                                        <span>{{ $group['label'] }}</span>
                                                        <strong x-text="optionGroupSummary(@js($group))"></strong>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="np-product-review-sizes np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Sizes &amp; Quantities</h4>
                                                <button type="button" @click="openCustomizerStep(2)">Edit</button>
                                            </div>
                                            @if(!empty($product['size_groups']))
                                                @foreach($product['size_groups'] as $group)
                                                    <div class="np-review-size-matrix">
                                                        @foreach($group['sizes'] as $size)
                                                            @php $key = $group['id'].':'.$size['code']; @endphp
                                                            <div :class="Number(quantities[@js($key)] || 0) > 0 ? 'is-selected' : ''">
                                                                <strong>{{ $size['label'] }}</strong>
                                                                <span x-text="quantities[@js($key)] || 0"></span>
                                                            </div>
                                                        @endforeach
                                                        <div class="is-total">
                                                            <strong>Total</strong>
                                                            <span x-text="totalQuantity()"></span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="np-review-size-matrix">
                                                    <div class="is-total"><strong>Total</strong><span x-text="totalQuantity()"></span></div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Player Names &amp; Numbers</h4>
                                                <button type="button" @click="openCustomizerStep(3)">Edit</button>
                                            </div>
                                            @if($rosterEnabled)
                                                <div class="np-review-roster-summary" x-show="rosterEnabled" x-cloak>
                                                    <div class="np-review-detail-list">
                                                        <div><span>Players</span><strong x-text="`${rosterRows.length} player${rosterRows.length === 1 ? '' : 's'}`"></strong></div>
                                                    </div>
                                                    <div class="np-review-roster-list">
                                                        <template x-for="(row, rowIndex) in rosterRows" :key="`review:${row.size_key || 'item'}:${rowIndex}`">
                                                            <div class="np-review-roster-row">
                                                                <span class="is-index" x-text="rowIndex + 1"></span>
                                                                <strong x-text="row.size_label || '—'"></strong>
                                                                @foreach($enabledRosterFields as $field)
                                                                    <span><small>{{ $field['label'] }}</small><b x-text="row.values[@js($field['key'])] || '—'"></b></span>
                                                                @endforeach
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                                <p class="np-review-empty" x-show="!rosterEnabled" x-cloak>Player details are not enabled for this order.</p>
                                            @else
                                                <p class="np-review-empty">Player details are not required for this product.</p>
                                            @endif
                                        </div>

                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Artwork</h4>
                                                <button type="button" @click="openCustomizerStep(4)">Edit</button>
                                            </div>
                                            <div class="np-review-detail-list">
                                                <div><span>Status</span><strong x-text="artworkFiles.length ? `${artworkFiles.length} file${artworkFiles.length === 1 ? '' : 's'} uploaded` : 'Not selected yet'"></strong></div>
                                            </div>
                                            <div class="np-review-artwork-list" x-show="artworkFiles.length" x-cloak>
                                                <template x-for="file in artworkFiles" :key="file.key || file.name">
                                                    <span x-text="file.name"></span>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Production &amp; Shipping</h4>
                                                <button type="button" @click="openCustomizerStep(5)">Edit</button>
                                            </div>
                                            <div class="np-review-detail-list">
                                                <div x-show="currentProductionOptions().length > 0" x-cloak><span>Production</span><strong><span x-text="speedLabel()"></span> · <span x-text="productionDaysOnlyLabel()"></span></strong></div>
                                                <div><span>Shipping</span><strong><span x-text="shippingLabel()"></span> · <span x-text="shippingDaysOnlyLabel()"></span></strong></div>
                                                <div><span>Estimated Delivery</span><strong x-text="totalDeliveryDaysLabel()"></strong></div>
                                            </div>
                                        </div>
                                    </div>

                                    <aside class="np-product-review-pricing" aria-label="Order pricing">
                                        <h3>Pricing</h3>
                                        <div><span>Unit Price</span><strong x-text="money(unitPrice())"></strong></div>
                                        <div><span>Total Pieces</span><strong x-text="totalQuantity()"></strong></div>
                                        <div><span>Minimum Order Quantity</span><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong></div>
                                        <div><span>Product Price</span><strong x-text="money(productPriceAmount())"></strong></div>
                                        <div><span>Shipping (Estimated)</span><strong x-text="money(shippingEstimatedAmount())"></strong></div>
                                        <div><span>Remote Area Surcharge</span><strong x-text="money(remoteAreaSurchargeAmount())"></strong></div>
                                        <div class="is-total"><span>Estimated Total</span><strong x-text="money(estimatedOrderTotal())"></strong></div>
                                        <button type="submit" class="btn btn-secondary btn-xl np-review-add-to-cart" :disabled="!canAddToCart()" :aria-disabled="!canAddToCart() ? 'true' : 'false'">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l3-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg>
                                            {{ $isEditing ? 'Update Cart Item' : 'Add to Cart' }}
                                        </button>
                                        <button type="button" class="btn btn-outline btn-xl np-review-save-later" @click="toggleWishlist()" :aria-pressed="wishlisted ? 'true' : 'false'">
                                            <span aria-hidden="true">♡</span><span x-text="wishlisted ? 'Saved for Later' : 'Save for Later'"></span>
                                        </button>
                                        <div class="np-product-review-trust">
                                            <div><span>✓</span><p><strong>Free design review</strong><small>We’ll check your artwork for best results</small></p></div>
                                            <div><span>♙</span><p><strong>Secure checkout</strong><small>Your information is safe with us</small></p></div>
                                            <div><span>◎</span><p><strong>Worldwide shipping</strong><small>Delivering to 100+ countries</small></p></div>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                <x-storefront.product.customizer.order-summary :product="$product" :material-group="$materialGroup" :size-range-label="$sizeRangeLabel" :is-editing="$isEditing" />
            </div>
        </section>


        @if($sizeGroupsWithCharts->isNotEmpty())
            <div x-show="sizeChartOpen" x-cloak class="np-size-chart-modal" role="dialog" aria-modal="true" aria-label="Product size chart" @click.self="closeSizeChart()">
                <div class="np-size-chart-dialog">
                    <div class="np-size-chart-dialog__head"><div><small>Administrator provided</small><h3 x-text="chartGroup()?.chart?.title || 'Size Chart'"></h3></div><button type="button" @click="closeSizeChart()" aria-label="Close size chart">×</button></div>
                    @if($sizeGroupsWithCharts->count() > 1)
                        <div class="np-size-chart-tabs" role="tablist" aria-label="Available size charts">
                            @foreach($sizeGroupsWithCharts as $group)
                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="activeChartGroup === @js($group['id']) ? 'true' : 'false'"
                                    :class="activeChartGroup === @js($group['id']) ? 'is-active' : ''"
                                    @click="activeChartGroup = @js($group['id'])"
                                >{{ $group['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                    @foreach($sizeGroupsWithCharts as $group)
                        @php $hasStructuredSizeTable = ! empty(data_get($group, 'chart.columns')) && ! empty(data_get($group, 'chart.rows')); @endphp
                        <div x-show="activeChartGroup === @js($group['id'])" x-cloak class="np-size-chart-dialog__body">
                            @if(data_get($group, 'chart.note'))<p class="np-size-chart-note">{{ data_get($group, 'chart.note') }}</p>@endif
                            @if(data_get($group, 'chart.html') && ! $hasStructuredSizeTable)<div class="product-rich-content touch-scroll-x">{!! data_get($group, 'chart.html') !!}</div>@endif
                            @if(data_get($group, 'chart.image'))<img src="{{ data_get($group, 'chart.image') }}" alt="{{ data_get($group, 'chart.title', $group['label'].' size chart') }}" loading="lazy" decoding="async">@endif
                            @if($hasStructuredSizeTable)
                                <div class="touch-scroll-x">
                                    <table><thead><tr>@foreach(data_get($group, 'chart.columns', []) as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach(data_get($group, 'chart.rows', []) as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
