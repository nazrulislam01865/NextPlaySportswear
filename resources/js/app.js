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
    categoryId: String(initial.categoryId || ''),
    subcategoryId: String(initial.subcategoryId || ''),
    subcategories: initial.subcategories || [],
    features: initial.features?.length ? initial.features : [''],
    specifications: initial.specifications?.length ? initial.specifications : [{ name: '', value: '' }],
    imageUrls: initial.imageUrls?.length ? initial.imageUrls : [{ url: '', alt: '', is_primary: true }],
    priceTiers: initial.priceTiers?.length ? initial.priceTiers : [{ label: '1+', minimum_quantity: 1, maximum_quantity: '', unit_price: initial.basePrice || 0, compare_at_price: '', savings_label: '' }],
    priceHeaders: initial.priceHeaders?.length ? initial.priceHeaders : ['Quantity', 'Unit Price', 'Savings'],
    priceRows: initial.priceRows?.length ? initial.priceRows : [['1+', '$0.00', '—']],
    optionGroups: initial.optionGroups?.length ? initial.optionGroups : [],
    sizeGroups: initial.sizeGroups?.length ? initial.sizeGroups : [{ name: 'Adult Unisex', code: 'adult-unisex', sizes_text: 'XS, S, M, L, XL, 2XL, 3XL', is_active: true }],
    artworkMethods: initial.artworkMethods?.length ? initial.artworkMethods : [
        { name: 'Upload Design', code: 'upload-design', icon: '⇧', description: 'Upload logo or finished artwork now.', price_adjustment: 0, requires_upload: true, is_active: true },
        { name: 'Design Later', code: 'design-later', icon: '◷', description: 'Send artwork after checkout.', price_adjustment: 0, requires_upload: false, is_active: true },
        { name: 'Free Design Help', code: 'design-help', icon: '✦', description: 'Ask the art team to prepare a proof.', price_adjustment: 0, requires_upload: false, is_active: true },
        { name: 'Blank Product', code: 'blank-product', icon: '□', description: 'Order without decoration.', price_adjustment: 0, requires_upload: false, is_active: true },
    ],
    productionSpeeds: initial.productionSpeeds?.length ? initial.productionSpeeds : [
        { name: 'Standard Production', code: 'standard', description: 'Standard production schedule.', price_adjustment: 0, minimum_days: 14, maximum_days: 18, is_active: true },
    ],
    faqs: initial.faqs?.length ? initial.faqs : [{ question: '', answer: '', is_active: true }],

    init() {
        this.normalizePriceRows();
        this.optionGroups.forEach(group => (group.values || []).forEach(value => {
            value.client_key ||= this.clientKey();
            value.existing_id ||= '';
            value.image_url ||= '';
            value.image_preview ||= value.image_url || '';
            value.image_error = false;
            value.color_hex ||= '';
        }));
    },
    updateSlug() {
        if (!this.slugTouched) this.slug = slugify(this.productName);
    },
    touchSlug() {
        this.slugTouched = true;
        this.slug = slugify(this.slug);
    },
    visibleSubcategories() {
        return this.subcategories.filter(item => String(item.parent_id) === String(this.categoryId));
    },
    addFeature() { this.features.push(''); },
    addSpecification() { this.specifications.push({ name: '', value: '' }); },
    addImageUrl() { this.imageUrls.push({ url: '', alt: '', is_primary: false }); },
    setPrimaryImage(index) { this.imageUrls.forEach((item, itemIndex) => item.is_primary = itemIndex === index); },
    addPriceTier() { this.priceTiers.push({ label: '', minimum_quantity: '', maximum_quantity: '', unit_price: '', compare_at_price: '', savings_label: '' }); },
    addPriceHeader() { this.priceHeaders.push('New Column'); this.normalizePriceRows(); },
    removePriceHeader(index) { if (this.priceHeaders.length <= 1) return; this.priceHeaders.splice(index, 1); this.priceRows.forEach(row => row.splice(index, 1)); },
    addPriceRow() { this.priceRows.push(this.priceHeaders.map(() => '')); },
    normalizePriceRows() { this.priceRows = this.priceRows.map(row => this.priceHeaders.map((_, i) => row[i] ?? '')); },
    addOptionGroup(section = 'product') {
        this.optionGroups.push({
            name: '', code: '', section, type: 'select', description: '', placeholder: '',
            is_required: false, minimum_selections: '', maximum_selections: '', accepted_file_types: '',
            maximum_file_size_mb: 15, is_active: true,
            values: [{ existing_id: '', client_key: this.clientKey(), label: '', code: '', description: '', color_hex: '', image_url: '', image_preview: '', image_error: false, price_adjustment: 0, stock_quantity: '', is_default: true, is_active: true }],
        });
    },
    updateGroupCode(group) { if (!group.code) group.code = slugify(group.name); },
    addOptionValue(group) { group.values.push({ existing_id: '', client_key: this.clientKey(), label: '', code: '', description: '', color_hex: '', image_url: '', image_preview: '', image_error: false, price_adjustment: 0, stock_quantity: '', is_default: false, is_active: true }); },
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
    previewOptionImage(event, value) {
        const file = event.target.files?.[0];
        if (!file) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        if (!allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
            event.target.value = '';
            window.alert('Choose a JPG, PNG, WebP, or AVIF image no larger than 5 MB.');
            return;
        }
        if (String(value.image_preview || '').startsWith('blob:')) URL.revokeObjectURL(value.image_preview);
        value.image_error = false;
        value.image_preview = URL.createObjectURL(file);
    },
    setDefaultValue(group, valueIndex) { group.values.forEach((value, index) => value.is_default = index === valueIndex); },
    addSizeGroup() { this.sizeGroups.push({ name: '', code: '', sizes_text: '', is_active: true }); },
    addArtworkMethod() { this.artworkMethods.push({ name: '', code: '', icon: '✦', description: '', price_adjustment: 0, requires_upload: false, is_active: true }); },
    addProductionSpeed() { this.productionSpeeds.push({ name: '', code: '', description: '', price_adjustment: 0, minimum_days: 1, maximum_days: 1, is_active: true }); },
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
    artworkMethod: config.artwork_methods?.[0]?.id || null,
    productionSpeed: config.production_speeds?.[0]?.id || null,
    configurationJson: '{}',
    init() {
        (config.option_groups || []).forEach(group => {
            if (group.type === 'checkbox') {
                this.multiSelections[group.id] = [];
            } else if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                const defaultValue = group.values?.find(value => value.default) || group.values?.[0];
                this.selections[group.id] = defaultValue?.id || '';
            } else {
                this.inputs[group.id] = '';
            }
        });
        (config.size_groups || []).forEach(group => (group.sizes || []).forEach(size => this.quantities[`${group.id}:${size.code}`] = 0));
        this.sync();
    },
    money(value) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: config.currency || 'USD' }).format(Number(value || 0));
    },
    currentImage() {
        return config.gallery?.[this.galleryIndex] || config.gallery?.[0] || { url: '', alt: config.title || '' };
    },
    optionValue(group, id) {
        return (group.values || []).find(value => value.id === id);
    },
    choose(group, id) {
        this.selections[group.id] = id;
        this.sync();
    },
    toggle(group, id) {
        const values = this.multiSelections[group.id] || [];
        const index = values.indexOf(id);
        if (index >= 0) values.splice(index, 1); else values.push(id);
        this.multiSelections[group.id] = values;
        this.sync();
    },
    changeQuantity(key, amount) {
        this.quantities[key] = Math.max(0, Math.min(9999, Number(amount || 0)));
        this.sync();
    },
    totalQuantity() {
        return Object.values(this.quantities).reduce((sum, value) => sum + Number(value || 0), 0);
    },
    tierPrice() {
        const quantity = Math.max(this.totalQuantity(), Number(config.minimum_quantity || 1));
        const tier = (config.price_tiers || []).find(item => quantity >= Number(item.min) && (item.max === null || item.max === '' || quantity <= Number(item.max)));
        return Number(tier?.unit ?? config.base_price ?? 0);
    },
    optionSurcharge() {
        let total = 0;
        (config.option_groups || []).forEach(group => {
            if (group.type === 'checkbox') {
                (this.multiSelections[group.id] || []).forEach(id => total += Number(this.optionValue(group, id)?.price_delta || 0));
            } else if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                total += Number(this.optionValue(group, this.selections[group.id])?.price_delta || 0);
            }
        });
        const artwork = (config.artwork_methods || []).find(item => item.id === this.artworkMethod);
        const speed = (config.production_speeds || []).find(item => item.id === this.productionSpeed);
        return total + Number(artwork?.price_delta || 0) + Number(speed?.price_delta || 0);
    },
    unitPrice() {
        return Math.max(0, this.tierPrice() + this.optionSurcharge());
    },
    totalPrice() {
        return this.unitPrice() * this.totalQuantity();
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
            if (group.type === 'checkbox') {
                const labels = (this.multiSelections[group.id] || []).map(id => this.optionValue(group, id)?.label).filter(Boolean);
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
        return (config.artwork_methods || []).find(item => item.id === this.artworkMethod)?.label || 'Not selected';
    },
    speedLabel() {
        return (config.production_speeds || []).find(item => item.id === this.productionSpeed)?.label || 'Standard production';
    },
    sync() {
        this.configurationJson = JSON.stringify({
            selections: this.selections,
            multi_selections: this.multiSelections,
            inputs: this.inputs,
            quantities: this.quantities,
            artwork_method: this.artworkMethod,
            production_speed: this.productionSpeed,
        });
    },
    validate() {
        const minimum = Number(config.minimum_quantity || 1);
        if (this.totalQuantity() < minimum) {
            window.alert(`Please select at least ${minimum} piece${minimum === 1 ? '' : 's'}.`);
            document.getElementById('size-quantity')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }
        for (const group of (config.option_groups || [])) {
            if (!group.required) continue;
            if (group.type === 'checkbox' && !(this.multiSelections[group.id] || []).length) {
                window.alert(`Please select ${group.label}.`); return false;
            }
            if (['image', 'swatch', 'buttons', 'select'].includes(group.type) && !this.selections[group.id]) {
                window.alert(`Please select ${group.label}.`); return false;
            }
            if (!['image', 'swatch', 'buttons', 'select', 'checkbox'].includes(group.type) && !this.inputs[group.id]) {
                window.alert(`Please complete ${group.label}.`); return false;
            }
        }
        this.sync();
        return true;
    },
});

Alpine.start();
