import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

    productionTimeLabel(option) {
        const minimum = Math.max(0, Number(option?.minimum_days || 0));
        const maximum = Math.max(minimum, Number(option?.maximum_days ?? minimum));
        return minimum === maximum
            ? `${minimum} ${minimum === 1 ? 'working day' : 'working days'}`
            : `${minimum}-${maximum} working days`;
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
    document.addEventListener('DOMContentLoaded', () => { setupStorefrontMenus(); setupHomepageSliders(); setupHomepageFaqs(); }, { once: true });
} else {
    setupStorefrontMenus();
    setupHomepageSliders();
    setupHomepageFaqs();
}

Alpine.start();
