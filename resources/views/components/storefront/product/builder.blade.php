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
                'purpose' => ($file['purpose'] ?? 'artwork') === 'reference' ? 'reference' : 'artwork',
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
        'is_customizable' => (bool) ($product['is_customizable'] ?? false),
        'minimum_quantity' => $product['minimum_quantity'] ?? 1,
        'maximum_quantity' => $product['maximum_quantity'] ?? 999,
        'gallery' => $product['gallery'],
        'product_profile' => $product['product_profile'] ?? 'standard',
        'option_groups' => $product['option_groups'] ?? [],
        'size_groups' => $product['size_groups'] ?? [],
        'artwork_upload' => $product['artwork_upload'] ?? ['enabled' => false],
        'artwork_preferences' => $product['artwork_preferences'] ?? [],
        'production_methods_enabled' => (bool) ($product['production_methods_enabled'] ?? false),
        'production_speeds' => $product['production_speeds'] ?? [],
        'shipping_methods' => $product['shipping_methods'] ?? [],
        'roster' => \App\Support\ProductRoster::settings($product),
        'jersey_roster' => \App\Support\ProductRoster::settings($product),
        'personalization_existing_files' => collect($editCustomization['personalization_files'] ?? [])->values()
            ->map(fn ($file, $index) => [
                'row' => (int) ($file['row'] ?? -1),
                'key' => (string) ($file['key'] ?? ''),
                'url' => $isEditing ? route('cart.items.personalization.show', ['cartItem' => (string) $editItem['key'], 'fileIndex' => $index]) : null,
            ])->all(),
        'sample' => $product['sample'] ?? ['available' => false, 'charge' => 0, 'charge_type' => 'fixed_order'],
        'price_tiers' => $product['price_tiers'] ?? [],
        'price_table' => $product['price_table'] ?? [],
        'fabric_price_tables' => $product['fabric_price_tables'] ?? [],
        'sale_campaigns' => $product['sale_campaigns'] ?? [],
        'compare_at_price' => $product['compare_at_price'] ?? null,
        'social' => $social,
        'edit_mode' => $isEditing,
        'edit_item_key' => $isEditing ? (string) $editItem['key'] : null,
        'initial_state' => $isEditing ? [
            'selections' => (array) ($editConfiguration['selections'] ?? []),
            'multi_selections' => (array) ($editConfiguration['multi_selections'] ?? []),
            'inputs' => (array) ($editConfiguration['inputs'] ?? []),
            'quantities' => (array) ($editConfiguration['quantities'] ?? []),
            'order_quantity' => (int) ($editItem['quantity'] ?? ($product['minimum_quantity'] ?? 1)),
            'shipping_method' => $editConfiguration['shipping_method'] ?? null,
            'roster_enabled' => (bool) ($editConfiguration['roster_enabled'] ?? false),
            'roster' => array_values((array) ($editConfiguration['roster'] ?? [])),
            'roster_same_for_all' => (bool) ($editConfiguration['roster_same_for_all'] ?? false),
            'roster_section_same_for_all' => (array) ($editConfiguration['roster_section_same_for_all'] ?? []),
            'sample_requested' => (bool) ($editConfiguration['sample_requested'] ?? false),
            'artwork_files' => $existingArtwork,
            'artwork_help' => (array) ($editConfiguration['artwork_help'] ?? []),
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
    $builderConfig['material_group_id'] = $materialGroup['id'] ?? null;
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
    $ui = \App\Support\ProductStorefrontUi::settings($product['storefront_ui'] ?? []);
    $sample = $product['sample'] ?? ['available' => false, 'charge' => 0, 'charge_type' => 'fixed_order'];
    $productHighlightIcons = collect($product['feature_icons'] ?? []);
    $featureStorefront = $product['feature_storefront'] ?? null;
    $hasExplicitHighlightSelection = is_array($featureStorefront);
    $productHighlightVisibility = collect($featureStorefront ?? []);
    $productHighlights = collect($product['features'] ?? [])
        ->map(fn ($feature, $index) => [
            'label' => trim((string) $feature),
            'icon' => $productHighlightIcons->get($index),
            'show' => $hasExplicitHighlightSelection
                ? (bool) $productHighlightVisibility->get($index, false)
                : true,
        ])
        ->filter(fn ($feature) => $feature['label'] !== '' && $feature['show'])
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
    $productionStepDescription = $ui['production_step_description'];
    // The roster is a real storefront step only when the product has the
    // roster feature enabled and at least one enabled roster field. When it is
    // unavailable, every later step moves up so numbering/navigation remains
    // continuous (1, 2, 3, 4, 5) with no empty Player Names & Numbers card.
    $showRosterStep = $rosterEnabled && $enabledRosterFields->isNotEmpty();
    // Keep the JavaScript/cart configuration in sync with the visible steps.
    // A product with the roster feature disabled (or with no enabled roster
    // fields) must not create hidden roster rows behind the skipped UI.
    $builderConfig['roster']['enabled'] = $showRosterStep;
    $builderConfig['jersey_roster']['enabled'] = $showRosterStep;

    $priceStep = 1;
    $sizeStep = 2;
    $rosterStep = $showRosterStep ? 3 : null;
    $artworkStep = $showRosterStep ? 4 : 3;
    $productionStep = $showRosterStep ? 5 : 4;
    $reviewStep = $showRosterStep ? 6 : 5;
    $customizerStepCount = $reviewStep;

    $customizerSteps = [
        $priceStep => ['title' => 'Price & Fabric', 'description' => 'Choose your product, fabric and options to get started.'],
        $sizeStep => ['title' => 'Sizes & Quantities', 'description' => 'Set the sizes and quantities for your order.'],
        $artworkStep => ['title' => $ui['artwork_step_title'], 'description' => $ui['artwork_step_description']],
        $productionStep => ['title' => $ui['production_step_title'], 'description' => $ui['production_step_description']],
        $reviewStep => ['title' => 'Review & Add to Cart', 'description' => 'Review your selections and add to cart.'],
    ];
    if ($showRosterStep) {
        $customizerSteps[$rosterStep] = ['title' => ($roster['template_id'] ?? null) ? ($roster['title'] ?? 'Personalization') : 'Player Names & Numbers', 'description' => ($roster['description'] ?? null) ?: (($roster['multiple_templates'] ?? false) ? 'Add your details to each selected section.' : 'Add player names, numbers and configured item details.')];
        ksort($customizerSteps);
    }

    $builderConfig['customizer_steps'] = [
        'configure-product' => $priceStep,
        'size-quantity' => $sizeStep,
        'product-roster' => $rosterStep,
        'artwork-upload' => $artworkStep,
        'production-shipping' => $productionStep,
        'review-add-to-cart' => $reviewStep,
    ];
    $builderConfig['customizer_step_count'] = $customizerStepCount;
    $builderConfig['roster_step_enabled'] = $showRosterStep;
@endphp

@once
<link rel="stylesheet" href="{{ asset('assets/item-personalization-20261027.css') }}?v=12">
<script>
window.productBuilderFabricPricing = function (config = {}) {
    const baseFactory = window.productBuilder;
    const builder = baseFactory ? baseFactory(config) : {};
    const baseSync = builder.sync || function () {};
    const baseSetRosterSameForAll = builder.setRosterSameForAll;
    const baseSetRosterSectionShared = builder.setRosterSectionShared;
    const baseSyncRosterRows = builder.syncRosterRows;

    return Object.assign(builder, {
        activeCustomizerStep: 1,
        maxCustomizerStep: Number(config.customizer_step_count || 6),
        openCustomizerSteps: Object.fromEntries(Array.from({ length: Number(config.customizer_step_count || 6) }, (_, index) => [index + 1, true])),
        mobileOrderSummaryOpen: false,
        completedCustomizerStep: Object.keys(config.initial_state || {}).length ? Math.max(0, Number(config.customizer_step_count || 6) - 1) : 0,
        skuCopied: false,
        personalizationPreviews: {},
        personalizationSharedPreviews: {},
        personalizationSharedNames: {},
        syncRosterRows() {
            baseSyncRosterRows?.call(this);
            // When size quantities change after a shared upload, newly created
            // item rows must still carry the shared filename for validation.
            const sections = config.roster?.sections || [];
            const sharedKeys = new Set();
            if (this.rosterSameForAll) (config.roster?.fields || []).filter(field => ['image', 'file'].includes(field.type)).forEach(field => sharedKeys.add(field.key));
            sections.forEach(section => {
                if (this.rosterSectionShared[String(section.id)]) (section.fields || []).filter(field => ['image', 'file'].includes(field.type)).forEach(field => sharedKeys.add(field.key));
            });
            sharedKeys.forEach(key => {
                const saved = (config.personalization_existing_files || []).find(file => file.row === -1 && file.key === key);
                const name = this.personalizationSharedNames[key] || (saved ? (config.initial_state?.roster || [])[0]?.values?.[key] : '') || '';
                if (name) (this.rosterRows || []).forEach(row => { row.values[key] = name; });
            });
            this.sync();
        },
        resetPersonalizationFileMode(fields, sharedEnabled) {
            (fields || []).filter(field => ['image', 'file'].includes(field.type)).forEach(field => {
                // An existing stored file can be retained when switching into
                // shared mode; freshly selected per-item uploads cannot.
                const retained = sharedEnabled && (config.personalization_existing_files || []).some(file =>
                    file.key === field.key && (file.row === -1 || file.row === 0));
                if (retained) return;
                this.clearPersonalizationSharedUpload(field);
                document.querySelectorAll('input[name^="personalization_files["]').forEach(input => {
                    if (input.name.endsWith(`][${field.key}]`)) input.value = '';
                });
                Object.keys(this.personalizationPreviews).filter(key => key.endsWith(`:${field.key}`)).forEach(key => {
                    const preview = this.personalizationPreviews[key];
                    if (typeof preview === 'string' && preview.startsWith('blob:')) URL.revokeObjectURL(preview);
                    delete this.personalizationPreviews[key];
                });
            });
            this.sync();
        },
        setRosterSameForAll(enabled) {
            const wasEnabled = this.rosterSameForAll;
            baseSetRosterSameForAll?.call(this, enabled);
            if (wasEnabled !== Boolean(enabled)) this.resetPersonalizationFileMode(config.roster?.fields, Boolean(enabled));
        },
        setRosterSectionShared(id, enabled) {
            const wasEnabled = Boolean(this.rosterSectionShared[String(id)]);
            baseSetRosterSectionShared?.call(this, id, enabled);
            if (wasEnabled !== Boolean(enabled)) {
                const section = (config.roster?.sections || []).find(item => String(item.id) === String(id));
                this.resetPersonalizationFileMode(section?.fields, Boolean(enabled));
            }
        },
        personalizationSharedPreview(key) {
            const live = this.personalizationSharedPreviews[key];
            if (live) return live;
            if (!this.rosterRows?.[0]?.values?.[key]) return '';
            return (config.personalization_existing_files || []).find(file => file.row === -1 && file.key === key)?.url || '';
        },
        setPersonalizationSharedUpload(field, event) {
            const file = event.target.files?.[0];
            if (!file || !field?.key) return;
            const image = field.type === 'image';
            const allowed = image
                ? ['image/png', 'image/jpeg', 'image/webp']
                : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'text/csv', 'application/csv', 'image/png', 'image/jpeg', 'image/webp'];
            if (!allowed.includes(file.type) || file.size > (image ? 5 : 10) * 1024 * 1024) {
                event.target.value = '';
                window.showStorefrontToast?.({ type: 'error', title: 'Unsupported file', message: image ? 'Upload a PNG, JPG or WebP image up to 5 MB.' : 'Upload a PDF, DOC, DOCX, TXT, CSV or image up to 10 MB.' });
                return;
            }
            const previous = this.personalizationSharedPreviews[field.key];
            if (previous?.startsWith('blob:')) URL.revokeObjectURL(previous);
            this.personalizationSharedPreviews[field.key] = image ? URL.createObjectURL(file) : '';
            this.personalizationSharedNames[field.key] = file.name;
            (this.rosterRows || []).forEach(row => { row.values[field.key] = file.name; });
            this.sync();
        },
        dropPersonalizationSharedUpload(field, event) {
            const file = event.dataTransfer?.files?.[0];
            const input = event.currentTarget.querySelector('input[type=file]');
            if (!file || !input || typeof DataTransfer === 'undefined') return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            this.setPersonalizationSharedUpload(field, { target: input });
        },
        clearPersonalizationSharedUpload(field, event) {
            if (!field?.key) return;
            const previous = this.personalizationSharedPreviews[field.key];
            if (previous?.startsWith('blob:')) URL.revokeObjectURL(previous);
            delete this.personalizationSharedPreviews[field.key];
            delete this.personalizationSharedNames[field.key];
            document.querySelectorAll('input[name^="personalization_shared_files["]').forEach(input => {
                if (input.name === `personalization_shared_files[${field.key}]`) input.value = '';
            });
            (this.rosterRows || []).forEach(row => { row.values[field.key] = ''; });
            this.sync();
        },

        applyRosterSectionSharedValues() {
            const first = this.rosterRows?.[0]?.values || {};
            (config.roster?.sections || []).forEach(section => {
                if (!this.rosterSectionShared[String(section.id)]) return;
                (this.rosterRows || []).forEach(row => (section.fields || []).filter(field => !['image','file'].includes(field.type)).forEach(field => {
                    row.values[field.key] = first[field.key] || '';
                }));
            });
        },
        applyRosterSharedValues() {
            const fileKeys = new Set((config.roster?.fields || []).filter(field => ['image','file'].includes(field.type)).map(field => field.key));
            const shared = Object.fromEntries(Object.entries(this.rosterSharedValues || {}).filter(([key]) => !fileKeys.has(key)));
            (this.rosterRows || []).forEach(row => { row.values = { ...(row.values || {}), ...shared }; });
        },
        clearPersonalizationRowFiles(rowIndex) {
            document.querySelectorAll('.np-proto-roster-table input[type="file"]').forEach(input => {
                if (input.name.startsWith(`personalization_files[${rowIndex}]`)) input.value = '';
            });
            Object.keys(this.personalizationPreviews).filter(key => key.startsWith(`${rowIndex}:`)).forEach(key => {
                const url = this.personalizationPreviews[key];
                if (typeof url === 'string' && url.startsWith('blob:')) URL.revokeObjectURL(url);
                delete this.personalizationPreviews[key];
            });
        },
    personalizationPreview(rowIndex, key) {
        const reference = `${rowIndex}:${key}`;
        if (!this.rosterRows?.[rowIndex]?.values?.[key]) return '';
        if (Object.prototype.hasOwnProperty.call(this.personalizationPreviews, reference)) {
            return this.personalizationPreviews[reference] || '';
        }
        return (config.personalization_existing_files || []).find(file => file.row === rowIndex && file.key === key)?.url || '';
    },

    setPersonalizationUpload(rowIndex, field, event) {
        const file = event.target.files?.[0];
        const row = this.rosterRows?.[rowIndex];
        if (!row || !field?.key || !file || event.target.disabled) return;
        const image = field.type === 'image';
        const limit = (image ? 5 : 10) * 1024 * 1024;
        const allowed = image
            ? ['image/png', 'image/jpeg', 'image/webp']
            : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'text/csv', 'application/csv', 'image/png', 'image/jpeg', 'image/webp'];
        if (!allowed.includes(file.type) || file.size > limit) {
            event.target.value = '';
            window.alert(image ? 'Use a PNG, JPG or WebP image up to 5 MB.' : 'Use a PDF, DOC, DOCX, TXT, CSV or image file up to 10 MB.');
            return;
        }
        const reference = `${rowIndex}:${field.key}`;
        if (this.personalizationPreviews[reference]?.startsWith('blob:')) {
            URL.revokeObjectURL(this.personalizationPreviews[reference]);
        }
        this.personalizationPreviews[reference] = image ? URL.createObjectURL(file) : null;
        row.values[field.key] = file.name;
        this.sync();
    },

    dropPersonalizationUpload(rowIndex, field, event) {
        const file = event.dataTransfer?.files?.[0];
        const input = event.currentTarget.querySelector('input[type="file"]');
        if (!file || !input || input.disabled || typeof DataTransfer === 'undefined') return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        this.setPersonalizationUpload(rowIndex, field, { target: input });
    },

    clearPersonalizationUpload(rowIndex, field, event) {
        const row = this.rosterRows?.[rowIndex];
        if (!row) return;
        const reference = `${rowIndex}:${field.key}`;
        if (this.personalizationPreviews[reference]?.startsWith('blob:')) {
            URL.revokeObjectURL(this.personalizationPreviews[reference]);
        }
        delete this.personalizationPreviews[reference];
        row.values[field.key] = '';
        const input = event.currentTarget.closest('td')?.querySelector('input[type=file]');
        if (input) input.value = '';
        this.sync();
    },

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
        // Artwork mode and help selections belong to the shared builder state in storefront.js.

        scrollCustomizerStepIntoView(step, behavior = 'smooth') {
            const next = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(step || 1)));
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
            const next = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(step || 1)));
            return this.openCustomizerSteps?.[next] !== false;
        },
        toggleCustomizerStep(step) {
            const next = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(step || 1)));
            if (!this.openCustomizerSteps) this.openCustomizerSteps = Object.fromEntries(Array.from({ length: Number(this.maxCustomizerStep || 1) }, (_, index) => [index + 1, true]));
            this.openCustomizerSteps[next] = !this.isCustomizerStepOpen(next);
            if (this.openCustomizerSteps[next]) {
                this.activeCustomizerStep = next;
                this.scrollCustomizerStepIntoView(next);
            }
        },
        openCustomizerStep(step) {
            const next = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(step || 1)));
            if (!this.openCustomizerSteps) this.openCustomizerSteps = Object.fromEntries(Array.from({ length: Number(this.maxCustomizerStep || 1) }, (_, index) => [index + 1, true]));
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
            const current = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(this.activeCustomizerStep || 1)));
            const next = Math.max(1, Math.min(Number(this.maxCustomizerStep || 1), Number(step || current)));
            if (next === current + 1) {
                this.completedCustomizerStep = Math.max(Number(this.completedCustomizerStep || 0), current);
                if (!this.openCustomizerSteps) this.openCustomizerSteps = Object.fromEntries(Array.from({ length: Number(this.maxCustomizerStep || 1) }, (_, index) => [index + 1, true]));
                this.openCustomizerSteps[current] = false;
            }
            this.openCustomizerStep(next);
        },
        setArtworkMode(mode) {
            if (!['upload', 'existing', 'help'].includes(mode)) return;
            this.artworkMode = mode;
            this.sync();
        },
        clearRosterRow(rowIndex) {
            const row = this.rosterRows?.[Number(rowIndex)];
            if (!row) return;
            this.clearPersonalizationRowFiles(Number(rowIndex));
            Object.keys(row.values || {}).forEach((key) => { row.values[key] = ''; });
            this.sync();
        },
        clearRosterRows() {
            (this.rosterRows || []).forEach((row, index) => {
                this.clearPersonalizationRowFiles(index);
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
                .replace(/\b(\d+(?:\.\d+)?)\s*g(?:sm)?\b/g, '$1gsm')
                .replace(/\b(\d+(?:\.\d+)?)\s*(?:grams?|grammes?)\s*(?:per\s*(?:square\s*)?(?:meter|metre|m2|m²))?\b/g, '$1gsm')
                .replace(/\b(?:fabric|material|price|table|code)\b/g, ' ')
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
            const materialGroupId = String(config.material_group_id || '').trim();
            if (materialGroupId) {
                const group = (config.option_groups || []).find((candidate) => String(candidate?.id || '') === materialGroupId);
                if (group) {
                    const selectedId = this.selections?.[group.id];
                    const value = (this.optionValue ? this.optionValue(group, selectedId) : null)
                        || (group.values || []).find((candidate) => candidate?.default)
                        || (group.values || [])[0]
                        || null;
                    if (value) {
                        const table = this.fabricPriceTableForValue(value);
                        if (table) return { ...value, fabric_price_table: table };
                    }
                    return null;
                }
            }

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

            if (fabricTable && (fabricTable.rows || []).length) {
                return {
                    ...fabricTable,
                    note: fabricTable.note || config.price_table?.note || '',
                };
            }
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
        effectiveProductPrice(original, tier = null) {
            const originalPrice = Math.max(0, Number(original || 0));
            const tierOffer = Number(tier?.compare_at ?? 0);
            const nativeOffer = tierOffer > 0 && tierOffer < originalPrice ? tierOffer : Number(config.compare_at_price ?? 0);
            const discounted = nativeOffer > 0 && nativeOffer < originalPrice ? nativeOffer : originalPrice;
            // Use cents for the offer comparison, matching the server and cart.
            // Native product discounts and live campaigns compete; they never stack.
            return Math.min(Math.round((discounted + Number.EPSILON) * 100) / 100, this.campaignPrice(originalPrice));
        },
        tierPrice() {
            const quantity = Math.max(this.totalQuantity ? this.totalQuantity() : 1, Number(config.minimum_quantity || 1));
            const table = this.activePriceTable();
            const rowIndex = this.priceTableRowIndexForQuantity(table, quantity);
            const tier = rowIndex >= 0 ? (this.activePriceTiers() || [])[rowIndex] : null;
            const matchingTier = tier || (this.activePriceTiers() || []).find((candidate) => {
                return quantity >= Number(candidate.min || 1) && (candidate.max === null || candidate.max === undefined || quantity <= Number(candidate.max));
            });
            const column = Number(table?.highlight_column ?? 1);
            const cell = rowIndex >= 0 && column >= 1 ? table?.rows?.[rowIndex]?.[column] : null;
            const tablePrice = this.parseUnitPriceCell(cell);
            const original = tablePrice !== null && Number.isFinite(tablePrice) && tablePrice >= 0
                ? tablePrice
                : Number(matchingTier?.unit ?? config.base_price ?? 0);
            return this.effectiveProductPrice(original, matchingTier);
        },
        // Each visible product-price cell carries the exact source price and its
        // non-stacking campaign/native offer. Non-price columns stay untouched.
        priceTableCellOffer(cell, columnIndex, rowIndex) {
            const input = String(cell ?? '').trim();
            const unchanged = { label: cell ?? '—', originalLabel: '', discounted: false };
            const table = this.activePriceTable() || {};
            if (columnIndex !== Number(table.highlight_column ?? 1)) return unchanged;
            // Mirror PriceTableUnitPrice::priceInRow(), used by the server cart.
            const match = input.match(/^(USD\s*|US\$\s*|[$£€]\s*)?(\d[\d,]*(?:\.\d+)?)(\s*(?:\/|per)\s*(?:pc|pcs|piece|pieces|unit|item))?$/i);
            if (!match) return unchanged;
            const original = this.parseUnitPriceCell(cell);
            if (original === null || original <= 0) return unchanged;
            const prefix = match[1] || '';
            const suffix = match[3] || '';
            const tier = (this.activePriceTiers() || [])[rowIndex] || null;
            const offered = this.effectiveProductPrice(original, tier);
            if (offered >= original) return unchanged;
            return {
                label: `${prefix}${offered.toFixed(2)}${suffix}`,
                originalLabel: `${prefix}${original.toFixed(2)}${suffix}`,
                discounted: true,
            };
        },
        offeredTableCell(cell, columnIndex, rowIndex) {
            return this.priceTableCellOffer(cell, columnIndex, rowIndex).label;
        },
        // The "from" price is the highest-quantity table bracket for the
        // currently selected fabric (not the product's saved base/tier price).
        startingPriceOffer() {
            const table = this.activePriceTable() || {};
            const column = Number(table.highlight_column ?? 1);
            const rows = Array.isArray(table.rows) ? table.rows : [];
            for (let index = rows.length - 1; index >= 0; index -= 1) {
                const raw = rows[index]?.[column];
                const original = this.parseUnitPriceCell(raw);
                if (original !== null) {
                    const offered = this.effectiveProductPrice(original, this.activePriceTiers()?.[index]);
                    return {
                        saleLabel: `$${offered.toFixed(2)}`,
                        originalLabel: `$${original.toFixed(2)}`,
                        discounted: offered < original,
                    };
                }
            }
            const tiers = this.activePriceTiers() || [];
            const lastTier = [...tiers].filter((tier) => tier?.unit !== null && tier?.unit !== undefined
                && tier?.unit !== '' && Number.isFinite(Number(tier.unit)))
                .sort((a, b) => Number(a.min || 0) - Number(b.min || 0)).at(-1);
            const original = Math.max(0, Number(lastTier?.unit ?? config.base_price ?? 0));
            const offered = this.effectiveProductPrice(original, lastTier);
            return {
                saleLabel: `$${offered.toFixed(2)}`,
                originalLabel: `$${original.toFixed(2)}`,
                discounted: offered < original,
            };
        },
        parseUnitPriceCell(cell) {
            const match = String(cell ?? '').trim().match(/^(?:USD\s*|US\$\s*|[$£€]\s*)?(\d[\d,]*(?:\.\d+)?)(?:\s*(?:\/|per)\s*(?:pc|pcs|piece|pieces|unit|item))?$/i);
            if (!match) return null;
            const price = Number(match[1].replace(/,/g, ''));
            return Number.isFinite(price) && price >= 0 ? Math.round((price + Number.EPSILON) * 100) / 100 : null;
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
        shippingCombinedAmount() {
            const quantity = Math.max(0, Number(this.totalQuantity?.() || 0));
            const breakdown = this.priceAdjustments?.() || {};
            return Math.max(0, Number(breakdown.shippingPerUnit || 0) * quantity + Number(breakdown.shippingFixed || 0));
        },
        productionExtraChargeAmount() {
            const quantity = Math.max(0, Number(this.totalQuantity?.() || 0));
            const speed = this.currentProductionSpeed?.();
            return Math.max(0, Number(speed?.price_delta || 0) * quantity);
        },
        shippingExtraChargeAmount() {
            const quantity = Math.max(0, Number(this.totalQuantity?.() || 0));
            const method = (config.shipping_methods || []).find(item => item.id === this.shippingMethod);
            return Math.max(0, Number(method?.extra_charge_per_piece || 0) * quantity);
        },
        shippingEstimatedAmount() {
            return Math.max(0, this.shippingCombinedAmount() - this.shippingExtraChargeAmount());
        },
        productPriceAmount() {
            return Math.max(
                0,
                Number(this.totalPrice?.() || 0)
                    - this.shippingCombinedAmount()
                    - this.productionExtraChargeAmount(),
            );
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
                    <p x-show="startingPriceOffer().discounted" x-cloak class="mt-2 text-sm font-bold text-green-700">
                        Sale price from <span x-text="startingPriceOffer().saleLabel"></span>
                        <del class="ml-2 text-slate-500" x-text="startingPriceOffer().originalLabel"></del>
                    </p>

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
                                        {{-- A real image (not an accent-colored mask) preserves the uploaded icon's own colors and details. --}}
                                        @if(filled($feature['icon'] ?? null))
                                            <img class="np-product-feature-icon-uploaded" src="{{ $feature['icon'] }}" alt="" decoding="async" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                        @endif
                                        {{-- Keep a graphic fallback for missing or failed uploads, without a request or an extra script. --}}
                                        @if($index % 4 === 0)
                                            <svg viewBox="0 0 24 24" @if(filled($feature['icon'] ?? null)) hidden @endif><path d="M4 8h16M4 16h16M8 4v16M16 4v16"/></svg>
                                        @elseif($index % 4 === 1)
                                            <svg viewBox="0 0 24 24" @if(filled($feature['icon'] ?? null)) hidden @endif><path d="M12 3s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11Z"/></svg>
                                        @elseif($index % 4 === 2)
                                            <svg viewBox="0 0 24 24" @if(filled($feature['icon'] ?? null)) hidden @endif><path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" @if(filled($feature['icon'] ?? null)) hidden @endif><path d="M12 3a9 9 0 1 0 0 18 3 3 0 0 0 3-3c0-1.7-1.3-3-3-3h-1a2 2 0 0 1-2-2c0-1.1.9-2 2-2h4a3 3 0 0 0 0-6h-3Z"/></svg>
                                        @endif
                                    </span>
                                    <strong>{{ $feature['label'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($materialGroup)
                        <div class="np-product-material-options">
                            <div class="np-product-section-label-row">
                                <h2>Material Option</h2>
                            </div>
                            @if(($materialGroup['type'] ?? 'image') === 'image')
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
                            @else
                                <div class="np-product-material-input-style">
                                    <x-storefront.product.option-group :group="$materialGroup" :show-header="false" />
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="np-product-order-facts">
                        <div>
                            <x-storefront.product.ui-icon :src="$ui['sizes_icon']" name="sizes" />
                            <span><small>{{ $ui['sizes_label'] }}</small><strong>{{ $sizeRangeLabel }}</strong></span>
                        </div>
                        <div>
                            <x-storefront.product.ui-icon :src="$ui['minimum_order_icon']" name="box" />
                            <span><small>{{ $ui['minimum_order_label'] }}</small><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} Piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong></span>
                        </div>
                        @if($firstChartGroup)
                            <button type="button" @click="openSizeChart(@js($firstChartGroup['id']))">
                                <x-storefront.product.ui-icon :src="$ui['size_guide_icon']" name="document" />
                                <strong>{{ $ui['size_guide_label'] }}</strong><span aria-hidden="true">›</span>
                            </button>
                        @endif
                    </div>

                    <div class="np-product-hero-actions">
                        <button type="button" class="btn btn-secondary btn-xl" @click="startCustomizing()">
                            Start Customizing
                        </button>
                        <a class="btn btn-outline btn-xl" href="{{ route('quote.request', ['product' => $product['id'] ?? $product['slug']]) }}">
                            Request Bulk Quote
                        </a>
                    </div>
                    <p class="np-product-hero-help">Choose sizes, quantities, {{ ($roster['template_id'] ?? null) ? 'personalization' : 'player details' }} &amp; artwork in the next steps.</p>
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
                    <input type="hidden" name="notes" :value="`${selectionSummary()}${artworkMode === 'help' && artworkHelpNotes ? ` | Artwork help: ${artworkHelpNotes}` : ''}${artworkMode === 'help' && selectedArtworkStyle() ? ` | Design style: ${selectedArtworkStyle().name}` : ''}${artworkMode === 'help' && selectedArtworkColor() ? ` | Pantone: ${selectedArtworkColor().code}` : ''}`.slice(0, 1000)">
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
                                <x-storefront.product.option-group :group="$materialGroup" :show-header="false" />
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
                                        <x-storefront.product.ui-icon :src="$ui['size_guide_icon']" name="document" />
                                        {{ $ui['size_guide_label'] }}
                                    </button>
                                </x-slot:action>
                            @endif
                        </x-storefront.product.customizer.step-header>

                        <div id="np-product-step-panel-2" class="np-proto-step-expanded" x-show="isCustomizerStepOpen(2)" x-cloak>
                            <div class="np-proto-step-content">
                            <div class="np-proto-size-summary-layout">
                                <div class="np-proto-size-summary-row{{ $materialGroup ? '' : ' np-proto-size-summary-row--no-fabric' }}">
                                    @if($materialGroup)
                                        <div class="np-proto-size-summary-fabric" :class="{ 'is-image-empty': !optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image }">
                                            <span class="np-custom-order-fabric-preview" x-show="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image" x-cloak><img :src="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.image" alt=""></span>
                                            <span><small>Selected Fabric</small><strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured fabric'"></strong></span>
                                            <button type="button" @click="openCustomizerStep(1)">Change</button>
                                        </div>
                                    @endif
                                    <div class="np-proto-size-summary-fact"><x-storefront.product.ui-icon :src="$ui['sizes_icon']" name="sizes" /><span><small>{{ $ui['sizes_available_label'] }}</small><strong>{{ $sizeRangeLabel }}</strong></span></div>
                                    <div class="np-proto-size-summary-fact"><x-storefront.product.ui-icon :src="$ui['minimum_order_icon']" name="box" /><span><small>{{ $ui['minimum_order_label'] }}</small><strong>{{ number_format((int) ($product['minimum_quantity'] ?? 1)) }} Piece{{ (int) ($product['minimum_quantity'] ?? 1) === 1 ? '' : 's' }}</strong></span></div>
                                    <div class="np-proto-size-summary-note"><x-storefront.product.ui-icon :src="$ui['multiple_sizes_note_icon']" name="info" /><span>{{ $ui['multiple_sizes_note'] }}</span></div>
                                </div>
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

                        <x-storefront.product.customizer.navigation
                            :step="$sizeStep"
                            :back-step="$priceStep"
                            back-label="Back: Price & Fabric"
                            :next-step="$showRosterStep ? $rosterStep : $artworkStep"
                            :next-label="$showRosterStep ? ($roster['template_id'] ?? null ? 'Next: '.($roster['title'] ?? 'Personalization') : 'Next: Player Names & Numbers') : 'Next: Upload Artwork'"
                        />
                        </div>
                    </section>

                    @if($showRosterStep)
                    {{-- PLAYER NAMES & NUMBERS (conditional step) --}}
                    <section class="np-proto-step-card" id="product-roster">
                        <x-storefront.product.customizer.step-header
                            :number="$rosterStep"
                            :title="$customizerSteps[$rosterStep]['title']"
                            :description="$customizerSteps[$rosterStep]['description']"
                        >
                            <x-slot:action><button type="button" class="btn btn-outline np-proto-inline-action" @click="clearRosterRows()"><span aria-hidden="true">⌫</span> Clear All</button></x-slot:action>
                        </x-storefront.product.customizer.step-header>

                        <div id="np-product-step-panel-{{ $rosterStep }}" class="np-proto-step-expanded" x-show="isCustomizerStepOpen({{ $rosterStep }})" x-cloak>
                            <div class="np-proto-step-content">
                            @if($rosterEnabled)
                                <div
                                    class="np-proto-empty-state np-proto-player-quantity-message"
                                    x-show="totalQuantity() <= 0"
                                    x-cloak
                                    role="status"
                                >
                                    <strong>Select a quantity from the Sizes &amp; Quantities section to add {{ strtolower($customizerSteps[$rosterStep]['title']) }}.</strong>
                                </div>
                                <div x-show="rosterEnabled && totalQuantity() > 0" x-cloak>
                                    @if($roster['shared_entry_enabled'] ?? true)
                                    <div class="np-roster-same-for-all" x-show="rosterRows.length > 0" x-cloak>
                                        <label class="np-roster-same-toggle">
                                            <input type="checkbox" :checked="rosterSameForAll" @change="setRosterSameForAll($event.target.checked)">
                                            <span><strong>{{ $roster['shared_entry_label'] ?? 'Use the same name & number for all items' }}</strong><small>Enter the shared details once and apply them to every selected piece.</small></span>
                                        </label>
                                        <div class="np-roster-shared-fields" x-show="rosterSameForAll" x-cloak>
                                            @foreach($enabledRosterFields as $field)
                                                <div class="np-roster-shared-field">
                                                    <span>{{ $field['label'] }}</span>
                                                    @if(in_array($field['type'] ?? '', ['image', 'file'], true))
                                                        <div class="np-personalization-upload-input np-personalization-shared-upload">
                                                            <label class="np-proto-upload-dropzone is-compact np-personalization-upload-zone"
                                                                @dragover.prevent @drop.prevent="dropPersonalizationSharedUpload(@js($field), $event)">
                                                                <x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" />
                                                                <strong>{{ $field['type'] === 'image' ? 'Upload image' : 'Upload file' }}</strong><span>or click to browse</span>
                                                                <input type="file" name="personalization_shared_files[{{ $field['key'] }}]" :disabled="!rosterSameForAll"
                                                                    accept="{{ $field['type'] === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp' }}"
                                                                    @change="setPersonalizationSharedUpload(@js($field), $event)">
                                                            </label>
                                                            <small x-show="rosterRows[0]?.values?.[@js($field['key'])]" x-text="rosterRows[0]?.values?.[@js($field['key'])]"></small>
                                                            @if(($field['type'] ?? '') === 'image')
                                                                <img x-show="personalizationSharedPreview(@js($field['key']))" :src="personalizationSharedPreview(@js($field['key']))" alt="Selected image" class="np-personalization-preview-image">
                                                            @endif
                                                            <button type="button" class="np-personalization-clear-file" x-show="rosterRows[0]?.values?.[@js($field['key'])]" @click="clearPersonalizationSharedUpload(@js($field), $event)">Clear file</button>
                                                        </div>
                                                    @elseif(($field['type'] ?? 'text') === 'color')
                                                        <div class="np-personalization-swatches" role="group" aria-label="{{ $field['label'] }}">
                                                            @foreach(($field['color_options'] ?? []) as $option)
                                                                @php $hex = is_array($option) ? ($option['hex'] ?? '') : $option; @endphp
                                                                @if(preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex))
                                                                    <button type="button" class="np-personalization-swatch" style="--personalization-swatch:{{ strtoupper($hex) }}"
                                                                        :class="rosterSharedValues[@js($field['key'])] === @js(strtoupper($hex)) ? 'is-selected' : ''"
                                                                        :aria-pressed="String(rosterSharedValues[@js($field['key'])] === @js(strtoupper($hex)))"
                                                                        @click="updateRosterSharedField(@js($field), rosterSharedValues[@js($field['key'])] === @js(strtoupper($hex)) ? '' : @js(strtoupper($hex)))"></button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                    <input
                                                        type="text"
                                                        @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif
                                                        maxlength="{{ max(1, (int) ($field['max_length'] ?? 60)) }}"
                                                        :value="rosterSharedValues[@js($field['key'])] || ''"
                                                        @input="updateRosterSharedField(@js($field), $event.target.value)"
                                                        placeholder="{{ $field['placeholder'] ?? $field['label'] }}"
                                                    >
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    <div class="np-proto-player-summary-row">
                                        <div class="np-proto-player-product"><img src="{{ $product['image'] }}" alt=""><span><small>Selected Fabric</small>@if($materialGroup)<strong x-text="optionValue(@js($materialGroup), selections[@js($materialGroup['id'])])?.label || 'Configured fabric'"></strong>@else<strong>Configured fabric</strong>@endif</span><button type="button" @click="openCustomizerStep(1)">Change</button></div>
                                        <div class="np-proto-player-stat"><x-storefront.product.ui-icon :src="$ui['roster_total_pieces_icon']" name="sizes" /><span class="np-proto-player-stat-copy"><small>Total Pieces</small><strong x-text="totalQuantity()"></strong></span></div>
                                        <div class="np-proto-player-stat"><x-storefront.product.ui-icon :src="$ui['roster_players_icon']" name="player" /><span class="np-proto-player-stat-copy"><small>{{ ($roster['template_id'] ?? null) ? 'Entries' : 'Players' }}</small><strong><span x-text="rosterRows.length"></span> entries required</strong></span></div>
                                    </div>

                                    @if($roster['multiple_templates'] ?? false)
                                        @foreach($roster['sections'] as $section)
                                            <div class="np-item-personalization-section">
                                                <div class="np-item-personalization-section__heading">
                                                    <h3>{{ $section['title'] }}</h3>
                                                    @if(filled($section['description']))<p>{{ $section['description'] }}</p>@endif
                                                </div>
                                                @if($section['shared_entry_enabled'])
                                                    <div class="np-roster-same-for-all" x-show="rosterRows.length > 0" x-cloak>
                                                        <label class="np-roster-same-toggle">
                                                            <input type="checkbox" :checked="rosterSectionShared[@js($section['id'])] || false"
                                                                @change="setRosterSectionShared(@js($section['id']), $event.target.checked)">
                                                            <span><strong>{{ $section['shared_entry_label'] }}</strong></span>
                                                        </label>
                                                        <div class="np-roster-shared-fields" x-show="rosterSectionShared[@js($section['id'])]" x-cloak>
                                                            @foreach($section['fields'] as $field)
                                                                <div class="np-roster-shared-field"><span>{{ $field['label'] }}</span>
                                                                    @if(in_array($field['type'] ?? '', ['image', 'file'], true))
                                                        <div class="np-personalization-upload-input np-personalization-shared-upload">
                                                            <label class="np-proto-upload-dropzone is-compact np-personalization-upload-zone"
                                                                @dragover.prevent @drop.prevent="dropPersonalizationSharedUpload(@js($field), $event)">
                                                                <x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" />
                                                                <strong>{{ $field['type'] === 'image' ? 'Upload image' : 'Upload file' }}</strong><span>or click to browse</span>
                                                                <input type="file" name="personalization_shared_files[{{ $field['key'] }}]" :disabled="!rosterSectionShared[@js($section['id'])]"
                                                                    accept="{{ $field['type'] === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp' }}"
                                                                    @change="setPersonalizationSharedUpload(@js($field), $event)">
                                                            </label>
                                                            <small x-show="rosterRows[0]?.values?.[@js($field['key'])]" x-text="rosterRows[0]?.values?.[@js($field['key'])]"></small>
                                                            @if(($field['type'] ?? '') === 'image')
                                                                <img x-show="personalizationSharedPreview(@js($field['key']))" :src="personalizationSharedPreview(@js($field['key']))" alt="Selected image" class="np-personalization-preview-image">
                                                            @endif
                                                            <button type="button" class="np-personalization-clear-file" x-show="rosterRows[0]?.values?.[@js($field['key'])]" @click="clearPersonalizationSharedUpload(@js($field), $event)">Clear file</button>
                                                        </div>
                                                    @elseif(($field['type'] ?? 'text') === 'color')
                                                                        <div class="np-personalization-swatches">
                                                                            @foreach(($field['color_options'] ?? []) as $option)
                                                                                @php $hex = is_array($option) ? ($option['hex'] ?? '') : $option; @endphp
                                                                                @if(preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex))
                                                                                    <button type="button" class="np-personalization-swatch" style="--personalization-swatch:{{ strtoupper($hex) }}"
                                                                                        :class="rosterRows[0]?.values?.[@js($field['key'])] === @js(strtoupper($hex)) ? 'is-selected' : ''"
                                                                                        @click="updateRosterSectionSharedField(@js($section['id']), @js($field), rosterRows[0]?.values?.[@js($field['key'])] === @js(strtoupper($hex)) ? '' : @js(strtoupper($hex)))"></button>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <input type="text" @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif
                                                                            maxlength="{{ max(1, (int) ($field['max_length'] ?? 60)) }}"
                                                                            :value="rosterRows[0]?.values?.[@js($field['key'])] || ''"
                                                                            @input="updateRosterSectionSharedField(@js($section['id']), @js($field), $event.target.value)"
                                                                            placeholder="{{ $field['placeholder'] ?? $field['label'] }}">
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="touch-scroll-x np-proto-roster-table-wrap">
                                                    <table class="np-proto-data-table np-proto-roster-table">
                                                        <thead><tr><th>#</th><th>Size</th>@foreach($section['fields'] as $field)<th>{{ $field['label'] }}@if(in_array($field['type'] ?? 'text', ['text','number'], true) && ($field['max_length'] ?? null))<small>(Max {{ (int) $field['max_length'] }} characters)</small>@endif</th>@endforeach</tr></thead>
                                                        <tbody>
                                                            <template x-for="(row, rowIndex) in rosterRows" :key="`${row.size_key || 'item'}:${rowIndex}`">
                                                                <tr>
                                                                    <td data-label="#" x-text="rowIndex + 1"></td>
                                                                    <td class="is-quantity" data-label="Size" x-text="row.size_label || '—'"></td>
                                                                    @foreach($section['fields'] as $field)
                                                                        <td data-label="{{ $field['label'] }}">
                                                                            @if(($field['type'] ?? 'text') === 'color')
    <div class="np-personalization-swatches" role="group" aria-label="Choose {{ $field['label'] }}">
        @foreach(($field['color_options'] ?? []) as $option)
            @php $hex = is_array($option) ? ($option['hex'] ?? '') : $option; $colorName = is_array($option) ? ($option['name'] ?? $hex) : $hex; @endphp
            @if(preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex))
                <button type="button" class="np-personalization-swatch"
                    :class="row.values[@js($field['key'])] === @js(strtoupper($hex)) ? 'is-selected' : ''"
                    :aria-pressed="String(row.values[@js($field['key'])] === @js(strtoupper($hex)))"
                    :disabled="rosterSectionShared[@js($section['id'])] || false"
                    @click="row.values[@js($field['key'])] = row.values[@js($field['key'])] === @js(strtoupper($hex)) ? '' : @js(strtoupper($hex)); sync()"
                    style="--personalization-swatch:{{ strtoupper($hex) }}" title="{{ $colorName }}" aria-label="{{ $colorName }}"></button>
            @endif
        @endforeach
        @if(empty($field['color_options']))<small>No colors available</small>@endif
    </div>
@elseif(in_array($field['type'] ?? '', ['image', 'file'], true))
    <div class="np-personalization-upload-input">
        <label class="np-proto-upload-dropzone is-compact np-personalization-upload-zone {{ str_contains(strtolower($field['label']), 'logo') ? 'is-logo' : ($field['type'] === 'image' ? 'is-image' : 'is-file') }}"
            @dragover.prevent @drop.prevent="dropPersonalizationUpload(rowIndex, @js($field), $event)">
            <x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" />
            <strong>{{ $field['type'] === 'image' ? 'Upload image' : 'Upload file' }}</strong><span>or click to browse</span>
            <input type="file" :name="`personalization_files[${rowIndex}][{{ $field['key'] }}]`" :disabled="rosterSectionShared[@js($section['id'])] || false"
                accept="{{ $field['type'] === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp' }}"
                @change="setPersonalizationUpload(rowIndex, @js($field), $event)">
        </label>
        <small x-show="row.values[@js($field['key'])]" x-text="row.values[@js($field['key'])]"></small>
        <a class="np-personalization-clear-file" x-show="row.values[@js($field['key'])] && personalizationPreview(rowIndex, @js($field['key'])) && !personalizationPreview(rowIndex, @js($field['key'])).startsWith('blob:')" :href="personalizationPreview(rowIndex, @js($field['key']))" target="_blank" rel="noopener">Open saved file</a>
        @if($field['type'] === 'image')
            <img x-show="personalizationPreview(rowIndex, @js($field['key']))" :src="personalizationPreview(rowIndex, @js($field['key']))" alt="Selected image" class="np-personalization-preview-image">
        @endif
        <button type="button" class="np-personalization-clear-file" x-show="row.values[@js($field['key'])] && !(rosterSectionShared[@js($section['id'])] || false)" @click="clearPersonalizationUpload(rowIndex, @js($field), $event)">Clear file</button>
    </div>
@else
    <input class="np-proto-table-input" type="text" @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif maxlength="{{ max(1, (int) ($field['max_length'] ?? 60)) }}" x-model="row.values[@js($field['key'])]" :disabled="rosterSectionShared[@js($section['id'])] || false" @input="sync()" @change="commitRosterField(rowIndex, @js($field), $event.target.value)" placeholder="{{ $field['placeholder'] ?? $field['label'] }}">
@endif
                                                                        </td>
                                                                    @endforeach
                                                                    <td class="np-personalization-unused-action" hidden aria-hidden="true"></td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                    <div class="touch-scroll-x np-proto-roster-table-wrap">
                                        <table class="np-proto-data-table np-proto-roster-table">
                                            <thead><tr><th>#</th><th>Size</th>@foreach($enabledRosterFields as $field)<th>{{ $field['label'] }}@if(in_array($field['type'] ?? 'text', ['text','number'], true) && ($field['max_length'] ?? null))<small>(Max {{ (int) $field['max_length'] }} characters)</small>@endif</th>@endforeach</tr></thead>
                                            <tbody>
                                                <template x-for="(row, rowIndex) in rosterRows" :key="`${row.size_key || 'item'}:${rowIndex}`">
                                                    <tr>
                                                        <td data-label="#" x-text="rowIndex + 1"></td>
                                                        <td class="is-quantity" data-label="Size" x-text="row.size_label || '—'"></td>
                                                        @foreach($enabledRosterFields as $field)
                                                            <td data-label="{{ $field['label'] }}">@if(($field['type'] ?? 'text') === 'color')
    <div class="np-personalization-swatches" role="group" aria-label="Choose {{ $field['label'] }}">
        @foreach(($field['color_options'] ?? []) as $option)
            @php $hex = is_array($option) ? ($option['hex'] ?? '') : $option; $colorName = is_array($option) ? ($option['name'] ?? $hex) : $hex; @endphp
            @if(preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex))
                <button type="button" class="np-personalization-swatch"
                    :class="row.values[@js($field['key'])] === @js(strtoupper($hex)) ? 'is-selected' : ''"
                    :aria-pressed="String(row.values[@js($field['key'])] === @js(strtoupper($hex)))"
                    :disabled="rosterSameForAll"
                    @click="row.values[@js($field['key'])] = row.values[@js($field['key'])] === @js(strtoupper($hex)) ? '' : @js(strtoupper($hex)); sync()"
                    style="--personalization-swatch:{{ strtoupper($hex) }}" title="{{ $colorName }}" aria-label="{{ $colorName }}"></button>
            @endif
        @endforeach
        @if(empty($field['color_options']))<small>No colors available</small>@endif
    </div>
@elseif(in_array($field['type'] ?? '', ['image', 'file'], true))
    <div class="np-personalization-upload-input">
        <label class="np-proto-upload-dropzone is-compact np-personalization-upload-zone {{ str_contains(strtolower($field['label']), 'logo') ? 'is-logo' : ($field['type'] === 'image' ? 'is-image' : 'is-file') }}"
            @dragover.prevent @drop.prevent="dropPersonalizationUpload(rowIndex, @js($field), $event)">
            <x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" />
            <strong>{{ $field['type'] === 'image' ? 'Upload image' : 'Upload file' }}</strong><span>or click to browse</span>
            <input type="file" :name="`personalization_files[${rowIndex}][{{ $field['key'] }}]`" :disabled="rosterSameForAll"
                accept="{{ $field['type'] === 'image' ? '.png,.jpg,.jpeg,.webp' : '.pdf,.doc,.docx,.txt,.csv,.png,.jpg,.jpeg,.webp' }}"
                @change="setPersonalizationUpload(rowIndex, @js($field), $event)">
        </label>
        <small x-show="row.values[@js($field['key'])]" x-text="row.values[@js($field['key'])]"></small>
        <a class="np-personalization-clear-file" x-show="row.values[@js($field['key'])] && personalizationPreview(rowIndex, @js($field['key'])) && !personalizationPreview(rowIndex, @js($field['key'])).startsWith('blob:')" :href="personalizationPreview(rowIndex, @js($field['key']))" target="_blank" rel="noopener">Open saved file</a>
        @if($field['type'] === 'image')
            <img x-show="personalizationPreview(rowIndex, @js($field['key']))" :src="personalizationPreview(rowIndex, @js($field['key']))" alt="Selected image" class="np-personalization-preview-image">
        @endif
        <button type="button" class="np-personalization-clear-file" x-show="row.values[@js($field['key'])] && !rosterSameForAll" @click="clearPersonalizationUpload(rowIndex, @js($field), $event)">Clear file</button>
    </div>
@else
    <input class="np-proto-table-input" type="text" @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif maxlength="{{ max(1, (int) ($field['max_length'] ?? 60)) }}" x-model="row.values[@js($field['key'])]" :disabled="rosterSameForAll" @input="sync()" @change="commitRosterField(rowIndex, @js($field), $event.target.value)" placeholder="{{ $field['placeholder'] ?? $field['label'] }}">
@endif</td>
                                                        @endforeach
                                                        <td class="np-personalization-unused-action" hidden aria-hidden="true"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <x-storefront.product.customizer.navigation :step="$rosterStep" :back-step="$sizeStep" back-label="Back: Sizes & Quantities" :next-step="$artworkStep" next-label="Next: Upload Artwork" />
                        </div>
                    </section>
                    @endif

                    {{-- UPLOAD ARTWORK --}}
                    <section class="np-proto-step-card" id="artwork-upload">
                        <x-storefront.product.customizer.step-header
                            :number="$artworkStep"
                            :title="$customizerSteps[$artworkStep]['title']"
                            :description="$customizerSteps[$artworkStep]['description']"
                        />

                        <div id="np-product-step-panel-{{ $artworkStep }}" class="np-proto-step-expanded" x-show="isCustomizerStepOpen({{ $artworkStep }})" x-cloak>
                            <div class="np-proto-step-content">
                            <div class="np-proto-artwork-tabs" role="tablist" aria-label="Artwork options">
                                <button type="button" :class="artworkMode === 'upload' ? 'is-active' : ''" @click="setArtworkMode('upload')"><x-storefront.product.ui-icon :src="$ui['artwork_upload_tab_icon']" name="upload" /><span><strong>{{ $ui['artwork_upload_tab_title'] }}</strong><small>{{ $ui['artwork_upload_tab_description'] }}</small></span></button>
                                <button type="button" :class="artworkMode === 'existing' ? 'is-active' : ''" @click="setArtworkMode('existing')"><x-storefront.product.ui-icon :src="$ui['artwork_existing_tab_icon']" name="image" /><span><strong>{{ $ui['artwork_existing_tab_title'] }}</strong><small>{{ $ui['artwork_existing_tab_description'] }}</small></span></button>
                                <button type="button" :class="artworkMode === 'help' ? 'is-active' : ''" @click="setArtworkMode('help')"><x-storefront.product.ui-icon :src="$ui['artwork_help_tab_icon']" name="message" /><span><strong>{{ $ui['artwork_help_tab_title'] }}</strong><small>{{ $ui['artwork_help_tab_description'] }}</small></span></button>
                            </div>

                            <div x-show="artworkMode === 'upload'" x-cloak>
                                @if((bool) ($artworkUpload['enabled'] ?? false))
                                    <div class="np-proto-upload-grid">
                                        <label class="np-proto-upload-dropzone">
                                            <x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" />
                                            <strong>{{ $ui['artwork_drop_title'] }}</strong>
                                            <span>{{ $ui['artwork_browse_text'] }}</span>
                                            <input x-ref="artworkInput" type="file" name="artwork_files[]" multiple accept="{{ collect($artworkUpload['accepted_types'] ?? [])->map(fn ($type) => '.'.ltrim($type, '.'))->implode(',') }}" @change="handleArtworkFiles($event, 'artwork')">
                                        </label>
                                        <div class="np-proto-upload-specs"><strong>{{ $ui['artwork_formats_label'] }}</strong><span>{{ collect($artworkUpload['accepted_types'] ?? [])->map(fn ($type) => strtoupper($type))->implode(', ') ?: 'PDF, SVG, PNG, JPG' }}</span><strong>{{ $ui['artwork_max_size_label'] }}</strong><span>{{ (int) ($artworkUpload['max_file_size_mb'] ?? 15) }} MB per file</span><small>{{ $ui['artwork_multiple_files_text'] }}</small></div>
                                    </div>

                                    <div class="np-proto-uploaded-files" x-show="artworkFiles.some(file => file.purpose !== 'reference')" x-cloak>
                                        <div class="np-proto-uploaded-files__head"><h4>Uploaded Files (<span x-text="artworkFiles.filter(file => file.purpose !== 'reference').length"></span>)</h4><button type="button" @click="artworkFiles.filter(file => file.purpose !== 'reference').forEach(file => removeArtworkFile(artworkFiles.findIndex(item => item.key === file.key)))">Remove All</button></div>
                                        <template x-for="file in artworkFiles.filter(file => file.purpose !== 'reference')" :key="file.key">
                                            <div class="np-proto-file-row">
                                                <span class="np-proto-file-thumb"><img x-show="file.previewUrl || file.url" :src="file.previewUrl || file.url" alt=""><svg x-show="!file.previewUrl && !file.url" viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 12h6M9 16h6"/></svg></span>
                                                <strong x-text="file.name"></strong>
                                                <small x-text="file.sizeLabel"></small>
                                                <span class="np-proto-file-status">● <span>Uploaded</span></span>
                                                <a class="np-proto-icon-button" :href="file.previewUrl || file.url || null" target="_blank" rel="noopener" aria-label="Preview artwork"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></a>
                                                <a class="np-proto-icon-button" :href="file.previewUrl || file.url || null" :download="file.name" aria-label="Download artwork"><svg viewBox="0 0 24 24"><path d="M12 4v11M8 11l4 4 4-4"/><path d="M5 19h14"/></svg></a>
                                                <button type="button" class="np-proto-icon-button" @click="removeArtworkFile(artworkFiles.findIndex(item => item.key === file.key))" aria-label="Remove artwork file"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg></button>
                                            </div>
                                        </template>
                                    </div>
                                @else
                                    <div class="np-proto-empty-state"><strong>Artwork upload is not required for this product.</strong></div>
                                @endif

                                <div class="np-proto-info-panel"><x-storefront.product.ui-icon :src="$ui['artwork_info_icon']" name="info" /><div><strong>{{ $ui['artwork_next_title'] }}</strong><ul>@foreach(\App\Support\ProductStorefrontUi::lines($ui['artwork_next_lines']) as $line)<li>{{ $line }}</li>@endforeach</ul></div></div>
                            </div>

                            <div x-show="artworkMode === 'existing'" x-cloak>
                                <div class="np-proto-existing-header"><div><h4>My Designs</h4><p>Choose from designs already attached to this saved order.</p></div></div>
                                <div class="np-proto-design-grid" x-show="artworkFiles.filter(file => file.existing && file.purpose !== 'reference').length">
                                    <template x-for="file in artworkFiles.filter(file => file.existing && file.purpose !== 'reference')" :key="file.key">
                                        <article class="np-proto-design-card is-selected"><span class="np-proto-design-check">✓</span><div class="np-proto-design-media"><img x-show="file.url" :src="file.url" alt=""></div><strong x-text="file.name"></strong><small>Saved artwork</small></article>
                                    </template>
                                </div>
                                <div class="np-proto-empty-state" x-show="!artworkFiles.filter(file => file.existing && file.purpose !== 'reference').length"><strong>No saved designs are attached to this order yet.</strong><span>Upload artwork to make it available here while editing the cart item.</span></div>
                            </div>

                            <div x-show="artworkMode === 'help'" x-cloak>
                                <div class="np-proto-help-grid">
                                    <div class="np-proto-help-form">
                                        <label><strong>Describe Your Ideas <span class="np-required">*</span></strong><textarea maxlength="500" x-model="artworkHelpNotes" @input="sync()" placeholder="Example: I want a design similar to this image, with our logo and player numbers."></textarea><small><span x-text="artworkHelpNotes.length"></span>/500</small></label>
                                        <div class="np-proto-help-upload"><strong>Reference Images <span>(Optional)</span></strong><label class="np-proto-upload-dropzone is-compact" @dragover.prevent @drop.prevent="dropArtworkFiles($event, 'reference')"><x-storefront.product.ui-icon :src="$ui['artwork_drop_icon']" name="upload" /><strong>Drag &amp; drop images here</strong><span>or click to browse</span><input x-ref="referenceInput" type="file" name="artwork_reference_files[]" multiple accept="image/png,image/jpeg,image/webp" @change="handleArtworkFiles($event, 'reference')"></label>
                                        <div class="np-proto-uploaded-files" x-show="artworkFiles.some(file => file.purpose === 'reference')" x-cloak>
                                            <div class="np-proto-uploaded-files__head"><h4>Reference Images (<span x-text="artworkFiles.filter(file => file.purpose === 'reference').length"></span>)</h4></div>
                                            <template x-for="file in artworkFiles.filter(file => file.purpose === 'reference')" :key="file.key"><div class="np-proto-file-row"><span class="np-proto-file-thumb"><img :src="file.previewUrl || file.url" :alt="file.name"></span><strong x-text="file.name"></strong><small x-text="file.sizeLabel"></small><button type="button" class="np-proto-icon-button" @click="removeArtworkFile(artworkFiles.findIndex(item => item.key === file.key))" aria-label="Remove reference image">×</button></div></template>
                                        </div>
                                        </div>
                                        <div class="np-proto-help-preferences">
                                            <div class="np-proto-color-picker"><strong>Color Preference <span>(Optional)</span></strong>
                                                <div x-show="(config.artwork_preferences?.pantone || []).length">
                                                    <template x-for="color in (config.artwork_preferences?.pantone || [])" :key="color.id"><button type="button" :class="String(artworkHelpColor) === String(color.id) ? 'is-selected' : ''" :aria-pressed="String(artworkHelpColor) === String(color.id)" :style="`--swatch:${color.hex_color}`" @click="artworkHelpColor = String(artworkHelpColor) === String(color.id) ? null : color.id; sync()" :title="`${color.name} (${color.code})`" :aria-label="`Pantone ${color.code} — ${color.name}`"></button></template>
                                                    <button type="button" class="is-more" x-show="artworkHelpColor !== null && artworkHelpColor !== ''" x-cloak @click="artworkHelpColor = null; sync()" aria-label="Clear Pantone color preference">Clear</button>
                                                </div><p x-show="!(config.artwork_preferences?.pantone || []).length" class="text-sm text-slate-500">No Pantone colors configured for this product.</p>
                                                <small x-show="selectedArtworkColor()" x-text="selectedArtworkColor() ? `${selectedArtworkColor().name} — Pantone ${selectedArtworkColor().code}` : ''"></small>
                                            </div>
                                            <div class="np-proto-style-picker"><strong>Design Style <span>(Optional)</span></strong><div>
                                                <template x-for="style in (config.artwork_preferences?.style || [])" :key="style.id"><button type="button" :class="String(artworkHelpStyle) === String(style.id) ? 'is-selected' : ''" :aria-pressed="String(artworkHelpStyle) === String(style.id)" @click="artworkHelpStyle = String(artworkHelpStyle) === String(style.id) ? null : style.id; sync()" x-text="style.name"></button></template>
                                                <span x-show="!(config.artwork_preferences?.style || []).length" class="text-sm text-slate-500">No styles configured for this product.</span>
                                                </div></div>
                                        </div>
                                    </div>
                                    <aside class="np-proto-inspiration"><h4>Design Inspiration</h4><p>Images from this product, for reference when describing your ideas.</p><div class="np-proto-inspiration-grid">@forelse(collect($product['gallery'] ?? [])->filter(fn ($image) => filled($image['url'] ?? null) && !str_contains($image['url'], 'product-placeholder.svg'))->take(6) as $image)<figure><img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? $product['title'] }}" loading="lazy"></figure>@empty<p>No design images available for this product yet.</p>@endforelse</div></aside>
                                </div>
                                <div class="np-proto-info-panel"><x-storefront.product.ui-icon :src="$ui['artwork_info_icon']" name="info" /><div><strong>{{ $ui['artwork_help_next_title'] }}</strong><ul>@foreach(\App\Support\ProductStorefrontUi::lines($ui['artwork_help_next_lines']) as $line)<li>{{ $line }}</li>@endforeach</ul></div></div>
                            </div>
                        </div>

                        <x-storefront.product.customizer.navigation
                            :step="$artworkStep"
                            :back-step="$showRosterStep ? $rosterStep : $sizeStep"
                            :back-label="$showRosterStep ? 'Back: '.($customizerSteps[$rosterStep]['title'] ?? 'Personalization') : 'Back: Sizes & Quantities'"
                            :next-step="$productionStep"
                            next-label="Next: Production & Shipping"
                        />
                        </div>
                    </section>

                    {{-- PRODUCTION & SHIPPING --}}
                    <section class="np-proto-step-card" id="production-shipping">
                        <x-storefront.product.customizer.step-header
                            :number="$productionStep"
                            :title="$ui['production_step_title']"
                            :description="$productionStepDescription"
                        />

                        <div id="np-product-step-panel-{{ $productionStep }}" class="np-proto-step-expanded" x-show="isCustomizerStepOpen({{ $productionStep }})" x-cloak>
                            <div class="np-proto-step-content">
                            @if(!empty($product['shipping_methods']))
                                <div class="np-proto-choice-section">
                                    <h4 class="np-proto-section-title">{{ $ui['shipping_method_label'] }}</h4>
                                    <div class="np-proto-choice-grid">
                                        @foreach($product['shipping_methods'] as $method)
                                            @php
                                                $methodJson = json_encode($method, JSON_THROW_ON_ERROR);
                                                $isStandardShipping = str_contains(strtolower((string) ($method['id'] ?? '')), 'standard')
                                                    || str_contains(strtolower((string) ($method['label'] ?? '')), 'standard shipping');
                                            @endphp
                                            <button type="button" class="np-proto-choice-card" :class="shippingMethod === @js($method['id']) ? 'is-selected' : ''" @click="chooseShippingMethod(@js($method['id']))">
                                                <span class="np-proto-choice-radio"></span>
                                                <span class="np-proto-choice-copy"><strong>{{ $method['label'] }}</strong><b x-text="shippingDaysOnlyLabel({{ $methodJson }})"></b><small>{{ $method['description'] ?? 'Worldwide delivery option.' }}</small></span>
                                                <span class="np-proto-choice-media">
                                                    @if(filled($method['image'] ?? null))
                                                        <img src="{{ $method['image'] }}" alt="{{ $method['label'] }}" @class(['np-proto-shipping-image--standard' => $isStandardShipping]) loading="lazy" decoding="async">
                                                    @else
                                                        <x-storefront.product.ui-icon :src="$ui['shipping_method_card_icon']" name="truck" />
                                                    @endif
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="np-proto-delivery-panel" x-show="currentProductionOptions().length > 0 && (config.shipping_methods || []).length > 0" x-cloak>
                                <h4 class="np-proto-section-title">{{ $ui['estimated_delivery_title'] }}</h4>
                                <div class="np-proto-delivery-equation"><div><x-storefront.product.ui-icon :src="$ui['production_time_icon']" name="factory" /><span><small>{{ $ui['production_time_label'] }}</small><strong x-text="productionDaysOnlyLabel()"></strong></span></div><b>+</b><div><x-storefront.product.ui-icon :src="$ui['shipping_time_icon']" name="truck" /><span><small>{{ $ui['shipping_time_label'] }}</small><strong x-text="shippingDaysOnlyLabel()"></strong></span></div><b>=</b><div class="is-estimate"><x-storefront.product.ui-icon :src="$ui['estimated_delivery_icon']" name="calendar" /><span><small>{{ $ui['estimated_delivery_label'] }}</small><strong x-text="totalDeliveryDaysLabel()"></strong><em>{{ $ui['estimated_delivery_note'] }}</em></span></div></div>
                            </div>

                            <div class="np-proto-worldwide-panel" x-show="currentProductionOptions().length === 0 && (config.shipping_methods || []).length > 0" x-cloak><x-storefront.product.ui-icon :src="$ui['worldwide_shipping_icon']" name="globe" /><div><strong>{{ $ui['worldwide_shipping_title'] }}</strong><p>{{ $ui['worldwide_shipping_text'] }}</p></div></div>

                            <div class="np-proto-important-notes"><x-storefront.product.ui-icon :src="$ui['important_notes_icon']" name="info" /><div><strong>{{ $ui['important_notes_title'] }}</strong><ul>@foreach(\App\Support\ProductStorefrontUi::lines($ui['important_notes_lines']) as $line)<li>{{ $line }}</li>@endforeach</ul></div></div>
                        </div>

                        <x-storefront.product.customizer.navigation :step="$productionStep" :back-step="$artworkStep" back-label="Back: Upload Artwork" :next-step="$reviewStep" next-label="Next: Review & Add to Cart" />
                        </div>
                    </section>

                    {{-- REVIEW & ADD TO CART --}}
                    <section class="np-proto-step-card" id="review-add-to-cart">
                        <x-storefront.product.customizer.step-header
                            :number="$reviewStep"
                            :title="$customizerSteps[$reviewStep]['title']"
                            :description="$customizerSteps[$reviewStep]['description']"
                        />
                        <div id="np-product-step-panel-{{ $reviewStep }}" class="np-proto-step-expanded" x-show="isCustomizerStepOpen({{ $reviewStep }})" x-cloak>
                            <div class="np-proto-step-content">
                                <div class="np-product-review-card np-product-review-card--single">
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

                                        @if($showRosterStep)
                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>{{ $customizerSteps[$rosterStep]['title'] ?? 'Player Names & Numbers' }}</h4>
                                                <button type="button" @click="openCustomizerStep({{ $rosterStep }})">Edit</button>
                                            </div>
                                            @if($rosterEnabled)
                                                <div class="np-review-roster-summary" x-show="rosterEnabled" x-cloak>
                                                    <div class="np-review-detail-list">
                                                        <div><span>{{ ($roster['template_id'] ?? null) ? 'Entries' : 'Players' }}</span><strong x-text="`${rosterRows.length} {{ ($roster['template_id'] ?? null) ? 'entries' : 'players' }}`"></strong></div>
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
                                                <p class="np-review-empty" x-show="!rosterEnabled" x-cloak>Personalization details are not enabled for this order.</p>
                                            @else
                                                <p class="np-review-empty">Personalization details are not required for this product.</p>
                                            @endif
                                        </div>

                                        @endif

                                        <div class="np-product-review-section">
                                            <div class="np-product-review-card-title">
                                                <h4>Artwork</h4>
                                                <button type="button" @click="openCustomizerStep({{ $artworkStep }})">Edit</button>
                                            </div>
                                            <div class="np-review-detail-list">
                                                <div><span>Status</span><strong x-text="artworkMode === 'help' ? 'Design help requested' : artworkLabel()"></strong></div>
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
                                                <button type="button" @click="openCustomizerStep({{ $productionStep }})">Edit</button>
                                            </div>
                                            <div class="np-review-detail-list">
                                                <div x-show="currentProductionOptions().length > 0" x-cloak><span>Production time</span><strong x-text="productionDaysOnlyLabel()"></strong></div>
                                                <div><span>Shipping</span><strong><span x-text="shippingLabel()"></span> · <span x-text="shippingDaysOnlyLabel()"></span></strong></div>
                                                <div><span>{{ $ui['estimated_delivery_label'] }}</span><strong x-text="totalDeliveryDaysLabel()"></strong></div>
                                            </div>
                                        </div>
                                        <x-storefront.product.customizer.order-actions :product="$product" :is-editing="$isEditing" :mobile-review="true" />
                                    </div>


                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                <x-storefront.product.customizer.order-summary :product="$product" :material-group="$materialGroup" :size-range-label="$sizeRangeLabel" :is-editing="$isEditing" :roster-step="$rosterStep" :artwork-step="$artworkStep" :production-step="$productionStep" :ui="$ui" />
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
