import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const slugify = (value = '') => String(value)
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

window.adminProductForm = (initial = {}) => ({
    productName: initial.productName || '',
    slug: initial.slug || '',
    slugTouched: Boolean(initial.slug),
    productProfile: initial.productProfile || 'standard',
    shippingMethodsEnabled: Boolean(initial.shippingMethodsEnabled),
    jerseyRosterEnabled: Boolean(initial.jerseyRosterEnabled),
    jerseyRosterOptional: initial.jerseyRosterOptional !== false,
    categoryId: String(initial.categoryId || ''),
    subcategoryId: String(initial.subcategoryId || ''),
    subcategories: initial.subcategories || [],
    features: initial.features?.length ? initial.features : [''],
    specifications: initial.specifications?.length ? initial.specifications : [{ name: '', value: '' }],
    imageUrls: initial.imageUrls?.length ? initial.imageUrls : [{ url: '', alt: '', is_primary: true }],
    newImagePreviews: [],
    priceHeaders: initial.priceHeaders?.length ? initial.priceHeaders : ['Unit Price', 'Savings'],
    priceRows: initial.priceRows?.length ? initial.priceRows : [{ minimum_quantity: 1, maximum_quantity: '', cells: ['$0.00', '—'] }],
    optionGroups: initial.optionGroups?.length ? initial.optionGroups : [],
    newFeatureDialogOpen: false,
    newFeatureName: '',
    newFeatureNameError: '',
    sizeGroups: initial.sizeGroups?.length ? initial.sizeGroups : [{ existing_id: '', name: 'Adult Unisex', code: 'adult-unisex', sizes_text: 'XS, S, M, L, XL, 2XL, 3XL', chart_enabled: false, chart_title: 'Adult Unisex Size Chart', chart_note: '', chart_columns_text: 'Size, Chest, Length', chart_rows_text: '', chart_image_url: '', chart_image_preview: '', clear_chart_image: false, is_active: true }],
    artworkUploadEnabled: Boolean(initial.artworkUploadEnabled),
    artworkUploadRequired: Boolean(initial.artworkUploadRequired),
    artworkUploadTitle: initial.artworkUploadTitle || 'Upload Custom Artwork',
    artworkUploadDescription: initial.artworkUploadDescription || 'Upload one or more artwork files for the production team.',
    artworkUploadMaxFiles: Number(initial.artworkUploadMaxFiles || 5),
    artworkUploadMaxFileSizeMb: Number(initial.artworkUploadMaxFileSizeMb || 15),
    artworkUploadAcceptedTypes: initial.artworkUploadAcceptedTypes || 'pdf,svg,png,jpg,jpeg,webp',
    productionRanges: initial.productionRanges?.length ? initial.productionRanges : [],
    productionOptionDialogOpen: false,
    productionOptionRangeIndex: null,
    productionOptionEditingIndex: null,
    productionOptionDialogError: '',
    productionOptionDraft: { name: '', code: '', description: '', price_adjustment: 0, minimum_days: 1, maximum_days: 1 },
    shippingMethods: initial.shippingMethods?.length ? initial.shippingMethods : [],
    rosterFields: initial.rosterFields?.length ? initial.rosterFields : [
        { key: 'name', label: 'Player name', type: 'text', max_length: 60, required: false, enabled: true },
        { key: 'number', label: 'Player number', type: 'number', max_length: 4, required: false, enabled: true },
        { key: 'front', label: 'Front text / position', type: 'text', max_length: 80, required: false, enabled: false },
        { key: 'back', label: 'Back text / position', type: 'text', max_length: 80, required: false, enabled: false },
    ],
    faqs: initial.faqs?.length ? initial.faqs : [{ question: '', answer: '', is_active: true }],

    init() {
        this.normalizePriceRows();
        this.syncProductionRangesWithPriceRows();
        this.optionGroups.forEach(group => {
            group.client_key ||= this.clientKey();
            group.display_mode = 'customer';
            group.fixed_value_code ||= '';
            group.fixed_text_value ||= '';
            group.show_in_summary = this.booleanValue(group.show_in_summary, true);
            group.is_active = this.booleanValue(group.is_active, true);
            group.catalog_attribute_id ||= '';
            group.use_as_filter = this.booleanValue(group.use_as_filter, false);
            this.normalizeFilterSetting(group);
            (group.values || []).forEach(value => {
                value.client_key ||= this.clientKey();
                value.existing_id ||= '';
                value.image_url ||= '';
                value.image_previews = Array.isArray(value.image_previews) ? value.image_previews : (value.image_preview ? [value.image_preview] : []);
                value.image_error = false;
                value.color_hex ||= '';
                value.charge_type ||= 'per_unit';
                value.is_default = this.booleanValue(value.is_default, false);
                value.is_active = this.booleanValue(value.is_active, true);
                value.clear_images = this.booleanValue(value.clear_images, false);
            });
        });
        this.sizeGroups.forEach(group => {
            group.existing_id ||= '';
            group.chart_enabled = Boolean(group.chart_enabled);
            group.chart_title ||= `${group.name || 'Product'} Size Chart`;
            group.chart_note ||= '';
            group.chart_columns_text ||= '';
            group.chart_rows_text ||= '';
            group.chart_image_url ||= '';
            group.chart_image_preview ||= group.chart_image_url || '';
            group.clear_chart_image = Boolean(group.clear_chart_image);
        });
        this.shippingMethods.forEach(method => {
            method.charge_type ||= 'per_unit';
            method.is_default = Boolean(method.is_default);
            method.is_active = method.is_active !== false;
        });
    },
    booleanValue(value, fallback = false) {
        if (value === undefined || value === null || value === '') return fallback;
        return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
    },
    updateSlug() { if (!this.slugTouched) this.slug = slugify(this.productName); },
    touchSlug() { this.slugTouched = true; this.slug = slugify(this.slug); },
    visibleSubcategories() { return this.subcategories.filter(item => String(item.parent_id) === String(this.categoryId)); },
    addFeature() { this.features.push(''); },
    addSpecification() { this.specifications.push({ name: '', value: '' }); },
    addImageUrl() { this.imageUrls.push({ url: '', alt: '', is_primary: false }); },
    setPrimaryImage(index) { this.imageUrls.forEach((item, itemIndex) => item.is_primary = itemIndex === index); },
    previewProductImages(event) {
        this.newImagePreviews.forEach(image => URL.revokeObjectURL(image.url));
        const files = Array.from(event.target.files || []);
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        const invalid = files.find(file => !allowed.includes(file.type) || file.size > 5 * 1024 * 1024);

        if (invalid || files.length > 20) {
            event.target.value = '';
            this.newImagePreviews = [];
            window.alert('Choose up to 20 JPG, PNG, WebP, or AVIF images, each no larger than 5 MB.');
            return;
        }

        this.newImagePreviews = files.map(file => ({
            client_key: this.clientKey(),
            file,
            name: String(file.name || 'Product image').slice(0, 255),
            url: URL.createObjectURL(file),
            sizeLabel: file.size >= 1024 * 1024
                ? `${(file.size / (1024 * 1024)).toFixed(2)} MB`
                : `${Math.max(1, Math.round(file.size / 1024))} KB`,
        }));
    },
    removeProductImage(index) {
        const removed = this.newImagePreviews.splice(index, 1)[0];
        if (removed?.url) URL.revokeObjectURL(removed.url);

        const transfer = new DataTransfer();
        this.newImagePreviews.forEach(image => transfer.items.add(image.file));
        if (this.$refs.productImageInput) this.$refs.productImageInput.files = transfer.files;
    },
    addPriceHeader() { this.priceHeaders.push('New Column'); this.normalizePriceRows(); },
    removePriceHeader(index) {
        if (this.priceHeaders.length <= 1) return;
        this.priceHeaders.splice(index, 1);
        this.priceRows.forEach(row => row.cells.splice(index, 1));
    },
    addPriceRow() {
        this.priceRows.push({ minimum_quantity: '', maximum_quantity: '', cells: this.priceHeaders.map(() => '') });
        this.syncProductionRangesWithPriceRows();
    },
    removePriceRow(index) {
        this.priceRows.splice(index, 1);
        this.syncProductionRangesWithPriceRows();
    },
    normalizePriceRows() {
        this.priceRows = this.priceRows.map(row => {
            const sourceCells = Array.isArray(row) ? row.slice(1) : (Array.isArray(row.cells) ? row.cells : []);
            return {
                minimum_quantity: Array.isArray(row) ? (row[0] || '') : (row.minimum_quantity ?? ''),
                maximum_quantity: Array.isArray(row) ? '' : (row.maximum_quantity ?? ''),
                cells: this.priceHeaders.map((_, index) => sourceCells[index] ?? ''),
            };
        });
    },
    optionValueTemplate(overrides = {}) {
        return {
            existing_id: '', client_key: this.clientKey(), label: '', code: '', description: '',
            color_hex: '', image_url: '', image_previews: [], image_error: false, clear_images: false,
            price_adjustment: 0, charge_type: 'per_unit', stock_quantity: '', is_default: false,
            is_active: true, ...overrides,
        };
    },
    choiceInputTypes() {
        return ['image', 'swatch', 'buttons', 'select', 'checkbox'];
    },
    uniqueOptionGroupCode(name) {
        const rawBase = slugify(name) || `feature-${this.optionGroups.length + 1}`;
        const base = rawBase.slice(0, 150).replace(/-+$/g, '') || `feature-${this.optionGroups.length + 1}`;
        const usedCodes = new Set(this.optionGroups.map(group => String(group.code || '')).filter(Boolean));
        let candidate = base;
        let suffix = 2;

        while (usedCodes.has(candidate)) {
            const suffixText = `-${suffix++}`;
            candidate = `${base.slice(0, Math.max(1, 160 - suffixText.length))}${suffixText}`;
        }

        return candidate;
    },
    openNewFeatureDialog() {
        this.newFeatureName = '';
        this.newFeatureNameError = '';
        this.newFeatureDialogOpen = true;
        this.$nextTick(() => this.$refs.newFeatureNameInput?.focus());
    },
    closeNewFeatureDialog() {
        this.newFeatureDialogOpen = false;
        this.newFeatureName = '';
        this.newFeatureNameError = '';
    },
    confirmNewFeature() {
        const name = String(this.newFeatureName || '').trim();

        if (!name) {
            this.newFeatureNameError = 'Enter a feature name before continuing.';
            this.$nextTick(() => this.$refs.newFeatureNameInput?.focus());
            return;
        }

        if (name.length > 160) {
            this.newFeatureNameError = 'Feature names cannot exceed 160 characters.';
            return;
        }

        const duplicate = this.optionGroups.some(group => String(group.name || '').trim().toLowerCase() === name.toLowerCase());
        if (duplicate) {
            this.newFeatureNameError = 'A feature with this name already exists for this product.';
            return;
        }

        const group = this.addOptionGroup(name);
        this.closeNewFeatureDialog();

        this.$nextTick(() => {
            const selector = `[data-option-group-key="${group.client_key}"]`;
            document.querySelector(selector)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    },
    addOptionGroup(name) {
        const group = {
            client_key: this.clientKey(), name, code: this.uniqueOptionGroupCode(name), section: 'product', type: 'select', display_mode: 'customer',
            fixed_value_code: '', fixed_text_value: '', show_in_summary: true, use_as_filter: false, catalog_attribute_id: '', description: '',
            placeholder: '', is_required: false, minimum_selections: '', maximum_selections: '', accepted_file_types: '', maximum_file_size_mb: 15,
            is_active: true, values: [this.optionValueTemplate({ is_default: true })],
        };

        this.optionGroups.push(group);
        return group;
    },
    removeOptionGroup(index) {
        this.optionGroups.splice(index, 1);
    },
    normalizeOptionGroupType(group) {
        group.display_mode = 'customer';
        group.show_in_summary = true;
        group.is_active = true;
        group.fixed_value_code = '';
        group.fixed_text_value = '';

        if (this.choiceInputTypes().includes(group.type) && (!Array.isArray(group.values) || group.values.length === 0)) {
            group.values = [this.optionValueTemplate({ is_default: true })];
        }
    },
    removeOptionValue(group, index) {
        group.values.splice(index, 1);
        if (group.values.length > 0 && !group.values.some(value => value.is_default)) {
            group.values[0].is_default = true;
        }
    },
    updateGroupCode(group) { if (!group.code) group.code = this.uniqueOptionGroupCode(group.name); },
    canUseAsFilter(group) {
        return group
            && group.is_active !== false
            && group.display_mode !== 'hidden'
            && ['image', 'swatch', 'buttons', 'select', 'checkbox'].includes(group.type);
    },
    normalizeFilterSetting(group) {
        if (!this.canUseAsFilter(group)) group.use_as_filter = false;
    },
    filterableOptionGroups() {
        return this.optionGroups.filter(group => group.use_as_filter && this.canUseAsFilter(group));
    },
    dynamicAttributeIds() {
        return this.filterableOptionGroups()
            .map(group => Number(group.catalog_attribute_id || 0))
            .filter(Boolean);
    },
    filterPreviewValues(group) {
        const values = (group.values || []).filter(value => value.is_active !== false && (value.label || value.code));
        if (group.display_mode !== 'fixed') return values;
        const fixed = values.find(value => value.code === group.fixed_value_code)
            || values.find(value => value.is_default)
            || values[0];
        return fixed ? [fixed] : [];
    },
    addOptionValue(group) { group.values.push(this.optionValueTemplate()); },
    updateValueCode(value) { if (!value.code) value.code = slugify(value.label); },
    clientKey() { return `${Date.now()}-${Math.random().toString(36).slice(2)}`; },
    validHex(value) { return /^#?[0-9a-fA-F]{3}$|^#?[0-9a-fA-F]{6}$/.test(String(value || '').trim()); },
    normalizedHex(value) {
        let hex = String(value || '').trim().replace(/^#/, '');
        if (/^[0-9a-fA-F]{3}$/.test(hex)) hex = hex.split('').map(character => character + character).join('');
        return /^[0-9a-fA-F]{6}$/.test(hex) ? `#${hex.toUpperCase()}` : '#E2E8F0';
    },
    formatHex(value) {
        if (this.validHex(value.color_hex)) value.color_hex = this.normalizedHex(value.color_hex);
        else value.color_hex = String(value.color_hex || '').trim().toUpperCase();
    },
    previewOptionImages(event, value) {
        const files = Array.from(event.target.files || []);
        if (!files.length) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        if (files.some(file => !allowed.includes(file.type) || file.size > 5 * 1024 * 1024)) {
            event.target.value = '';
            window.alert('Choose up to 12 JPG, PNG, WebP, or AVIF images, each no larger than 5 MB.');
            return;
        }
        value.image_error = false;
        value.image_previews = [...(value.image_previews || []), ...files.map(file => URL.createObjectURL(file))].slice(0, 12);
        value.clear_images = false;
    },
    clearOptionImages(value) { value.image_previews = []; value.image_url = ''; value.clear_images = true; },
    setDefaultValue(group, valueIndex) {
        group.values.forEach((value, index) => value.is_default = index === valueIndex);
        group.fixed_value_code = group.values[valueIndex]?.code || '';
    },
    addSizeGroup() {
        this.sizeGroups.push({ existing_id: '', name: '', code: '', sizes_text: '', chart_enabled: false, chart_title: '', chart_note: '', chart_columns_text: 'Size, Chest, Length', chart_rows_text: '', chart_image_url: '', chart_image_preview: '', clear_chart_image: false, is_active: true });
    },
    previewSizeChartImage(event, group) {
        const file = event.target.files?.[0];
        if (!file) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        if (!allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
            event.target.value = '';
            window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 5 MB.');
            return;
        }
        group.chart_image_preview = URL.createObjectURL(file);
        group.clear_chart_image = false;
    },
    clearSizeChartImage(group) { group.chart_image_preview = ''; group.chart_image_url = ''; group.clear_chart_image = true; },
    productionQuantityLabel(range) {
        const minimum = Math.max(1, Number(range?.minimum_quantity || 1));
        const maximum = range?.maximum_quantity === '' || range?.maximum_quantity === null || range?.maximum_quantity === undefined
            ? null
            : Number(range.maximum_quantity);
        return maximum ? `${minimum}–${maximum} pieces` : `${minimum}+ pieces`;
    },
    productionChargeLabel(option) {
        const amount = Number(option?.price_adjustment || 0);
        return amount > 0 ? `+$${amount.toFixed(2)} / piece` : 'Included';
    },
    uniqueProductionCode(name, rangeIndex, optionIndex = null) {
        const range = this.productionRanges[rangeIndex] || { minimum_quantity: 1, maximum_quantity: '' };
        const rangeSuffix = `${Number(range.minimum_quantity || 1)}-${range.maximum_quantity === '' || range.maximum_quantity === null || range.maximum_quantity === undefined ? 'plus' : Number(range.maximum_quantity)}`;
        const base = `${slugify(name) || 'production'}-${rangeSuffix}`;
        const used = new Set(this.productionRanges
            .flatMap(item => item.options || [])
            .filter(option => option !== this.productionRanges[rangeIndex]?.options?.[optionIndex])
            .map(option => String(option.code || ''))
            .filter(Boolean));
        let code = base;
        let suffix = 2;
        while (used.has(code)) code = `${base}-${suffix++}`;
        return code;
    },
    syncProductionRangesWithPriceRows() {
        const existing = Array.isArray(this.productionRanges) ? this.productionRanges : [];
        this.productionRanges = this.priceRows.map((row, index) => {
            const minimum = Math.max(1, Number(row.minimum_quantity || 1));
            const maximum = row.maximum_quantity === '' || row.maximum_quantity === null || row.maximum_quantity === undefined
                ? ''
                : Math.max(minimum, Number(row.maximum_quantity));
            const exact = existing.find(range => Number(range.minimum_quantity || 0) === minimum
                && String(range.maximum_quantity ?? '') === String(maximum));
            const previous = exact || existing[index] || {};
            const options = Array.isArray(previous.options) ? previous.options.slice(0, 3) : [];

            return {
                client_key: previous.client_key || this.clientKey(),
                minimum_quantity: minimum,
                maximum_quantity: maximum,
                options: options.map(option => ({
                    client_key: option.client_key || this.clientKey(),
                    name: String(option.name || ''),
                    code: String(option.code || ''),
                    description: String(option.description || ''),
                    price_adjustment: Number(option.price_adjustment || 0),
                    minimum_days: Math.max(0, Number(option.minimum_days ?? 1)),
                    maximum_days: Math.max(Number(option.minimum_days ?? 1), Number(option.maximum_days ?? option.minimum_days ?? 1)),
                    is_active: true,
                })),
            };
        });
    },
    openProductionOptionDialog(rangeIndex, optionIndex = null) {
        const range = this.productionRanges[rangeIndex];
        if (!range || (optionIndex === null && range.options.length >= 3)) return;

        const option = optionIndex === null ? null : range.options[optionIndex];
        this.productionOptionRangeIndex = rangeIndex;
        this.productionOptionEditingIndex = optionIndex;
        this.productionOptionDialogError = '';
        this.productionOptionDraft = option ? {
            name: String(option.name || ''),
            code: String(option.code || ''),
            description: String(option.description || ''),
            price_adjustment: Number(option.price_adjustment || 0),
            minimum_days: Math.max(0, Number(option.minimum_days ?? 1)),
            maximum_days: Math.max(0, Number(option.maximum_days ?? option.minimum_days ?? 1)),
        } : {
            name: '',
            code: '',
            description: '',
            price_adjustment: 0,
            minimum_days: 1,
            maximum_days: 1,
        };
        this.productionOptionDialogOpen = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => this.$root.querySelector('[x-model="productionOptionDraft.name"]')?.focus());
    },
    closeProductionOptionDialog() {
        this.productionOptionDialogOpen = false;
        this.productionOptionRangeIndex = null;
        this.productionOptionEditingIndex = null;
        this.productionOptionDialogError = '';
        document.documentElement.classList.remove('overflow-hidden');
    },
    saveProductionOption() {
        const rangeIndex = this.productionOptionRangeIndex;
        const range = this.productionRanges[rangeIndex];
        if (!range) return this.closeProductionOptionDialog();

        const name = String(this.productionOptionDraft.name || '').trim();
        const minimumDays = Math.max(0, Number(this.productionOptionDraft.minimum_days || 0));
        const maximumDays = Math.max(0, Number(this.productionOptionDraft.maximum_days || 0));
        if (!name) {
            this.productionOptionDialogError = 'Enter the production option name.';
            return;
        }
        if (maximumDays < minimumDays) {
            this.productionOptionDialogError = 'Maximum days cannot be less than minimum days.';
            return;
        }
        if (this.productionOptionEditingIndex === null && range.options.length >= 3) {
            this.productionOptionDialogError = 'A quantity range can contain a maximum of three production options.';
            return;
        }

        const option = {
            client_key: this.productionOptionEditingIndex === null
                ? this.clientKey()
                : (range.options[this.productionOptionEditingIndex]?.client_key || this.clientKey()),
            name,
            code: this.productionOptionDraft.code || this.uniqueProductionCode(name, rangeIndex, this.productionOptionEditingIndex),
            description: String(this.productionOptionDraft.description || '').trim(),
            price_adjustment: Math.max(0, Number(this.productionOptionDraft.price_adjustment || 0)),
            minimum_days: minimumDays,
            maximum_days: maximumDays,
            is_active: true,
        };

        if (this.productionOptionEditingIndex === null) range.options.push(option);
        else range.options.splice(this.productionOptionEditingIndex, 1, option);
        this.closeProductionOptionDialog();
    },
    removeProductionOption(rangeIndex, optionIndex) {
        const range = this.productionRanges[rangeIndex];
        if (!range) return;
        range.options.splice(optionIndex, 1);
    },
    addShippingMethod() { this.shippingMethods.push({ name: '', code: '', description: '', price_adjustment: 0, charge_type: 'per_unit', minimum_days: 1, maximum_days: 1, is_default: this.shippingMethods.length === 0, is_active: true }); },
    setDefaultShipping(index) { this.shippingMethods.forEach((method, methodIndex) => method.is_default = methodIndex === index); },
    addRosterField() { this.rosterFields.push({ key: '', label: '', type: 'text', max_length: 60, required: false, enabled: true }); },
    addFaq() { this.faqs.push({ question: '', answer: '', is_active: true }); },
});

window.adminRichEditor = (initial = '') => ({
    value: initial || '',
    init() {
        this.$refs.editor.innerHTML = this.value;
        this.sync();
    },
    command(command, value = null) {
        this.$refs.editor.focus();
        document.execCommand(command, false, value);
        this.sync();
    },
    createLink() {
        const url = window.prompt('Enter a secure URL (https://, mailto:, tel:, /path or #anchor):');
        if (url) this.command('createLink', url);
    },
    sync() {
        this.value = this.$refs.editor.innerHTML;
    },
});


window.productBuilder = (config = {}) => ({
    config,
    galleryIndex: 0,
    selections: {},
    multiSelections: {},
    inputs: {},
    quantities: {},
    activeSizeGroup: config.size_groups?.[0]?.id || null,
    artworkFiles: [],
    productionSpeed: null,
    shippingMethod: config.shipping_methods?.find(method => method.default)?.id || config.shipping_methods?.[0]?.id || null,
    rosterEnabled: Boolean(config.jersey_roster?.enabled && !config.jersey_roster?.optional),
    rosterRows: [],
    sizeChartOpen: false,
    activeChartGroup: null,
    configurationJson: '{}',

    init() {
        (config.option_groups || []).forEach(group => {
            const mode = group.display_mode || 'customer';
            const values = group.values || [];

            if (group.type === 'checkbox') {
                this.multiSelections[group.id] = mode === 'fixed'
                    ? values.filter(value => value.default).map(value => value.id)
                    : [];
                return;
            }

            if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                const fixed = values.find(value => value.id === group.fixed_value_code);
                const defaultValue = fixed || values.find(value => value.default) || values[0];
                this.selections[group.id] = defaultValue?.id || '';
                return;
            }

            this.inputs[group.id] = mode === 'fixed' ? String(group.fixed_text_value || '') : '';
        });

        (config.size_groups || []).forEach(group => {
            (group.sizes || []).forEach(size => {
                this.quantities[`${group.id}:${size.code}`] = 0;
            });
        });

        this.syncProductionSpeed();
        this.syncRosterRows();
        this.sync();
    },

    money(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: config.currency || 'USD',
        }).format(Number(value || 0));
    },

    currentImage() {
        return config.gallery?.[this.galleryIndex] || config.gallery?.[0] || { url: '', alt: config.title || '' };
    },

    optionValue(group, id) {
        return (group.values || []).find(value => value.id === id);
    },

    choose(group, id) {
        if ((group.display_mode || 'customer') !== 'customer') return;
        this.selections[group.id] = id;
        this.sync();
    },

    toggle(group, id) {
        if ((group.display_mode || 'customer') !== 'customer') return;
        const values = [...(this.multiSelections[group.id] || [])];
        const index = values.indexOf(id);
        const maximum = Math.max(1, Number(group.maximum_selections || (group.values || []).length || 1));

        if (index >= 0) {
            values.splice(index, 1);
        } else if (values.length < maximum) {
            values.push(id);
        }

        this.multiSelections[group.id] = values;
        this.sync();
    },

    changeQuantity(key, amount) {
        const maximum = Number(config.maximum_quantity || 999);
        this.quantities[key] = Math.max(0, Math.min(maximum, Number(amount || 0)));
        this.syncProductionSpeed();
        this.syncRosterRows();
        this.sync();
    },

    totalQuantity() {
        return Object.values(this.quantities).reduce((sum, value) => sum + Number(value || 0), 0);
    },

    effectiveProductionQuantity() {
        return Math.max(this.totalQuantity(), Number(config.minimum_quantity || 1));
    },

    productionOptionsForQuantity(quantity = this.effectiveProductionQuantity()) {
        return (config.production_speeds || []).filter(speed => {
            const minimum = Number(speed.minimum_quantity || 1);
            const maximum = speed.maximum_quantity === null || speed.maximum_quantity === '' || speed.maximum_quantity === undefined
                ? null
                : Number(speed.maximum_quantity);
            return quantity >= minimum && (maximum === null || quantity <= maximum);
        });
    },

    currentProductionOptions() {
        return this.productionOptionsForQuantity();
    },

    syncProductionSpeed() {
        const options = this.productionOptionsForQuantity();
        if (!options.some(option => option.id === this.productionSpeed)) {
            this.productionSpeed = options[0]?.id || null;
        }
    },

    chooseProductionSpeed(id) {
        if (!this.productionOptionsForQuantity().some(option => option.id === id)) return;
        this.productionSpeed = id;
        this.sync();
    },

    currentProductionSpeed() {
        const options = this.productionOptionsForQuantity();
        return options.find(option => option.id === this.productionSpeed) || options[0] || null;
    },

    tierPrice() {
        const quantity = Math.max(this.totalQuantity(), Number(config.minimum_quantity || 1));
        const tier = (config.price_tiers || []).find(item => quantity >= Number(item.min)
            && (item.max === null || item.max === '' || quantity <= Number(item.max)));
        return Number(tier?.unit ?? config.base_price ?? 0);
    },

    applyPricedValue(breakdown, value) {
        if (!value) return;
        const amount = Number(value.price_delta || 0);
        const chargeType = value.charge_type || 'per_unit';

        if (chargeType === 'fixed_order') breakdown.fixed += amount;
        else if (chargeType !== 'included') breakdown.perUnit += amount;
    },

    priceAdjustments() {
        const breakdown = { perUnit: 0, fixed: 0, shippingPerUnit: 0, shippingFixed: 0 };

        (config.option_groups || []).forEach(group => {
            if ((group.display_mode || 'customer') === 'hidden') return;

            if (group.type === 'checkbox') {
                (this.multiSelections[group.id] || []).forEach(id => this.applyPricedValue(breakdown, this.optionValue(group, id)));
            } else if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                this.applyPricedValue(breakdown, this.optionValue(group, this.selections[group.id]));
            }
        });

        const speed = this.currentProductionSpeed();
        breakdown.perUnit += Number(speed?.price_delta || 0);

        const shipping = (config.shipping_methods || []).find(item => item.id === this.shippingMethod);
        if (shipping) {
            const amount = Number(shipping.price_delta || 0);
            if (shipping.charge_type === 'fixed_order') {
                breakdown.fixed += amount;
                breakdown.shippingFixed += amount;
            } else if (shipping.charge_type !== 'included') {
                breakdown.perUnit += amount;
                breakdown.shippingPerUnit += amount;
            }
        }

        return breakdown;
    },

    optionSurcharge() {
        const quantity = this.totalQuantity();
        const breakdown = this.priceAdjustments();
        return breakdown.perUnit + (quantity > 0 ? breakdown.fixed / quantity : 0);
    },

    fixedOrderSurcharge() {
        return this.priceAdjustments().fixed;
    },

    unitPrice() {
        return Math.max(0, this.tierPrice() + this.optionSurcharge());
    },

    sizeUnitPrice() {
        return this.unitPrice();
    },

    totalPrice() {
        const quantity = this.totalQuantity();
        if (quantity <= 0) return 0;
        const breakdown = this.priceAdjustments();
        return Math.max(0, (this.tierPrice() + breakdown.perUnit) * quantity + breakdown.fixed);
    },

    sizeSummary() {
        const parts = [];
        (config.size_groups || []).forEach(group => {
            const sizes = (group.sizes || []).map(size => {
                const quantity = Number(this.quantities[`${group.id}:${size.code}`] || 0);
                return quantity > 0 ? `${size.label} × ${quantity}` : null;
            }).filter(Boolean);
            if (sizes.length) parts.push(`${group.label}: ${sizes.join(', ')}`);
        });
        return parts.join('; ');
    },

    selectionSummary() {
        const parts = [];
        (config.option_groups || []).forEach(group => {
            if ((group.display_mode || 'customer') === 'hidden' || group.show_in_summary === false) return;

            if (group.type === 'checkbox') {
                const labels = (this.multiSelections[group.id] || [])
                    .map(id => this.optionValue(group, id)?.label)
                    .filter(Boolean);
                if (labels.length) parts.push(`${group.label}: ${labels.join(', ')}`);
            } else if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                const label = this.optionValue(group, this.selections[group.id])?.label;
                if (label) parts.push(`${group.label}: ${label}`);
            } else if (this.inputs[group.id]) {
                parts.push(`${group.label}: ${this.inputs[group.id]}`);
            }
        });
        return parts.join('; ');
    },

    artworkLabel() {
        if (!config.artwork_upload?.enabled) return 'Not requested';
        if (!this.artworkFiles.length) return config.artwork_upload?.required ? 'Artwork required' : 'No artwork selected';
        return `${this.artworkFiles.length} artwork file${this.artworkFiles.length === 1 ? '' : 's'} selected`;
    },

    handleArtworkFiles(event) {
        const files = Array.from(event.target.files || []);
        const maximumFiles = Math.max(1, Math.min(12, Number(config.artwork_upload?.max_files || 5)));
        const maximumBytes = Math.max(1, Math.min(25, Number(config.artwork_upload?.max_file_size_mb || 15))) * 1024 * 1024;
        const allowed = (config.artwork_upload?.accepted_types || ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'])
            .map(type => String(type).toLowerCase().replace(/^\./, ''));

        const invalid = files.find(file => {
            const extension = String(file.name || '').split('.').pop().toLowerCase();
            return !allowed.includes(extension) || file.size > maximumBytes;
        });

        if (files.length > maximumFiles || invalid) {
            event.target.value = '';
            this.artworkFiles = [];
            window.alert(`Choose no more than ${maximumFiles} approved artwork files, each no larger than ${config.artwork_upload?.max_file_size_mb || 15} MB.`);
            this.sync();
            return;
        }

        this.artworkFiles = files.map(file => ({
            name: String(file.name || '').slice(0, 255),
            size: Number(file.size || 0),
            sizeLabel: file.size >= 1024 * 1024
                ? `${(file.size / (1024 * 1024)).toFixed(2)} MB`
                : `${Math.max(1, Math.round(file.size / 1024))} KB`,
        }));
        this.sync();
    },

    speedLabel() {
        return this.currentProductionSpeed()?.label || 'Standard production';
    },

    productionRangeLabel() {
        const speed = this.currentProductionSpeed();
        if (!speed) return '';
        const minimum = Number(speed.minimum_quantity || 1);
        const maximum = speed.maximum_quantity === null || speed.maximum_quantity === '' || speed.maximum_quantity === undefined
            ? null
            : Number(speed.maximum_quantity);
        return maximum ? `${minimum}–${maximum} pieces` : `${minimum}+ pieces`;
    },

    shippingLabel() {
        return (config.shipping_methods || []).find(item => item.id === this.shippingMethod)?.label || 'Standard shipping';
    },

    deliveryLabel() {
        const labels = [];
        if (this.currentProductionSpeed()) labels.push(this.speedLabel());
        if ((config.shipping_methods || []).length) labels.push(this.shippingLabel());
        return labels.join(' · ') || 'Standard delivery';
    },

    chargeLabel(value) {
        const amount = Number(value?.price_delta || 0);
        if (!amount || value?.charge_type === 'included') return 'Included';
        return `${amount > 0 ? '+' : '−'}${this.money(Math.abs(amount))}${value?.charge_type === 'fixed_order' ? ' / order' : ' / piece'}`;
    },

    shippingChargeLabel(method) {
        const amount = Number(method?.price_delta || 0);
        if (!amount || method?.charge_type === 'included') return 'Included';
        return `${amount > 0 ? '+' : '−'}${this.money(Math.abs(amount))}${method?.charge_type === 'fixed_order' ? ' / order' : ' / piece'}`;
    },

    sizeMeta(key) {
        for (const group of (config.size_groups || [])) {
            const size = (group.sizes || []).find(item => `${group.id}:${item.code}` === key);
            if (size) return { key, groupId: group.id, groupLabel: group.label, sizeCode: size.code, sizeLabel: size.label };
        }
        return null;
    },

    blankRosterValues() {
        return Object.fromEntries((config.jersey_roster?.fields || [])
            .filter(field => field.enabled !== false)
            .map(field => [field.key, '']));
    },

    syncRosterRows() {
        if (!config.jersey_roster?.enabled || config.product_profile !== 'jersey') {
            this.rosterEnabled = false;
            this.rosterRows = [];
            return;
        }

        if (!config.jersey_roster?.optional) this.rosterEnabled = true;
        if (!this.rosterEnabled) {
            this.rosterRows = [];
            return;
        }

        const existingBySize = new Map();
        (this.rosterRows || []).forEach(row => {
            if (!existingBySize.has(row.size_key)) existingBySize.set(row.size_key, []);
            existingBySize.get(row.size_key).push(row);
        });

        const rows = [];
        (config.size_groups || []).forEach(group => (group.sizes || []).forEach(size => {
            const key = `${group.id}:${size.code}`;
            const count = Math.max(0, Number(this.quantities[key] || 0));
            const reusable = existingBySize.get(key) || [];
            for (let index = 0; index < count; index += 1) {
                const old = reusable[index];
                rows.push({
                    size_key: key,
                    size_group: group.id,
                    size_group_label: group.label,
                    size_code: size.code,
                    size_label: size.label,
                    values: old?.values ? { ...this.blankRosterValues(), ...old.values } : this.blankRosterValues(),
                });
            }
        }));
        this.rosterRows = rows;
    },

    toggleRoster(enabled) {
        this.rosterEnabled = Boolean(enabled);
        this.syncRosterRows();
        this.sync();
    },

    rosterSummary() {
        if (!this.rosterEnabled) return 'Not requested';
        return `${this.rosterRows.length} personalized jersey${this.rosterRows.length === 1 ? '' : 's'}`;
    },

    openSizeChart(groupId) {
        this.activeChartGroup = groupId;
        this.sizeChartOpen = true;
        document.documentElement.classList.add('overflow-hidden');
    },

    closeSizeChart() {
        this.sizeChartOpen = false;
        document.documentElement.classList.remove('overflow-hidden');
    },

    chartGroup() {
        return (config.size_groups || []).find(group => group.id === this.activeChartGroup) || null;
    },

    sync() {
        this.syncProductionSpeed();
        this.configurationJson = JSON.stringify({
            selections: this.selections,
            multi_selections: this.multiSelections,
            inputs: this.inputs,
            quantities: this.quantities,
            artwork_files: this.artworkFiles.map(file => ({ name: file.name, size: file.size })),
            production_speed: this.productionSpeed,
            shipping_method: this.shippingMethod,
            roster_enabled: this.rosterEnabled,
            roster: this.rosterRows,
        });
    },

    validate() {
        const minimum = Number(config.minimum_quantity || 1);
        const maximum = Number(config.maximum_quantity || 999);
        const total = this.totalQuantity();

        if (total < minimum) {
            window.alert(`Please select at least ${minimum} piece${minimum === 1 ? '' : 's'}.`);
            document.getElementById('size-quantity')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if (total > maximum) {
            window.alert(`The maximum quantity for this product is ${maximum}.`);
            document.getElementById('size-quantity')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        for (const group of (config.option_groups || [])) {
            if ((group.display_mode || 'customer') !== 'customer' || !group.required) continue;

            if (group.type === 'checkbox' && (this.multiSelections[group.id] || []).length < Math.max(1, Number(group.minimum_selections || 1))) {
                window.alert(`Please select ${group.label}.`);
                return false;
            }
            if (['image', 'swatch', 'buttons', 'select'].includes(group.type) && !this.selections[group.id]) {
                window.alert(`Please select ${group.label}.`);
                return false;
            }
            if (!['image', 'swatch', 'buttons', 'select', 'checkbox', 'file'].includes(group.type) && !this.inputs[group.id]) {
                window.alert(`Please complete ${group.label}.`);
                return false;
            }
        }

        if (config.artwork_upload?.enabled) {
            const maximumFiles = Math.max(1, Math.min(12, Number(config.artwork_upload?.max_files || 5)));
            if (config.artwork_upload?.required && this.artworkFiles.length === 0) {
                window.alert('Please upload the required custom artwork.');
                document.getElementById('artwork-upload')?.scrollIntoView({ behavior: 'smooth' });
                return false;
            }
            if (this.artworkFiles.length > maximumFiles) {
                window.alert(`You can upload a maximum of ${maximumFiles} artwork files.`);
                return false;
            }
        }

        if (this.currentProductionOptions().length && !this.productionSpeed) {
            window.alert('Please choose a production option.');
            document.getElementById('delivery-options')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if ((config.shipping_methods || []).length && !this.shippingMethod) {
            window.alert('Please choose a shipping method.');
            return false;
        }

        if (this.rosterEnabled) {
            if (this.rosterRows.length > 250) {
                window.alert('Per-jersey details are limited to 250 pieces per configured cart line.');
                return false;
            }

            const requiredFields = (config.jersey_roster?.fields || []).filter(field => field.enabled !== false && field.required);
            for (let rowIndex = 0; rowIndex < this.rosterRows.length; rowIndex += 1) {
                for (const field of requiredFields) {
                    if (!String(this.rosterRows[rowIndex]?.values?.[field.key] || '').trim()) {
                        window.alert(`Complete ${field.label} for jersey ${rowIndex + 1}.`);
                        document.getElementById('jersey-roster')?.scrollIntoView({ behavior: 'smooth' });
                        return false;
                    }
                }
            }
        }

        this.sync();
        return true;
    },
});
Alpine.start();
