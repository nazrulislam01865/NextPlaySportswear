import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.showStorefrontToast = (payload = {}) => {
    window.dispatchEvent(new CustomEvent('storefront-toast', {
        detail: payload,
    }));
};

window.storefrontToastCenter = (initialToast = null) => ({
    toasts: [],
    sequence: 0,
    timers: new Map(),

    init() {
        if (initialToast) {
            this.$nextTick(() => this.show(initialToast));
        }
    },

    normalize(payload = {}) {
        const isString = typeof payload === 'string';
        const type = ['success', 'info', 'warning', 'error'].includes(payload?.type)
            ? payload.type
            : 'success';

        return {
            key: String(payload?.key || ''),
            type,
            title: String(isString ? 'Notice' : (payload?.title || (type === 'success' ? 'Added' : 'Notice'))),
            message: String(isString ? payload : (payload?.message || 'Your change has been saved.')),
            duration: Math.max(1400, Math.min(10000, Number(payload?.duration || 3200))),
        };
    },

    show(payload = {}) {
        const toast = this.normalize(payload);
        const matching = toast.key
            ? this.toasts.find(item => item.key === toast.key)
            : null;

        if (matching) {
            this.dismiss(matching.id, false);
        }

        const id = ++this.sequence;
        this.toasts.push({ ...toast, id });

        while (this.toasts.length > 3) {
            this.dismiss(this.toasts[0].id, false);
        }

        const timer = window.setTimeout(() => this.dismiss(id), toast.duration);
        this.timers.set(id, timer);
    },

    dismiss(id, animate = true) {
        const timer = this.timers.get(id);
        if (timer) window.clearTimeout(timer);
        this.timers.delete(id);

        const remove = () => {
            this.toasts = this.toasts.filter(item => item.id !== id);
        };

        if (animate) {
            window.setTimeout(remove, 20);
        } else {
            remove();
        }
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

    notifyCustomization(title, message, key, type = 'success') {
        window.showStorefrontToast?.({
            type,
            title,
            message,
            key,
            duration: 3000,
        });
    },

    choose(group, id) {
        if ((group.display_mode || 'customer') !== 'customer') return;

        const previous = this.selections[group.id];
        this.selections[group.id] = id;
        this.sync();

        if (id && previous !== id) {
            const value = this.optionValue(group, id);
            this.notifyCustomization(
                'Added',
                `${group.label}: ${value?.label || id} has been added to your product.`,
                `option:${group.id}`,
            );
        }
    },

    chooseSelect(group, id) {
        if ((group.display_mode || 'customer') !== 'customer') return;

        this.selections[group.id] = id;
        this.sync();

        if (id) {
            const value = this.optionValue(group, id);
            this.notifyCustomization(
                'Added',
                `${group.label}: ${value?.label || id} has been added to your product.`,
                `option:${group.id}`,
            );
        }
    },

    toggle(group, id) {
        if ((group.display_mode || 'customer') !== 'customer') return;
        const values = [...(this.multiSelections[group.id] || [])];
        const index = values.indexOf(id);
        const maximum = Math.max(1, Number(group.maximum_selections || (group.values || []).length || 1));
        const value = this.optionValue(group, id);

        if (index >= 0) {
            values.splice(index, 1);
            this.notifyCustomization(
                'Removed',
                `${group.label}: ${value?.label || id} has been removed from your product.`,
                `option:${group.id}:${id}`,
                'info',
            );
        } else if (values.length < maximum) {
            values.push(id);
            this.notifyCustomization(
                'Added',
                `${group.label}: ${value?.label || id} has been added to your product.`,
                `option:${group.id}:${id}`,
            );
        } else {
            this.notifyCustomization(
                'Selection limit',
                `You can select up to ${maximum} option${maximum === 1 ? '' : 's'} for ${group.label}.`,
                `option-limit:${group.id}`,
                'warning',
            );
        }

        this.multiSelections[group.id] = values;
        this.sync();
    },

    changeQuantity(key, amount) {
        const maximum = Number(config.maximum_quantity || 999);
        const previous = Number(this.quantities[key] || 0);
        const next = Math.max(0, Math.min(maximum, Number(amount || 0)));
        this.quantities[key] = next;
        this.syncProductionSpeed();
        this.syncRosterRows();
        this.sync();

        if (next !== previous) {
            const meta = this.sizeMeta(key);
            const sizeLabel = meta?.sizeLabel || key;
            const groupLabel = meta?.groupLabel ? `${meta.groupLabel} ` : '';

            if (next > 0) {
                this.notifyCustomization(
                    'Added',
                    `${groupLabel}${sizeLabel} quantity has been set to ${next}.`,
                    `quantity:${key}`,
                );
            } else {
                this.notifyCustomization(
                    'Removed',
                    `${groupLabel}${sizeLabel} has been removed from your product.`,
                    `quantity:${key}`,
                    'info',
                );
            }
        }
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
        const option = this.productionOptionsForQuantity().find(item => item.id === id);
        if (!option) return;

        const previous = this.productionSpeed;
        this.productionSpeed = id;
        this.sync();

        if (previous !== id) {
            this.notifyCustomization(
                'Added',
                `Production option: ${option.label} has been added to your product.`,
                'production-speed',
            );
        }
    },

    currentProductionSpeed() {
        const options = this.productionOptionsForQuantity();
        return options.find(option => option.id === this.productionSpeed) || options[0] || null;
    },

    productionTimeLabel(option) {
        const customTime = String(option?.production_time || '').trim();
        const minimum = Math.max(0, Number(option?.minimum_days || 0));
        const maximum = Math.max(minimum, Number(option?.maximum_days ?? minimum));
        if (customTime && minimum === 0 && maximum === 0) return customTime;
        if (minimum === 0 && maximum === 0) return 'To be confirmed';
        return minimum === maximum
            ? `${minimum} ${minimum === 1 ? 'working day' : 'working days'}`
            : `${minimum}-${maximum} working days`;
    },

    dayRangeLabel(minimum = 0, maximum = 0, unit = 'days') {
        const min = Math.max(0, Number(minimum || 0));
        const max = Math.max(min, Number(maximum ?? min));
        if (min === 0 && max === 0) return 'To be confirmed';
        const suffix = unit ? ` ${unit}` : '';
        return min === max ? `${min}${suffix}` : `${min}–${max}${suffix}`;
    },

    productionDaysOnlyLabel(option = null) {
        const selected = option || this.currentProductionSpeed();
        const customTime = String(selected?.production_time || '').trim();
        const minimum = Math.max(0, Number(selected?.minimum_days || 0));
        const maximum = Math.max(minimum, Number(selected?.maximum_days ?? minimum));
        if (customTime && minimum === 0 && maximum === 0) return customTime;
        return this.dayRangeLabel(minimum, maximum, 'working days');
    },

    shippingDaysOnlyLabel(method = null) {
        const selected = method || (config.shipping_methods || []).find(item => item.id === this.shippingMethod);
        return this.dayRangeLabel(selected?.minimum_days, selected?.maximum_days, 'working days');
    },

    totalDeliveryDaysLabel(method = null) {
        const speed = this.currentProductionSpeed();
        const shipping = method || (config.shipping_methods || []).find(item => item.id === this.shippingMethod);
        const productionMin = Math.max(0, Number(speed?.minimum_days || 0));
        const productionMax = Math.max(productionMin, Number(speed?.maximum_days ?? productionMin));
        const shippingMin = Math.max(0, Number(shipping?.minimum_days || 0));
        const shippingMax = Math.max(shippingMin, Number(shipping?.maximum_days ?? shippingMin));

        if ((productionMin + productionMax + shippingMin + shippingMax) === 0) {
            return 'To be confirmed';
        }

        return this.dayRangeLabel(productionMin + shippingMin, productionMax + shippingMax, 'days');
    },

    selectedFabricPriceTable() {
        for (const group of (config.option_groups || [])) {
            if (['image', 'swatch', 'buttons', 'select'].includes(group.type)) {
                const table = this.optionValue(group, this.selections[group.id])?.fabric_price_table;
                if (table && ((table.rows || []).length || (table.price_tiers || []).length)) return table;
            }

            if (group.type === 'checkbox') {
                for (const id of (this.multiSelections[group.id] || [])) {
                    const table = this.optionValue(group, id)?.fabric_price_table;
                    if (table && ((table.rows || []).length || (table.price_tiers || []).length)) return table;
                }
            }
        }

        return null;
    },

    activePriceTable() {
        const fabricTable = this.selectedFabricPriceTable();
        if (fabricTable) return fabricTable;
        return {
            ...(config.price_table || {}),
            price_tiers: config.price_table?.price_tiers || config.price_tiers || [],
        };
    },

    activePriceTiers() {
        return this.activePriceTable()?.price_tiers || config.price_tiers || [];
    },

    normalizePriceText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    },

    parseDisplayMoney(value) {
        const text = String(value ?? '').trim();
        if (!text) return null;
        if (/^(free|included)$/i.test(text)) return 0;
        const match = text.match(/-?\d[\d,]*(?:\.\d+)?/);
        return match ? Number(match[0].replace(/,/g, '')) : null;
    },

    parseQuantityRangeFromLabel(value) {
        let text = String(value ?? '')
            .replace(/[\u2013\u2014\u2212]/g, '-')
            .replace(/,/g, '')
            .replace(/\b(pcs?|pieces?|units?|qty|quantity|pairs?|sets?|items?|products?|garments?|shirts?|jerseys?|kits?)\b\.?/gi, '')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/^[\s:~-]+|[\s:~-]+$/g, '');

        if (!text) return { min: null, max: null };

        let match = text.match(/^(\d+)\s*(?:-|to)\s*(\d+)$/i);
        if (match) return { min: Number(match[1]), max: Number(match[2]) };

        match = text.match(/^(?:more\s+than|greater\s+than|over|above|>)\s*(\d+)$/i);
        if (match) return { min: Number(match[1]) + 1, max: null };

        match = text.match(/^(?:up\s*to|upto|max(?:imum)?|<=|=<|≤)\s*(\d+)$/i);
        if (match) return { min: 1, max: Number(match[1]) };

        match = text.match(/^(?:at\s+least|min(?:imum)?|>=|=>|≥)?\s*(\d+)\s*(?:\+|plus|and\s+(?:above|up)|or\s+more|or\s+above)?$/i);
        if (match) return { min: Number(match[1]), max: null };

        return { min: null, max: null };
    },

    priceTableRanges(table = this.activePriceTable()) {
        const rows = (table?.rows || []).filter(Array.isArray);
        const tiers = table?.price_tiers || [];
        const ranges = rows.map((row, index) => {
            const tier = tiers[index] || {};
            let min = tier.min ?? tier.minimum_quantity ?? null;
            let max = tier.max ?? tier.maximum_quantity ?? null;

            min = min === '' || min === null || min === undefined ? null : Number(min);
            max = max === '' || max === null || max === undefined ? null : Number(max);

            if (!Number.isFinite(min) || min < 1) {
                const parsed = this.parseQuantityRangeFromLabel(row?.[0]);
                min = parsed.min;
                max = parsed.max;
            }

            return { min, max };
        });

        ranges.forEach((range, index) => {
            if (!Number.isFinite(range.min)) return;
            const next = ranges.slice(index + 1).find(item => Number.isFinite(item.min) && item.min > range.min);
            if (next && (!Number.isFinite(range.max) || range.max <= range.min)) range.max = next.min - 1;
            if (!next && Number.isFinite(range.max) && range.max <= range.min) range.max = null;
        });

        return ranges;
    },

    priceTableRowIndexForQuantity(table = this.activePriceTable(), quantity = this.effectiveProductionQuantity()) {
        const ranges = this.priceTableRanges(table);
        const qty = Math.max(1, Number(quantity || 1));
        let fallback = -1;

        for (let index = 0; index < ranges.length; index += 1) {
            const range = ranges[index];
            if (!Number.isFinite(range.min)) continue;
            if (qty >= range.min && (!Number.isFinite(range.max) || qty <= range.max)) return index;
            if (qty >= range.min) fallback = index;
        }

        return fallback >= 0 ? fallback : (ranges.length ? 0 : -1);
    },

    shippingMethodCategory(method) {
        const text = this.normalizePriceText(`${method?.label || ''} ${method?.id || ''}`);
        if (/\b(remote|rural|outlying)\b/.test(text)) return 'remote';
        // Rush/event-review methods must not borrow urgent/express pricing unless
        // the price table has a dedicated Rush column. They should show Contact us.
        if (/\b(urgent|express|expedited|emergency|priority|fast|rapid)\b/.test(text)) return 'urgent';
        if (/\b(standard|normal|regular|economy|basic|default)\b/.test(text)) return 'standard';
        return null;
    },

    shippingHeaderMatchesCategory(header, category) {
        if (category === 'remote') return /\b(remote|rural|outlying)\b/.test(header);
        if (category === 'urgent') return /\b(urgent|express|expedited|emergency|priority|fast|rapid)\b/.test(header);
        if (category === 'standard') return /\b(standard|normal|regular|economy|basic|default)\b/.test(header);
        return false;
    },

    isShippingPriceHeader(header) {
        const text = this.normalizePriceText(header);
        if (!text) return false;
        if (/\b(total est|total cost|cost per|cost piece|price per|product price)\b/.test(text)) return false;
        return /\b(shipping|shipment|delivery|freight|surcharge)\b/.test(text);
    },

    shippingColumnIndex(method, table = this.activePriceTable()) {
        const headers = table?.headers || [];
        const category = this.shippingMethodCategory(method);
        const methodWords = this.normalizePriceText(`${method?.label || ''} ${method?.id || ''}`)
            .split(' ')
            .filter(word => word.length >= 3 && !['shipping', 'delivery', 'method', 'service', 'option', 'est', 'estimated', 'and', 'the'].includes(word));
        const columns = headers
            .map((header, index) => ({ index, header: this.normalizePriceText(header) }))
            .filter(item => item.index > 0 && this.isShippingPriceHeader(item.header));

        for (const column of columns) {
            const matchedWords = methodWords.filter(word => (` ${column.header} `).includes(` ${word} `));
            if (matchedWords.length && (matchedWords.length === methodWords.length || column.header.includes(' shipping') || column.header.includes(' shipment') || column.header.includes(' delivery'))) {
                return column.index;
            }
        }

        if (category) {
            const categorized = columns.find(column => this.shippingHeaderMatchesCategory(column.header, category));
            if (categorized) return categorized.index;

            if (category === 'standard') {
                const generic = columns.filter(column => !this.shippingHeaderMatchesCategory(column.header, 'urgent') && !this.shippingHeaderMatchesCategory(column.header, 'remote'));
                if (generic.length === 1) return generic[0].index;
            }
        }

        if (columns.length === 1 && category === 'standard') {
            const only = columns[0];
            if (!this.shippingHeaderMatchesCategory(only.header, 'urgent') && !this.shippingHeaderMatchesCategory(only.header, 'remote')) {
                return only.index;
            }
        }
        return null;
    },

    shippingUsesPriceTable(method) {
        return method?.price_source === 'price_table'
            || method?.charge_type === 'price_table'
            || method?.requires_price_table === true;
    },

    shippingTableRate(method) {
        if (!method) return null;

        const quantity = this.totalQuantity();
        if (quantity <= 0) return null;

        const table = this.activePriceTable();
        const column = this.shippingColumnIndex(method, table);
        const rowIndex = this.priceTableRowIndexForQuantity(table, quantity);
        const row = rowIndex >= 0 ? (table?.rows || [])[rowIndex] : null;
        if (column === null || !row) return null;
        return this.parseDisplayMoney(row[column]);
    },

    tierPrice() {
        const quantity = Math.max(this.totalQuantity(), Number(config.minimum_quantity || 1));
        const table = this.activePriceTable();
        const rowIndex = this.priceTableRowIndexForQuantity(table, quantity);
        const tier = rowIndex >= 0 ? (this.activePriceTiers()[rowIndex] || null) : null;

        if (tier && tier.unit !== null && tier.unit !== undefined && tier.unit !== '') {
            return Number(tier.unit || 0);
        }

        const fallbackTier = (config.price_tiers || []).find(item => quantity >= Number(item.min)
            && (item.max === null || item.max === '' || quantity <= Number(item.max)));
        return Number(fallbackTier?.unit ?? config.base_price ?? 0);
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
            const tableRate = this.shippingTableRate(shipping);
            if (tableRate !== null) {
                breakdown.perUnit += tableRate;
                breakdown.shippingPerUnit += tableRate;
            } else if (this.shippingUsesPriceTable(shipping)) {
                // No matching price-table column exists for this method. Master
                // shipping methods should not fall back to stale saved prices.
            } else if (shipping.charge_type === 'master_method') {
                const base = Number(shipping.base_price || shipping.price_delta || 0);
                const perItem = Number(shipping.per_item_price || 0);
                if (base || perItem) {
                    const fixedBase = Math.max(0, base - perItem);
                    breakdown.fixed += fixedBase;
                    breakdown.shippingFixed += fixedBase;
                    breakdown.perUnit += perItem;
                    breakdown.shippingPerUnit += perItem;
                }
            } else {
                const amount = Number(shipping.price_delta || 0);
                if (shipping.charge_type === 'fixed_order') {
                    breakdown.fixed += amount;
                    breakdown.shippingFixed += amount;
                } else if (!['included', 'price_table'].includes(shipping.charge_type)) {
                    breakdown.perUnit += amount;
                    breakdown.shippingPerUnit += amount;
                }
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

        if (this.artworkFiles.length > 0) {
            this.notifyCustomization(
                'Added',
                `${this.artworkFiles.length} artwork file${this.artworkFiles.length === 1 ? '' : 's'} has been added to your product.`,
                'artwork-files',
            );
        }
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

    chooseShippingMethod(id) {
        const method = (config.shipping_methods || []).find(item => item.id === id);
        if (!method) return;

        const previous = this.shippingMethod;
        this.shippingMethod = id;
        this.sync();

        if (previous !== id) {
            this.notifyCustomization(
                'Added',
                `Shipping method: ${method.label} has been added to your product.`,
                'shipping-method',
            );
        }
    },

    commitInput(group, value) {
        const normalized = String(value ?? '').trim();
        this.inputs[group.id] = value;
        this.sync();

        if (normalized !== '') {
            this.notifyCustomization(
                'Added',
                `${group.label} has been added to your product.`,
                `input:${group.id}`,
            );
        }
    },

    commitRosterField(rowIndex, field, value) {
        const normalized = String(value ?? '').trim();
        this.sync();

        if (normalized !== '') {
            this.notifyCustomization(
                'Added',
                `${field.label} has been added for jersey ${Number(rowIndex) + 1}.`,
                `roster:${rowIndex}:${field.key}`,
            );
        }
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
        if (this.shippingUsesPriceTable(method) && this.totalQuantity() <= 0) {
            return 'Select quantity first';
        }

        const tableRate = this.shippingTableRate(method);
        if (tableRate !== null) {
            if (!tableRate) return 'Included';
            return `${tableRate > 0 ? '+' : '−'}${this.money(Math.abs(tableRate))} / piece`;
        }

        if (this.shippingUsesPriceTable(method)) {
            return 'Contact us for price';
        }

        if (method?.charge_type === 'master_method') {
            const base = Number(method?.base_price || method?.price_delta || 0);
            const perItem = Number(method?.per_item_price || 0);
            if (!base && !perItem) return 'Included';
            const parts = [];
            if (base) parts.push(`${this.money(base)} base`);
            if (perItem) parts.push(`${this.money(perItem)} / extra`);
            return `+${parts.join(' + ')}`;
        }
        const amount = Number(method?.price_delta || 0);
        if (!amount || method?.charge_type === 'included') return 'Included';
        const application = method?.charge_application || '';
        const suffix = application === 'per_product'
            ? ' / product'
            : (application === 'per_item' ? ' / item' : (method?.charge_type === 'fixed_order' ? ' / order' : ' / piece'));
        return `${amount > 0 ? '+' : '−'}${this.money(Math.abs(amount))}${suffix}`;
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

        this.notifyCustomization(
            this.rosterEnabled ? 'Added' : 'Removed',
            this.rosterEnabled
                ? 'Individual jersey details have been added to your product.'
                : 'Individual jersey details have been removed from your product.',
            'jersey-roster',
            this.rosterEnabled ? 'success' : 'info',
        );
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


const setupHomepageSliders = () => {
    document.querySelectorAll('[data-storefront-slider]').forEach((slider) => {
        if (slider.dataset.initialized === 'true') return;
        slider.dataset.initialized = 'true';

        const slides = Array.from(slider.querySelectorAll('.promo-slide'));
        const dots = Array.from(slider.querySelectorAll('.promo-dot'));
        const prev = slider.querySelector('.promo-prev');
        const next = slider.querySelector('.promo-next');
        let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('active')));
        let timer = null;

        if (slides.length === 0) return;
        if (current < 0) current = 0;

        const showSlide = (index) => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('active', slideIndex === current);
                slide.setAttribute('aria-hidden', slideIndex === current ? 'false' : 'true');
            });
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('active', dotIndex === current);
                dot.setAttribute('aria-current', dotIndex === current ? 'true' : 'false');
            });
        };

        const stop = () => {
            if (timer) window.clearInterval(timer);
            timer = null;
        };

        const start = () => {
            stop();
            if (slides.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                timer = window.setInterval(() => showSlide(current + 1), 6000);
            }
        };

        next?.addEventListener('click', () => {
            showSlide(current + 1);
            start();
        });

        prev?.addEventListener('click', () => {
            showSlide(current - 1);
            start();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                start();
            });
        });

        slider.addEventListener('mouseenter', stop, { passive: true });
        slider.addEventListener('mouseleave', start, { passive: true });
        slider.addEventListener('focusin', stop);
        slider.addEventListener('focusout', start);

        showSlide(current);
        start();
    });
};


const setupHomepageFaqs = () => {
    document.querySelectorAll('[data-home-faq]').forEach((faq) => {
        if (faq.dataset.initialized === 'true') return;
        faq.dataset.initialized = 'true';

        faq.querySelectorAll('.faq-q').forEach((button) => {
            button.addEventListener('click', () => {
                const item = button.closest('.faq-item');
                if (!item) return;

                const wasOpen = item.classList.contains('open');
                faq.querySelectorAll('.faq-item').forEach((other) => {
                    other.classList.remove('open');
                    other.querySelector('.faq-q')?.setAttribute('aria-expanded', 'false');
                });

                if (!wasOpen) {
                    item.classList.add('open');
                    button.setAttribute('aria-expanded', 'true');
                }
            });

            button.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    const item = button.closest('.faq-item');
                    item?.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                    button.focus({ preventScroll: true });
                }
            });
        });
    });
};


const escapeHtml = (value = '') => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const setupStorefrontSearchSuggestions = () => {
    document.querySelectorAll('[data-storefront-search-suggest]').forEach((wrapper) => {
        if (wrapper.dataset.suggestionsReady === 'true') return;
        wrapper.dataset.suggestionsReady = 'true';

        const input = wrapper.querySelector('input[type="search"][name="q"]');
        const panel = wrapper.querySelector('[data-storefront-search-suggestions]');
        const endpoint = wrapper.dataset.suggestUrl;

        if (!input || !panel || !endpoint) return;

        let debounceTimer = null;
        let abortController = null;
        let activeIndex = -1;
        let suggestions = [];

        const close = () => {
            panel.classList.add('hidden');
            panel.innerHTML = '';
            activeIndex = -1;
            suggestions = [];
            input.setAttribute('aria-expanded', 'false');
        };

        const setActive = (index) => {
            activeIndex = index;
            panel.querySelectorAll('[data-suggestion-index]').forEach((item) => {
                const isActive = Number(item.dataset.suggestionIndex) === activeIndex;
                item.classList.toggle('bg-slate-50', isActive);
                item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        const render = (items, query) => {
            suggestions = Array.isArray(items) ? items : [];
            activeIndex = -1;

            if (!query.trim()) {
                close();
                return;
            }

            if (suggestions.length === 0) {
                panel.innerHTML = '<div class="px-4 py-3 text-sm font-bold text-slate-500">No matching products found.</div>';
                panel.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            panel.innerHTML = suggestions.map((item, index) => `
                <a
                    href="${escapeHtml(item.url || '#')}"
                    class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-slate-50"
                    data-suggestion-index="${index}"
                    role="option"
                    aria-selected="false"
                >
                    <img src="${escapeHtml(item.image || '')}" alt="" class="h-12 w-12 shrink-0 rounded-xl border border-slate-200 object-cover" loading="lazy">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-black text-brand-ink">${escapeHtml(item.title || 'Untitled product')}</span>
                        <span class="mt-0.5 block truncate text-xs font-bold text-slate-500">${escapeHtml([item.sku, item.category].filter(Boolean).join(' · '))}</span>
                    </span>
                    ${item.price ? `<span class="hidden shrink-0 text-xs font-black text-brand-red sm:inline">${escapeHtml(item.price)}</span>` : ''}
                </a>
            `).join('');

            panel.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        };

        const fetchSuggestions = () => {
            const query = input.value.trim();

            if (query === '') {
                close();
                return;
            }

            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(async () => {
                abortController?.abort();
                abortController = new AbortController();

                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('q', query);

                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                        signal: abortController.signal,
                    });

                    if (!response.ok) throw new Error('Suggestion request failed');

                    const payload = await response.json();
                    render(payload.data || [], query);
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    panel.innerHTML = '<div class="px-4 py-3 text-sm font-bold text-red-600">Suggestions could not load.</div>';
                    panel.classList.remove('hidden');
                    input.setAttribute('aria-expanded', 'true');
                }
            }, 220);
        };

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');

        input.addEventListener('input', fetchSuggestions);
        input.addEventListener('focus', () => {
            if (input.value.trim() === '') close();
        });
        input.addEventListener('keydown', (event) => {
            if (panel.classList.contains('hidden') || suggestions.length === 0) {
                if (event.key === 'Escape') close();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex < suggestions.length - 1 ? activeIndex + 1 : 0);
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex > 0 ? activeIndex - 1 : suggestions.length - 1);
            }

            if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                const selected = suggestions[activeIndex];
                if (selected?.url) window.location.assign(selected.url);
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        });

        panel.addEventListener('pointerdown', (event) => {
            const link = event.target.closest('a[href]');
            if (!link) return;
            event.preventDefault();
            window.location.assign(link.href);
        });

        document.addEventListener('pointerdown', (event) => {
            if (!wrapper.contains(event.target)) close();
        }, { passive: true });
    });
};

const syncShopMenuPosition = () => {
    const navRow = document.querySelector('.storefront-nav-row');
    if (!navRow) return;

    const bottom = Math.ceil(navRow.getBoundingClientRect().bottom);
    const top = Math.max(8, bottom - 1);
    document.documentElement.style.setProperty('--np-shop-menu-top', `${top}px`);
};

const closeOpenDesktopMenus = (except = null) => {
    document.querySelectorAll('.np-menu-item.is-open').forEach((item) => {
        if (item !== except) item.classList.remove('is-open');
    });
};

const setupStorefrontMenus = () => {
    syncShopMenuPosition();

    document.querySelectorAll('.np-menu-item').forEach((item) => {
        const trigger = item.querySelector(':scope > .np-menu-link');
        const panel = item.querySelector(':scope > .np-menu-panel');
        if (!trigger || !panel) return;

        trigger.setAttribute('aria-haspopup', 'true');
        trigger.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false');

        const setOpen = (open) => {
            if (open) {
                closeOpenDesktopMenus(item);
                syncShopMenuPosition();
                item.classList.add('is-open');
            } else {
                item.classList.remove('is-open');
            }
            trigger.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false');
        };

        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                setOpen(!item.classList.contains('is-open'));
                if (item.classList.contains('is-open')) {
                    panel.querySelector('a, button, input, select, textarea')?.focus({ preventScroll: true });
                }
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                trigger.focus({ preventScroll: true });
            }
        });

        panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                trigger.focus({ preventScroll: true });
            }
        });

        item.addEventListener('pointerenter', syncShopMenuPosition, { passive: true });
        item.addEventListener('focusin', syncShopMenuPosition);
        item.addEventListener('pointerleave', () => setOpen(false), { passive: true });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.np-menu-item')) closeOpenDesktopMenus();
    });

    window.addEventListener('load', syncShopMenuPosition, { passive: true });
    window.addEventListener('resize', syncShopMenuPosition, { passive: true });
    window.addEventListener('scroll', syncShopMenuPosition, { passive: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { setupStorefrontMenus(); setupHomepageSliders(); setupHomepageFaqs(); setupStorefrontSearchSuggestions(); }, { once: true });
} else {
    setupStorefrontMenus();
    setupHomepageSliders();
    setupHomepageFaqs();
    setupStorefrontSearchSuggestions();
}

Alpine.start();
