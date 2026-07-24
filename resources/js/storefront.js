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

const DEFAULT_GUEST_WISHLIST_STORAGE_KEY = 'nextplay:guest-wishlist:v1';

const readStoredWishlist = (storageKey = DEFAULT_GUEST_WISHLIST_STORAGE_KEY) => {
    try {
        const parsed = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        return {};
    }
};

const writeStoredWishlist = (items, storageKey = DEFAULT_GUEST_WISHLIST_STORAGE_KEY) => {
    try {
        window.localStorage.setItem(storageKey, JSON.stringify(items));
        return true;
    } catch (error) {
        return false;
    }
};

const storedWishlistCount = (storageKey = DEFAULT_GUEST_WISHLIST_STORAGE_KEY) => Object.keys(readStoredWishlist(storageKey)).length;

const dispatchWishlistChanged = (detail = {}) => {
    window.dispatchEvent(new CustomEvent('nextplay:wishlist-changed', { detail }));
};

const createProductSocialActions = (socialConfig = {}) => ({
    socialConfig,
    wishlisted: Boolean(socialConfig.initial_wishlisted),
    productFavoritesCount: Math.max(0, Number(socialConfig.favorites_count || 0)),
    wishlistBusy: false,
    shareBusy: false,
    shareOpen: false,
    socialStatus: '',

    initProductSocial() {
        if (!this.socialConfig?.product_id && !this.socialConfig?.slug) return;

        if (!this.socialConfig.authenticated) {
            this.wishlisted = Boolean(this.readGuestWishlist()[this.socialProductKey()]);
        }
    },

    socialProductKey() {
        return String(this.socialConfig.product_id || this.socialConfig.slug || '');
    },

    guestWishlistStorageKey() {
        return String(this.socialConfig.guest_storage_key || DEFAULT_GUEST_WISHLIST_STORAGE_KEY);
    },

    readGuestWishlist() {
        return readStoredWishlist(this.guestWishlistStorageKey());
    },

    writeGuestWishlist(items) {
        return writeStoredWishlist(items, this.guestWishlistStorageKey());
    },

    wishlistLabel() {
        return this.wishlisted ? 'Remove from wishlist' : 'Add to wishlist';
    },

    wishlistMessage() {
        return this.wishlisted ? 'Added to your wishlist' : 'Removed from your wishlist';
    },

    announceSocial(message, type = 'success', title = 'Wishlist') {
        this.socialStatus = String(message || '');
        window.showStorefrontToast?.({
            type,
            title,
            message: this.socialStatus,
            key: `product-social:${this.socialProductKey()}:${title}`,
            duration: 3000,
        });
    },

    async toggleWishlist() {
        if (this.wishlistBusy || !this.socialProductKey()) return;

        const nextState = !this.wishlisted;
        this.wishlistBusy = true;

        try {
            if (this.socialConfig.authenticated) {
                const endpoint = String(this.socialConfig.wishlist_endpoint || '');
                if (!endpoint) throw new Error('Wishlist endpoint is unavailable.');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(endpoint, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ wishlisted: nextState }),
                });

                if (!response.ok) {
                    if ([401, 403, 419].includes(response.status)) {
                        throw new Error('Please sign in again to update your wishlist.');
                    }

                    throw new Error('Your wishlist could not be updated. Please try again.');
                }

                const payload = await response.json();
                this.wishlisted = Boolean(payload.wishlisted);
                this.productFavoritesCount = Math.max(0, Number(payload.favorites_count ?? this.productFavoritesCount));
                dispatchWishlistChanged({
                    authenticated: true,
                    count: Math.max(0, Number(payload.wishlist_count || 0)),
                    productId: this.socialProductKey(),
                    wishlisted: this.wishlisted,
                });
                this.announceSocial(payload.message || this.wishlistMessage());
                return;
            }

            const items = this.readGuestWishlist();
            const key = this.socialProductKey();

            if (nextState) {
                items[key] = {
                    product_id: this.socialConfig.product_id || null,
                    slug: String(this.socialConfig.slug || ''),
                    title: String(this.socialConfig.title || ''),
                    summary: String(this.socialConfig.summary || ''),
                    url: String(this.socialConfig.url || window.location.href),
                    image: String(this.socialConfig.image || ''),
                    price: Number(this.socialConfig.price || 0),
                    currency: String(this.socialConfig.currency || 'USD'),
                    saved_at: new Date().toISOString(),
                };
            } else {
                delete items[key];
            }

            if (!this.writeGuestWishlist(items)) {
                throw new Error('Browser storage is unavailable. Sign in to save this product.');
            }

            this.wishlisted = nextState;
            dispatchWishlistChanged({
                authenticated: false,
                count: Object.keys(items).length,
                productId: key,
                wishlisted: this.wishlisted,
                storageKey: this.guestWishlistStorageKey(),
            });
            this.announceSocial(this.wishlistMessage());
        } catch (error) {
            this.announceSocial(
                error instanceof Error ? error.message : 'Your wishlist could not be updated.',
                'error',
                'Wishlist unavailable',
            );
        } finally {
            this.wishlistBusy = false;
        }
    },

    sharePayload() {
        const title = String(this.socialConfig.title || document.title || 'NextPlay Sportswear product');
        const url = String(this.socialConfig.url || window.location.href);

        return {
            title,
            text: `Check out ${title} from NextPlay Sportswear.`,
            url,
        };
    },

    shareMenuId() {
        return `product-share-menu-${this.socialProductKey().replace(/[^a-zA-Z0-9_-]/g, '-') || 'item'}`;
    },

    async shareImageFile() {
        if (typeof navigator.canShare !== 'function' || typeof window.File !== 'function') return null;

        const image = String(this.socialConfig.image || '');
        if (!image) return null;

        try {
            const imageUrl = new URL(image, window.location.href);
            if (imageUrl.origin !== window.location.origin) return null;

            const response = await fetch(imageUrl.toString(), {
                credentials: 'same-origin',
                cache: 'force-cache',
            });

            if (!response.ok) return null;

            const blob = await response.blob();
            if (!blob.type.startsWith('image/') || blob.size > 10 * 1024 * 1024) return null;

            const extension = blob.type.split('/')[1]?.replace('jpeg', 'jpg').replace(/[^a-z0-9]/gi, '') || 'jpg';
            const filename = `${String(this.socialConfig.slug || 'nextplay-product').replace(/[^a-z0-9_-]/gi, '-')}.${extension}`;
            const file = new File([blob], filename, { type: blob.type });

            return navigator.canShare({ files: [file] }) ? file : null;
        } catch (error) {
            return null;
        }
    },

    async shareProduct() {
        if (this.shareBusy) return;

        this.shareBusy = true;
        const payload = this.sharePayload();

        try {
            if (typeof navigator.share === 'function') {
                const imageFile = await this.shareImageFile();
                const payloadWithImage = imageFile ? { ...payload, files: [imageFile] } : null;
                const nativePayload = payloadWithImage && navigator.canShare?.(payloadWithImage)
                    ? payloadWithImage
                    : payload;

                try {
                    await navigator.share(nativePayload);
                    this.shareOpen = false;
                    return;
                } catch (error) {
                    if (error?.name === 'AbortError') return;
                }
            }

            this.shareOpen = true;
        } finally {
            this.shareBusy = false;
        }
    },

    async copyProductLink() {
        const url = this.sharePayload().url;

        try {
            if (navigator.clipboard?.writeText && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                const input = document.createElement('textarea');
                input.value = url;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (!copied) throw new Error('Copy failed');
            }

            this.shareOpen = false;
            this.announceSocial('Product link copied', 'success', 'Share');
        } catch (error) {
            this.announceSocial('The product link could not be copied.', 'error', 'Share unavailable');
        }
    },

    shareThrough(channel) {
        const payload = this.sharePayload();
        const encodedUrl = encodeURIComponent(payload.url);
        const encodedTitle = encodeURIComponent(payload.title);
        const encodedText = encodeURIComponent(`${payload.text} ${payload.url}`);
        let destination = '';

        if (channel === 'whatsapp') {
            destination = `https://wa.me/?text=${encodedText}`;
        } else if (channel === 'facebook') {
            destination = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
        } else if (channel === 'x') {
            destination = `https://twitter.com/intent/tweet?text=${encodeURIComponent(payload.text)}&url=${encodedUrl}`;
        } else if (channel === 'email') {
            window.location.href = `mailto:?subject=${encodedTitle}&body=${encodedText}`;
            this.shareOpen = false;
            return;
        }

        if (destination) {
            const popup = window.open(destination, '_blank', 'noopener,noreferrer,width=760,height=680');
            if (popup) popup.opener = null;
        }

        this.shareOpen = false;
    },
});

const rosterExcludedProductProfiles = ['bag', 'headwear', 'drinkware', 'drinkwear', 'lanyard', 'lyniard', 'headband'];
const rosterSupportsProductProfile = (profile = '') => !rosterExcludedProductProfiles.includes(String(profile || '').trim().toLowerCase());

window.productBuilder = (config = {}) => ({
    ...createProductSocialActions(config.social || {}),
    config,
    initialized: false,
    galleryIndex: 0,
    selections: {},
    multiSelections: {},
    inputs: {},
    quantities: {},
    orderQuantity: Number(config.minimum_quantity || 1),
    activeSizeGroup: config.size_groups?.[0]?.id || null,
    artworkFiles: [],
    artworkSequence: 0,
    productionSpeed: null,
    shippingMethod: config.shipping_methods?.find(method => method.default)?.id || config.shipping_methods?.[0]?.id || null,
    rosterEnabled: Boolean(config.jersey_roster?.enabled && !config.jersey_roster?.optional),
    rosterRows: [],
    sizeChartOpen: false,
    activeChartGroup: null,
    configurationJson: '{}',

    init() {
        // Alpine automatically calls init() and this component is also used by
        // older cached templates that may still contain x-init="init()".
        // Guard against a second initialization resetting restored cart values.
        if (this.initialized) return;
        this.initialized = true;
        this.initProductSocial();

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

        this.restoreInitialState(config.initial_state || {});
    },

    restoreInitialState(initial = {}) {
        const hasInitialState = initial && typeof initial === 'object' && Object.keys(initial).length > 0;

        if (hasInitialState) {
            (config.option_groups || []).forEach(group => {
                const values = group.values || [];
                const allowedIds = values.map(value => String(value.id));

                if (group.type === 'checkbox' && (group.display_mode || 'customer') === 'customer') {
                    this.multiSelections[group.id] = Array.from(new Set((initial.multi_selections?.[group.id] || [])
                        .map(id => String(id))
                        .filter(id => allowedIds.includes(id))))
                        .slice(0, Math.max(1, Number(group.maximum_selections || allowedIds.length || 1)));
                    return;
                }

                if (['image', 'swatch', 'buttons', 'select'].includes(group.type) && (group.display_mode || 'customer') === 'customer') {
                    const selected = String(initial.selections?.[group.id] || '');
                    if (allowedIds.includes(selected)) this.selections[group.id] = selected;
                    return;
                }

                if (!['image', 'swatch', 'buttons', 'select', 'checkbox', 'file'].includes(group.type)
                    && (group.display_mode || 'customer') === 'customer'
                    && initial.inputs?.[group.id] !== undefined) {
                    this.inputs[group.id] = String(initial.inputs[group.id] ?? '');
                }
            });

            if ((config.size_groups || []).length) {
                Object.entries(initial.quantities || {}).forEach(([key, value]) => {
                    if (Object.prototype.hasOwnProperty.call(this.quantities, key)) {
                        this.quantities[key] = Math.max(0, Math.min(Number(config.maximum_quantity || 999), Number(value || 0)));
                    }
                });
            } else {
                const minimum = Number(config.minimum_quantity || 1);
                const maximum = Number(config.maximum_quantity || 999);
                this.orderQuantity = Math.max(minimum, Math.min(maximum, Number(initial.order_quantity || minimum)));
            }

            const requestedShipping = String(initial.shipping_method || '');
            if ((config.shipping_methods || []).some(method => String(method.id) === requestedShipping)) {
                this.shippingMethod = requestedShipping;
            }

            this.productionSpeed = initial.production_speed ? String(initial.production_speed) : null;
            this.rosterEnabled = Boolean(initial.roster_enabled);
            this.rosterRows = Array.isArray(initial.roster)
                ? initial.roster.map(row => ({
                    ...row,
                    values: { ...this.blankRosterValues(), ...(row?.values || {}) },
                }))
                : [];
            this.artworkFiles = Array.isArray(initial.artwork_files)
                ? initial.artwork_files
                    .filter(file => file && file.path)
                    .map(file => ({
                        key: String(file.key || `existing:${++this.artworkSequence}`),
                        path: String(file.path),
                        name: String(file.name || 'Artwork file'),
                        size: Math.max(0, Number(file.size || 0)),
                        sizeLabel: String(file.sizeLabel || this.formatFileSize(file.size)),
                        existing: true,
                        rawFile: null,
                        extension: String(file.extension || this.fileExtension(file.name)),
                        previewable: Boolean(file.previewable ?? this.isArtworkImage(file.name)),
                        url: String(file.url || ''),
                        previewUrl: null,
                    }))
                : [];
        }

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
        return (group.values || []).find(value => String(value.id) === String(id));
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

    setOrderQuantity(amount) {
        const minimum = Number(config.minimum_quantity || 1);
        const maximum = Number(config.maximum_quantity || 999);
        const previous = Number(this.orderQuantity || 0);
        const next = Math.max(minimum, Math.min(maximum, Number(amount || minimum)));
        this.orderQuantity = next;
        this.syncProductionSpeed();
        this.sync();

        if (next !== previous) {
            this.notifyCustomization(
                'Updated',
                `Order quantity has been set to ${next}.`,
                'order-quantity',
                'info',
            );
        }
    },

    totalQuantity() {
        if (!(config.size_groups || []).length) {
            return Math.max(0, Number(this.orderQuantity || 0));
        }

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
        if (!(config.size_groups || []).length) {
            const quantity = this.totalQuantity();
            return quantity > 0 ? `Quantity: ${quantity}` : '';
        }

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

    formatFileSize(size) {
        const bytes = Math.max(0, Number(size || 0));
        return bytes >= 1024 * 1024
            ? `${(bytes / (1024 * 1024)).toFixed(2)} MB`
            : `${Math.max(1, Math.round(bytes / 1024))} KB`;
    },

    fileExtension(name) {
        const parts = String(name || '').toLowerCase().split('.');
        return parts.length > 1 ? parts.pop() : '';
    },

    isArtworkImage(name) {
        return ['png', 'jpg', 'jpeg', 'webp', 'svg'].includes(this.fileExtension(name));
    },

    artworkExtension(file) {
        return String(file?.extension || this.fileExtension(file?.name) || 'FILE').toUpperCase();
    },

    artworkFileUrl(file) {
        return String(file?.previewUrl || file?.url || '');
    },

    artworkCanPreview(file) {
        return Boolean((file?.previewable || this.isArtworkImage(file?.name)) && this.artworkFileUrl(file));
    },

    artworkCanOpen(file) {
        const extension = String(file?.extension || this.fileExtension(file?.name));
        return Boolean(this.artworkFileUrl(file) && extension !== 'svg');
    },

    artworkLabel() {
        if (!config.artwork_upload?.enabled) return 'Not requested';
        if (!this.artworkFiles.length) return config.artwork_upload?.required ? 'Artwork required' : 'No artwork selected';
        return `${this.artworkFiles.length} artwork file${this.artworkFiles.length === 1 ? '' : 's'} selected`;
    },

    retainedArtworkJson() {
        return JSON.stringify(this.artworkFiles
            .filter(file => file.existing && file.path)
            .map(file => file.path));
    },

    handleArtworkFiles(event) {
        const files = Array.from(event.target.files || []);
        const retainedFiles = this.artworkFiles.filter(file => file.existing);
        const maximumFiles = Math.max(1, Math.min(12, Number(config.artwork_upload?.max_files || 5)));
        const maximumBytes = Math.max(1, Math.min(25, Number(config.artwork_upload?.max_file_size_mb || 15))) * 1024 * 1024;
        const allowed = (config.artwork_upload?.accepted_types || ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'])
            .map(type => String(type).toLowerCase().replace(/^\./, ''));

        const invalid = files.find(file => {
            const extension = String(file.name || '').split('.').pop().toLowerCase();
            return !allowed.includes(extension) || file.size > maximumBytes;
        });

        if (retainedFiles.length + files.length > maximumFiles || invalid) {
            event.target.value = '';
            this.artworkFiles = retainedFiles;
            window.alert(`Choose no more than ${maximumFiles} approved artwork files in total, each no larger than ${config.artwork_upload?.max_file_size_mb || 15} MB.`);
            this.sync();
            return;
        }

        const selectedFiles = files.map(file => {
            const name = String(file.name || '').slice(0, 255);
            const extension = this.fileExtension(name);
            const previewable = this.isArtworkImage(name);
            const previewUrl = typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function'
                ? URL.createObjectURL(file)
                : null;

            return {
                key: `new:${++this.artworkSequence}:${file.name}:${file.size}:${file.lastModified}`,
                path: null,
                name,
                size: Number(file.size || 0),
                sizeLabel: this.formatFileSize(file.size),
                existing: false,
                rawFile: file,
                extension,
                previewable,
                url: null,
                previewUrl,
            };
        });

        this.artworkFiles = [...retainedFiles, ...selectedFiles];
        this.sync();

        if (selectedFiles.length > 0) {
            this.notifyCustomization(
                'Added',
                `${selectedFiles.length} new artwork file${selectedFiles.length === 1 ? '' : 's'} has been selected.`,
                'artwork-files',
            );
        }
    },

    removeArtworkFile(index) {
        const target = this.artworkFiles[index];
        if (!target) return;

        this.artworkFiles.splice(index, 1);

        if (target.previewUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
            URL.revokeObjectURL(target.previewUrl);
        }

        if (!target.existing && this.$refs.artworkInput) {
            const remainingNewFiles = this.artworkFiles
                .filter(file => !file.existing && file.rawFile)
                .map(file => file.rawFile);

            if (typeof DataTransfer !== 'undefined') {
                const transfer = new DataTransfer();
                remainingNewFiles.forEach(file => {
                    const rawFile = window.Alpine?.raw ? window.Alpine.raw(file) : file;
                    transfer.items.add(rawFile);
                });
                this.$refs.artworkInput.files = transfer.files;
            } else {
                this.$refs.artworkInput.value = '';
                this.artworkFiles = this.artworkFiles.filter(file => file.existing);
            }
        }

        this.sync();
        this.notifyCustomization(
            'Removed',
            `${target.name || 'Artwork file'} has been removed from this cart configuration.`,
            `artwork-remove:${target.key || index}`,
            'info',
        );
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
                `${field.label} has been added for item ${Number(rowIndex) + 1}.`,
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
        if (!config.jersey_roster?.enabled || !rosterSupportsProductProfile(config.product_profile)) {
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
                ? 'Individual roster details have been added to your product.'
                : 'Individual roster details have been removed from your product.',
            'product-roster',
            this.rosterEnabled ? 'success' : 'info',
        );
    },

    rosterSummary() {
        if (!this.rosterEnabled) return 'Not requested';
        return `${this.rosterRows.length} personalized item${this.rosterRows.length === 1 ? '' : 's'}`;
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
            (document.getElementById('size-quantity') || document.getElementById('product-quantity'))?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if (total > maximum) {
            window.alert(`The maximum quantity for this product is ${maximum}.`);
            (document.getElementById('size-quantity') || document.getElementById('product-quantity'))?.scrollIntoView({ behavior: 'smooth' });
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
                window.alert('Per-item details are limited to 250 pieces per configured cart line.');
                return false;
            }

            const requiredFields = (config.jersey_roster?.fields || []).filter(field => field.enabled !== false && field.required);
            for (let rowIndex = 0; rowIndex < this.rosterRows.length; rowIndex += 1) {
                for (const field of requiredFields) {
                    if (!String(this.rosterRows[rowIndex]?.values?.[field.key] || '').trim()) {
                        window.alert(`Complete ${field.label} for item ${rowIndex + 1}.`);
                        document.getElementById('product-roster')?.scrollIntoView({ behavior: 'smooth' });
                        return false;
                    }
                }
            }
        }

        this.sync();
        return true;
    },
});



const setupHeroCarousels = () => {
    document.querySelectorAll('[data-hero-carousel]').forEach((carousel) => {
        if (carousel.dataset.initialized === 'true') return;
        carousel.dataset.initialized = 'true';

        const slides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(carousel.querySelectorAll('[data-hero-dot]'));
        const prev = carousel.querySelector('[data-hero-prev]');
        const next = carousel.querySelector('[data-hero-next]');
        const status = carousel.querySelector('[data-hero-status]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
        let timer = null;
        let pointerStartX = 0;
        let pointerStartY = 0;
        let pointerActive = false;

        if (slides.length === 0) return;
        if (current < 0) current = 0;

        const setStatus = () => {
            if (status) status.textContent = `Showing slide ${current + 1} of ${slides.length}`;
        };

        const showSlide = (index, shouldFocus = false) => {
            const total = slides.length;
            current = (index + total) % total;
            const nextPreview = (current + 1) % total;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === current;
                const isPreview = total > 1 && slideIndex === nextPreview;

                slide.classList.toggle('is-active', isActive);
                slide.classList.toggle('is-next-preview', isPreview && !isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === current;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            setStatus();

            if (shouldFocus) carousel.focus({ preventScroll: true });
        };

        const stop = () => {
            if (timer) window.clearInterval(timer);
            timer = null;
        };

        const start = () => {
            stop();
            if (slides.length > 1 && !reducedMotion.matches) {
                timer = window.setInterval(() => showSlide(current + 1), 6500);
            }
        };

        next?.addEventListener('click', () => {
            showSlide(current + 1, true);
            start();
        });

        prev?.addEventListener('click', () => {
            showSlide(current - 1, true);
            start();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index, true);
                start();
            });
        });

        carousel.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                showSlide(current + 1, true);
                start();
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showSlide(current - 1, true);
                start();
            }
        });

        carousel.addEventListener('pointerdown', (event) => {
            pointerActive = true;
            pointerStartX = event.clientX;
            pointerStartY = event.clientY;
            stop();
        }, { passive: true });

        carousel.addEventListener('pointerup', (event) => {
            if (!pointerActive) return;
            pointerActive = false;

            const diffX = event.clientX - pointerStartX;
            const diffY = event.clientY - pointerStartY;

            if (Math.abs(diffX) > 45 && Math.abs(diffX) > Math.abs(diffY)) {
                showSlide(diffX < 0 ? current + 1 : current - 1, true);
            }

            start();
        }, { passive: true });

        carousel.addEventListener('pointercancel', () => {
            pointerActive = false;
            start();
        }, { passive: true });

        carousel.addEventListener('mouseenter', stop, { passive: true });
        carousel.addEventListener('mouseleave', start, { passive: true });
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);
        reducedMotion.addEventListener?.('change', start);

        showSlide(current);
        start();
    });
};

const setupHeroCardCarousels = () => {
    document.querySelectorAll('[data-hero-card-carousel]').forEach((carousel) => {
        if (carousel.dataset.cardInitialized === 'true') return;

        const track = carousel.querySelector('[data-hero-card-track]');
        const realSlides = track ? Array.from(track.querySelectorAll('[data-hero-card-slide]')) : [];
        const dots = Array.from(carousel.querySelectorAll('[data-hero-card-dot]'));
        const prev = carousel.querySelector('[data-hero-card-prev]');
        const next = carousel.querySelector('[data-hero-card-next]');
        const status = carousel.querySelector('[data-hero-card-status]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const total = realSlides.length;

        if (!track || total === 0) return;

        carousel.dataset.cardInitialized = 'true';
        carousel.classList.toggle('has-wraparound', total > 1);

        // Hero images are few and above the fold. Preload them so the wrap-around
        // clone is never shown before its image has finished loading.
        realSlides.forEach((slide) => {
            const image = slide.querySelector('img');
            if (image) image.loading = 'eager';
        });

        if (total > 1) {
            const firstClone = realSlides[0].cloneNode(true);
            const lastClone = realSlides[total - 1].cloneNode(true);
            // The active first-slide clone still needs a following card to fill
            // the right-side preview area while the last-to-first animation is
            // running. Without this extra clone, the viewport background is
            // exposed as a pale/white strip until the track jumps back.
            const firstPreviewClone = (realSlides[1] || realSlides[0]).cloneNode(true);

            firstClone.dataset.heroClone = 'first';
            lastClone.dataset.heroClone = 'last';
            firstPreviewClone.dataset.heroClone = 'first-preview';

            [firstClone, lastClone, firstPreviewClone].forEach((clone) => {
                clone.setAttribute('aria-hidden', 'true');
                clone.removeAttribute('data-hero-card-slide');
                clone.querySelectorAll('img').forEach((image) => {
                    image.loading = 'eager';
                    image.fetchPriority = 'high';
                });
            });

            track.prepend(lastClone);
            track.append(firstClone, firstPreviewClone);
        }

        const trackSlides = () => Array.from(track.querySelectorAll('.hero-carousel__slide'));
        let current = Math.max(0, realSlides.findIndex((slide) => slide.classList.contains('is-active')));
        let position = total > 1 ? current + 1 : current;
        let timer = null;
        let transitionTimer = null;
        let isAnimating = false;
        let pointerStartX = 0;
        let pointerStartY = 0;
        let pointerActive = false;
        let resizeFrame = null;

        if (current < 0) current = 0;

        const gap = () => {
            const styles = window.getComputedStyle(track);
            return parseFloat(styles.columnGap || styles.gap || '0') || 0;
        };

        const step = () => {
            const first = trackSlides()[0];
            return first ? first.getBoundingClientRect().width + gap() : 0;
        };

        const setStatus = () => {
            if (status) status.textContent = `Showing slide ${current + 1} of ${total}`;
        };

        const updateDots = () => {
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === current;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        const updateClasses = () => {
            const slides = trackSlides();
            const nextPosition = total > 1 ? position + 1 : position;

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === position;
                const isPreview = total > 1 && slideIndex === nextPosition;
                const isClone = Boolean(slide.dataset.heroClone);

                slide.classList.toggle('is-active', isActive);
                slide.classList.toggle('is-next-preview', isPreview && !isActive);
                slide.setAttribute('aria-hidden', isActive && !isClone ? 'false' : 'true');
            });

            updateDots();
            setStatus();
        };

        const moveTrack = (animate = true) => {
            if (animate) track.classList.remove('is-jump-disabled');
            else track.classList.add('is-jump-disabled');

            const slides = trackSlides();
            const targetSlide = slides[position];
            const targetOffset = targetSlide ? targetSlide.offsetLeft : position * step();

            track.style.transform = `translate3d(${-targetOffset}px, 0, 0)`;

            if (!animate) {
                window.requestAnimationFrame(() => {
                    track.classList.remove('is-jump-disabled');
                });
            }
        };

        const finishTransition = () => {
            if (transitionTimer) window.clearTimeout(transitionTimer);
            transitionTimer = null;

            if (total > 1 && position === 0) {
                position = total;
                current = total - 1;
                updateClasses();
                moveTrack(false);
            } else if (total > 1 && position === total + 1) {
                position = 1;
                current = 0;
                updateClasses();
                moveTrack(false);
            }

            isAnimating = false;
        };

        const scheduleTransitionFallback = () => {
            if (transitionTimer) window.clearTimeout(transitionTimer);
            // transitionend can be skipped by browsers when a tab loses focus or
            // when users click quickly. This fallback always normalizes the clone.
            transitionTimer = window.setTimeout(finishTransition, 900);
        };

        const goTo = (targetIndex, animate = true) => {
            if (transitionTimer) window.clearTimeout(transitionTimer);
            transitionTimer = null;

            current = (targetIndex + total) % total;
            position = total > 1 ? current + 1 : current;
            isAnimating = animate;
            updateClasses();
            moveTrack(animate);

            if (animate) scheduleTransitionFallback();
            else isAnimating = false;
        };

        const move = (direction) => {
            if (total <= 1 || isAnimating) return;

            isAnimating = true;
            current = (current + direction + total) % total;
            position += direction;
            updateClasses();
            moveTrack(true);
            scheduleTransitionFallback();
        };

        const stop = () => {
            if (timer) window.clearInterval(timer);
            timer = null;
        };

        const start = () => {
            stop();
            if (total > 1 && !reducedMotion.matches) {
                timer = window.setInterval(() => move(1), 6200);
            }
        };

        track.addEventListener('transitionend', (event) => {
            if (event.target !== track || event.propertyName !== 'transform') return;
            finishTransition();
        });

        next?.addEventListener('click', () => {
            move(1);
            start();
        });

        prev?.addEventListener('click', () => {
            move(-1);
            start();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goTo(index, true);
                start();
            });
        });

        carousel.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                move(1);
                start();
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                move(-1);
                start();
            }
        });

        carousel.addEventListener('pointerdown', (event) => {
            pointerActive = true;
            pointerStartX = event.clientX;
            pointerStartY = event.clientY;
            stop();
        }, { passive: true });

        carousel.addEventListener('pointerup', (event) => {
            if (!pointerActive) return;
            pointerActive = false;

            const diffX = event.clientX - pointerStartX;
            const diffY = event.clientY - pointerStartY;

            if (Math.abs(diffX) > 45 && Math.abs(diffX) > Math.abs(diffY)) {
                move(diffX < 0 ? 1 : -1);
            }

            start();
        }, { passive: true });

        carousel.addEventListener('pointercancel', () => {
            pointerActive = false;
            start();
        }, { passive: true });

        carousel.addEventListener('mouseenter', stop, { passive: true });
        carousel.addEventListener('mouseleave', start, { passive: true });
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);
        reducedMotion.addEventListener?.('change', start);

        window.addEventListener('resize', () => {
            if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(() => moveTrack(false));
        }, { passive: true });

        goTo(current, false);
        start();
    });
};

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




const updateProductCardWishlistButton = (button, wishlisted, busy = false) => {
    const title = String(button.dataset.wishlistProductTitle || 'this product');
    const active = Boolean(wishlisted);

    button.dataset.wishlisted = active ? '1' : '0';
    button.classList.toggle('is-active', active);
    button.classList.toggle('is-busy', Boolean(busy));
    button.disabled = Boolean(busy);
    button.setAttribute('aria-busy', busy ? 'true' : 'false');
    button.setAttribute('aria-pressed', active ? 'true' : 'false');
    button.setAttribute('aria-label', active
        ? `Remove ${title} from wishlist`
        : `Add ${title} to wishlist`);
    button.setAttribute('title', active ? 'Remove from wishlist' : 'Add to wishlist');
};

const productCardWishlistPayload = (button) => ({
    product_id: Number(button.dataset.productFavorite || 0) || null,
    slug: String(button.dataset.wishlistProductSlug || ''),
    title: String(button.dataset.wishlistProductTitle || ''),
    summary: String(button.dataset.wishlistProductSummary || ''),
    url: String(button.dataset.wishlistProductUrl || window.location.href),
    image: String(button.dataset.wishlistProductImage || ''),
    price: Number(button.dataset.wishlistProductPrice || 0),
    currency: String(button.dataset.wishlistProductCurrency || 'USD'),
    saved_at: new Date().toISOString(),
});

const setupProductCardWishlists = () => {
    const buttons = Array.from(document.querySelectorAll('[data-product-favorite]'))
        .filter((button) => Number(button.dataset.productFavorite || 0) > 0);

    if (buttons.length === 0) return;

    const headerSource = document.querySelector('[data-wishlist-header-link]');
    const authenticated = headerSource?.dataset.wishlistAuthenticated === '1';
    const storageKey = headerSource?.dataset.wishlistStorageKey || DEFAULT_GUEST_WISHLIST_STORAGE_KEY;
    const statusEndpoint = String(headerSource?.dataset.wishlistStatusEndpoint || '');

    buttons.forEach((button) => {
        const productId = String(button.dataset.productFavorite || '');
        const storedItems = authenticated ? {} : readStoredWishlist(storageKey);
        const initialState = authenticated
            ? button.dataset.wishlisted === '1'
            : Boolean(storedItems[productId]);

        updateProductCardWishlistButton(
            button,
            initialState,
            authenticated && button.dataset.wishlistStatusLoaded !== '1',
        );

        if (button.dataset.productWishlistReady === 'true') return;
        button.dataset.productWishlistReady = 'true';

        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (button.disabled || button.dataset.wishlistBusy === '1') return;

            const currentState = button.dataset.wishlisted === '1';
            const nextState = !currentState;
            button.dataset.wishlistBusy = '1';
            updateProductCardWishlistButton(button, currentState, true);

            try {
                if (authenticated) {
                    const endpoint = String(button.dataset.wishlistEndpoint || '');
                    if (!endpoint) throw new Error('Wishlist endpoint is unavailable.');

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(endpoint, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ wishlisted: nextState }),
                    });

                    if (!response.ok) {
                        if ([401, 403, 419].includes(response.status)) {
                            throw new Error('Please sign in again to update your wishlist.');
                        }

                        throw new Error('Your wishlist could not be updated. Please try again.');
                    }

                    const payload = await response.json();
                    const finalState = Boolean(payload.wishlisted);
                    updateProductCardWishlistButton(button, finalState, false);
                    dispatchWishlistChanged({
                        authenticated: true,
                        count: Math.max(0, Number(payload.wishlist_count || 0)),
                        productId,
                        wishlisted: finalState,
                    });
                    window.showStorefrontToast?.({
                        type: 'success',
                        title: 'Wishlist',
                        message: payload.message || (finalState
                            ? 'Added to your wishlist'
                            : 'Removed from your wishlist'),
                        key: `product-card-wishlist:${productId}`,
                        duration: 3000,
                    });
                    return;
                }

                const items = readStoredWishlist(storageKey);

                if (nextState) {
                    items[productId] = productCardWishlistPayload(button);
                } else {
                    delete items[productId];
                }

                if (!writeStoredWishlist(items, storageKey)) {
                    throw new Error('Browser storage is unavailable. Sign in to save this product.');
                }

                updateProductCardWishlistButton(button, nextState, false);
                dispatchWishlistChanged({
                    authenticated: false,
                    count: Object.keys(items).length,
                    productId,
                    wishlisted: nextState,
                    storageKey,
                });
                window.showStorefrontToast?.({
                    type: 'success',
                    title: 'Wishlist',
                    message: nextState
                        ? 'Added to your wishlist'
                        : 'Removed from your wishlist',
                    key: `product-card-wishlist:${productId}`,
                    duration: 3000,
                });
            } catch (error) {
                updateProductCardWishlistButton(button, currentState, false);
                window.showStorefrontToast?.({
                    type: 'error',
                    title: 'Wishlist unavailable',
                    message: error instanceof Error
                        ? error.message
                        : 'Your wishlist could not be updated.',
                    key: `product-card-wishlist-error:${productId}`,
                    duration: 4200,
                });
            } finally {
                button.dataset.wishlistBusy = '0';
                button.disabled = false;
                button.classList.remove('is-busy');
                button.setAttribute('aria-busy', 'false');
            }
        });
    });

    if (document.documentElement.dataset.productCardWishlistSyncReady !== 'true') {
        document.documentElement.dataset.productCardWishlistSyncReady = 'true';

        window.addEventListener('nextplay:wishlist-changed', (event) => {
            const productId = String(event.detail?.productId || '');
            if (!productId) return;

            document.querySelectorAll('[data-product-favorite]').forEach((button) => {
                if (String(button.dataset.productFavorite || '') !== productId) return;
                updateProductCardWishlistButton(button, Boolean(event.detail?.wishlisted), false);
            });
        });

        window.addEventListener('storage', (event) => {
            if (authenticated || event.key !== storageKey) return;

            const items = readStoredWishlist(storageKey);
            document.querySelectorAll('[data-product-favorite]').forEach((button) => {
                const productId = String(button.dataset.productFavorite || '');
                updateProductCardWishlistButton(button, Boolean(items[productId]), false);
            });
        });
    }

    if (!authenticated || !statusEndpoint) return;

    const pendingButtons = buttons.filter((button) => button.dataset.wishlistStatusLoaded !== '1');
    const productIds = Array.from(new Set(
        pendingButtons
            .map((button) => Number(button.dataset.productFavorite || 0))
            .filter((id) => Number.isInteger(id) && id > 0),
    )).slice(0, 100);

    if (productIds.length === 0) return;

    const statusUrl = new URL(statusEndpoint, window.location.origin);
    productIds.forEach((productId) => statusUrl.searchParams.append('product_ids[]', String(productId)));

    fetch(statusUrl.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Wishlist status could not be loaded.');
            return response.json();
        })
        .then((payload) => {
            const wishlistedIds = new Set(
                Array.isArray(payload.product_ids)
                    ? payload.product_ids.map((id) => String(id))
                    : [],
            );

            pendingButtons.forEach((button) => {
                const productId = String(button.dataset.productFavorite || '');
                button.dataset.wishlistStatusLoaded = '1';
                updateProductCardWishlistButton(button, wishlistedIds.has(productId), false);
            });

            if (Number.isFinite(Number(payload.wishlist_count))) {
                dispatchWishlistChanged({
                    authenticated: true,
                    count: Math.max(0, Number(payload.wishlist_count || 0)),
                });
            }
        })
        .catch(() => {
            pendingButtons.forEach((button) => {
                button.dataset.wishlistStatusLoaded = '1';
                updateProductCardWishlistButton(
                    button,
                    button.dataset.wishlisted === '1',
                    false,
                );
            });
        });
};

const setupProductCardActivity = () => {
    const activityUrl = window.NextPlayProductActivityUrl || '';
    if (!activityUrl) return;

    const cards = Array.from(document.querySelectorAll('[data-product-card][data-product-id]'));
    const trackers = Array.from(document.querySelectorAll('[data-product-view-track][data-product-id]'));
    const detailRows = Array.from(document.querySelectorAll('[data-product-detail-activity][data-product-id]'));
    const ids = Array.from(new Set([...cards, ...trackers, ...detailRows]
        .map((element) => Number(element.dataset.productId || 0))
        .filter((id) => Number.isInteger(id) && id > 0)))
        .slice(0, 40);
    const viewedProductId = Number(trackers[0]?.dataset.productId || 0);

    if (ids.length === 0) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let running = false;

    const updateCards = (activities = {}) => {
        cards.forEach((card) => {
            const id = String(Number(card.dataset.productId || 0));
            const row = card.querySelector('[data-product-card-activity]');
            const label = row?.querySelector('[data-product-card-activity-label]');
            const activity = activities[id];

            if (!row || !label) return;

            if (activity?.label) {
                label.textContent = activity.label;
                row.hidden = false;
                row.classList.add('has-activity');
                row.classList.remove('is-live');
            } else if (!row.dataset.persistedActivity) {
                label.textContent = '';
                row.hidden = true;
                row.classList.remove('has-activity');
                row.classList.remove('is-live');
            }
        });
    };

    cards.forEach((card) => {
        const row = card.querySelector('[data-product-card-activity]');
        const label = row?.querySelector('[data-product-card-activity-label]');
        if (row && label && label.textContent.trim() !== '') {
            row.dataset.persistedActivity = 'true';
        }
    });

    detailRows.forEach((row) => {
        const label = row.querySelector('[data-product-detail-activity-label]');
        if (label && label.textContent.trim() !== '') {
            row.dataset.persistedActivity = 'true';
        }
    });

    const updateDetailRows = (activities = {}) => {
        detailRows.forEach((row) => {
            const id = String(Number(row.dataset.productId || 0));
            const label = row.querySelector('[data-product-detail-activity-label]');
            const activity = activities[id];

            if (!label) return;

            if (activity?.label) {
                label.textContent = activity.label;
                row.hidden = false;
                row.classList.add('has-activity');
                row.classList.remove('is-live');
            } else if (!row.dataset.persistedActivity) {
                label.textContent = '';
                row.hidden = true;
                row.classList.remove('has-activity');
                row.classList.remove('is-live');
            }
        });
    };

    const ping = async () => {
        if (running || document.visibilityState === 'hidden') return;
        running = true;

        try {
            const response = await fetch(activityUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    product_ids: ids,
                    viewed_product_id: Number.isInteger(viewedProductId) && viewedProductId > 0
                        ? viewedProductId
                        : null,
                }),
            });

            if (!response.ok) return;

            const payload = await response.json();
            updateCards(payload.activities || {});
            updateDetailRows(payload.activities || {});
        } catch (error) {
            // Visitor activity is optional and must never interrupt storefront browsing.
        } finally {
            running = false;
        }
    };

    ping();
    window.setInterval(ping, 60000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') ping();
    });
};

const setupGlobalWishlistHeader = () => {
    const badges = Array.from(document.querySelectorAll('[data-wishlist-count]'));
    const links = Array.from(document.querySelectorAll('[data-wishlist-header-link]'));
    if (badges.length === 0 && links.length === 0) return;

    const source = links[0];
    const authenticated = source?.dataset.wishlistAuthenticated === '1';
    const storageKey = source?.dataset.wishlistStorageKey || DEFAULT_GUEST_WISHLIST_STORAGE_KEY;
    const initialCount = Math.max(0, Number(source?.dataset.wishlistInitialCount || 0));

    const render = (count) => {
        const safeCount = Math.max(0, Number(count || 0));
        badges.forEach((badge) => {
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.setAttribute('aria-label', `${safeCount} wishlist item${safeCount === 1 ? '' : 's'}`);
        });
        links.forEach((link) => {
            link.setAttribute('aria-label', `Wishlist, ${safeCount} item${safeCount === 1 ? '' : 's'}`);
        });
    };

    render(authenticated ? initialCount : storedWishlistCount(storageKey));

    window.addEventListener('nextplay:wishlist-changed', (event) => {
        const count = Number(event.detail?.count);
        if (Number.isFinite(count)) render(count);
    });

    window.addEventListener('storage', (event) => {
        if (!authenticated && event.key === storageKey) {
            render(storedWishlistCount(storageKey));
        }
    });
};

const setupWishlistPage = () => {
    const page = document.querySelector('[data-wishlist-page]');
    if (!page || page.dataset.wishlistReady === 'true') return;
    page.dataset.wishlistReady = 'true';

    const authenticated = page.dataset.authenticated === '1';
    const storageKey = page.dataset.storageKey || DEFAULT_GUEST_WISHLIST_STORAGE_KEY;
    const endpoint = page.dataset.productsEndpoint || '';
    const itemsContainer = page.querySelector('[data-wishlist-items]');
    const emptyState = page.querySelector('[data-wishlist-empty]');
    const loadingState = page.querySelector('[data-wishlist-loading]');
    const countNode = page.querySelector('[data-wishlist-page-count]');
    const countLabel = page.querySelector('[data-wishlist-page-count-label]');
    const template = page.querySelector('template[data-wishlist-guest-template]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const renderCount = (count) => {
        const safeCount = Math.max(0, Number(count || 0));
        if (countNode) countNode.textContent = String(safeCount);
        if (countLabel) countLabel.textContent = safeCount === 1 ? 'item' : 'items';
        emptyState?.classList.toggle('hidden', safeCount > 0);
        dispatchWishlistChanged({ authenticated, count: safeCount, storageKey });
    };

    const money = (value, currency = 'USD') => {
        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: String(currency || 'USD'),
            }).format(Number(value || 0));
        } catch (error) {
            return `$${Number(value || 0).toFixed(2)}`;
        }
    };

    const showToast = (message, type = 'success') => {
        window.showStorefrontToast?.({
            type,
            title: type === 'success' ? 'Wishlist' : 'Wishlist unavailable',
            message,
            key: 'wishlist-page-action',
        });
    };

    const bindAuthenticatedRemove = (button) => {
        if (!button || button.dataset.wishlistRemoveReady === 'true') return;
        button.dataset.wishlistRemoveReady = 'true';

        button.addEventListener('click', async () => {
            const item = button.closest('[data-wishlist-item]');
            const endpointUrl = button.dataset.endpoint || '';
            if (!item || !endpointUrl || button.disabled) return;

            button.disabled = true;
            try {
                const response = await fetch(endpointUrl, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ wishlisted: false }),
                });

                if (!response.ok) throw new Error('This product could not be removed.');
                const payload = await response.json();
                item.remove();
                renderCount(Number(payload.wishlist_count || itemsContainer?.querySelectorAll('[data-wishlist-item]').length || 0));
                showToast(payload.message || 'Removed from your wishlist');
            } catch (error) {
                showToast(error instanceof Error ? error.message : 'This product could not be removed.', 'error');
            } finally {
                button.disabled = false;
            }
        });
    };

    if (authenticated) {
        page.querySelectorAll('[data-wishlist-remove]').forEach(bindAuthenticatedRemove);
        renderCount(itemsContainer?.querySelectorAll('[data-wishlist-item]').length || 0);
        return;
    }

    const renderGuestItems = async () => {
        loadingState?.classList.remove('hidden');
        const storedItems = readStoredWishlist(storageKey);
        const entries = Object.entries(storedItems)
            .sort(([, a], [, b]) => String(b?.saved_at || '').localeCompare(String(a?.saved_at || '')));
        let resolvedProducts = {};

        const productIds = entries
            .map(([, item]) => Number(item?.product_id || 0))
            .filter((id) => Number.isInteger(id) && id > 0);

        if (endpoint && productIds.length > 0) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ product_ids: [...new Set(productIds)] }),
                });
                if (response.ok) {
                    const payload = await response.json();
                    resolvedProducts = payload.products || {};
                }
            } catch (error) {
                resolvedProducts = {};
            }
        }

        if (itemsContainer) itemsContainer.replaceChildren();

        entries.forEach(([key, stored]) => {
            if (!template?.content?.firstElementChild || !itemsContainer) return;
            const resolved = resolvedProducts[String(stored?.product_id || '')] || {};
            const item = { ...stored, ...resolved };
            const card = template.content.firstElementChild.cloneNode(true);
            const url = String(item.url || '#');
            const title = String(item.title || 'Saved product');
            const image = String(item.image || window.NextPlayImagePlaceholder || '');
            const category = String(item.category || 'Saved product');
            const summary = String(item.summary || 'Open this product to review customization and ordering options.');

            card.dataset.productKey = key;
            card.querySelectorAll('[data-wishlist-product-link], [data-wishlist-product-title], [data-wishlist-view-product]').forEach((link) => {
                link.href = url;
            });
            const imageNode = card.querySelector('[data-wishlist-product-image]');
            if (imageNode) {
                imageNode.src = image;
                imageNode.alt = String(item.alt || title);
            }
            const titleNode = card.querySelector('[data-wishlist-product-title]');
            if (titleNode) titleNode.textContent = title;
            const categoryNode = card.querySelector('[data-wishlist-product-category]');
            if (categoryNode) categoryNode.textContent = category;
            const summaryNode = card.querySelector('[data-wishlist-product-summary]');
            if (summaryNode) summaryNode.textContent = summary;
            const priceNode = card.querySelector('[data-wishlist-product-price]');
            if (priceNode) priceNode.textContent = money(item.price, item.currency);

            const removeButton = card.querySelector('[data-wishlist-remove]');
            if (removeButton) {
                removeButton.setAttribute('aria-label', `Remove ${title} from wishlist`);
                removeButton.addEventListener('click', () => {
                    const items = readStoredWishlist(storageKey);
                    delete items[key];
                    if (!writeStoredWishlist(items, storageKey)) {
                        showToast('Browser storage is unavailable.', 'error');
                        return;
                    }
                    card.remove();
                    renderCount(Object.keys(items).length);
                    showToast('Removed from your wishlist');
                });
            }

            itemsContainer.appendChild(card);
        });

        loadingState?.classList.add('hidden');
        renderCount(entries.length);
    };

    renderGuestItems();
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) renderGuestItems();
    });
};

const setupProductDetailActions = () => {};

const trackHeaderInteraction = (eventName, payload = {}) => {
    const name = eventName || 'header_navigation_click';
    const data = {
        location: 'header',
        ...payload,
    };

    try {
        if (Array.isArray(window.dataLayer)) {
            window.dataLayer.push({ event: name, ...data });
        }
    } catch (error) {
        // Analytics must never block navigation.
    }

    try {
        if (typeof window.gtag === 'function') {
            window.gtag('event', name, data);
        }
    } catch (error) {
        // Analytics must never block navigation.
    }

    window.dispatchEvent(new CustomEvent('nextplay:header-analytics', {
        detail: { event: name, ...data },
    }));
};

const setupSingleSubmitForms = () => {
    document.querySelectorAll('form[data-single-submit]').forEach((form) => {
        if (form.dataset.singleSubmitReady === 'true') return;
        form.dataset.singleSubmitReady = 'true';

        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');

            window.requestAnimationFrame(() => {
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');

                    if (button instanceof HTMLButtonElement) {
                        button.dataset.originalText = button.textContent || '';
                        button.textContent = button.dataset.submittingLabel || 'Please wait…';
                    }
                });
            });
        });
    });
};

const setupHeaderAnalytics = () => {
    document.querySelectorAll('[data-header-analytics]').forEach((element) => {
        if (element.dataset.headerAnalyticsReady === 'true') return;
        element.dataset.headerAnalyticsReady = 'true';

        const eventName = element.dataset.headerAnalytics || 'header_navigation_click';
        const label = element.dataset.headerAnalyticsLabel || element.textContent?.trim() || '';

        if (element.matches('form')) {
            element.addEventListener('submit', () => {
                const input = element.querySelector('input[name="q"]');
                trackHeaderInteraction(eventName, {
                    label,
                    search_term: input?.value?.trim() || '',
                });
            });
            return;
        }

        element.addEventListener('click', () => {
            trackHeaderInteraction(eventName, {
                label,
                href: element.getAttribute('href') || '',
                text: element.textContent?.trim()?.replace(/\s+/g, ' ') || label,
            });
        });
    });
};

const setupProductCollectionSliders = () => {
    document.querySelectorAll('[data-product-slider]').forEach((slider) => {
        if (slider.dataset.productSliderReady === 'true') return;

        const track = slider.querySelector('[data-product-slider-track]');
        const previousButton = slider.querySelector('[data-product-slider-prev]');
        const nextButton = slider.querySelector('[data-product-slider-next]');

        if (!track || !previousButton || !nextButton) return;

        const items = Array.from(track.querySelectorAll('.home-product-slider-item'));
        if (items.length === 0) return;

        slider.dataset.productSliderReady = 'true';

        const tolerance = 3;

        const cardStep = () => {
            const firstItem = items[0];
            if (!firstItem) return Math.max(240, track.clientWidth * .85);

            const styles = window.getComputedStyle(track);
            const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;

            return firstItem.getBoundingClientRect().width + gap;
        };

        const updateControls = () => {
            const hasOverflow = track.scrollWidth > track.clientWidth + tolerance;

            previousButton.hidden = !hasOverflow;
            nextButton.hidden = !hasOverflow;

            if (!hasOverflow) {
                previousButton.disabled = true;
                nextButton.disabled = true;
                return;
            }

            const maximumScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            previousButton.disabled = track.scrollLeft <= tolerance;
            nextButton.disabled = track.scrollLeft >= maximumScroll - tolerance;
        };

        const scrollByCard = (direction) => {
            track.scrollBy({
                left: cardStep() * direction,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            });
        };

        previousButton.addEventListener('click', () => scrollByCard(-1));
        nextButton.addEventListener('click', () => scrollByCard(1));

        track.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                scrollByCard(-1);
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                scrollByCard(1);
            }
        });

        let updateFrame = null;
        track.addEventListener('scroll', () => {
            if (updateFrame) window.cancelAnimationFrame(updateFrame);
            updateFrame = window.requestAnimationFrame(updateControls);
        }, { passive: true });

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(updateControls);
            observer.observe(track);
            items.forEach(item => observer.observe(item));
        } else {
            window.addEventListener('resize', updateControls, { passive: true });
        }

        updateControls();
        window.requestAnimationFrame(updateControls);
    });
};


let liveProductSectionsRefreshing = false;
let liveProductSectionsTimer = null;
let liveProductSectionsChannel = null;

const refreshLiveProductSection = async (section) => {
    const endpoint = section.dataset.liveProductRefreshUrl || '';
    if (!endpoint) return;

    const url = new URL(endpoint, window.location.origin);
    const signature = section.dataset.liveProductSignature || '';

    if (signature) url.searchParams.set('signature', signature);
    url.searchParams.set('_', String(Date.now()));

    const response = await fetch(url.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) return;

    const payload = await response.json();
    if (!payload?.changed || !payload?.html) {
        if (payload?.signature) section.dataset.liveProductSignature = payload.signature;
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = String(payload.html).trim();
    const replacement = template.content.firstElementChild;

    if (!replacement?.matches('[data-live-product-section]')) return;

    section.replaceWith(replacement);
    setupProductCollectionSliders();
    setupProductCardWishlists();

    window.dispatchEvent(new CustomEvent('nextplay:latest-products-updated', {
        detail: {
            signature: payload.signature || '',
            checkedAt: payload.checked_at || '',
        },
    }));
};

const refreshLiveProductSections = async () => {
    if (liveProductSectionsRefreshing || document.visibilityState === 'hidden') return;

    const sections = Array.from(document.querySelectorAll('[data-live-product-section]'));
    if (sections.length === 0) return;

    liveProductSectionsRefreshing = true;

    try {
        await Promise.all(sections.map(async (section) => {
            try {
                await refreshLiveProductSection(section);
            } catch (error) {
                // Live homepage updates are an enhancement and must never block browsing.
            }
        }));
    } finally {
        liveProductSectionsRefreshing = false;
    }
};

const setupLiveProductSections = () => {
    const sections = Array.from(document.querySelectorAll('[data-live-product-section]'));
    if (sections.length === 0 || liveProductSectionsTimer !== null) return;

    const refreshInterval = Math.max(3000, Math.min(...sections.map((section) => {
        const value = Number(section.dataset.liveProductRefreshMs || 5000);
        return Number.isFinite(value) ? value : 5000;
    })));

    liveProductSectionsTimer = window.setInterval(refreshLiveProductSections, refreshInterval);

    window.addEventListener('focus', refreshLiveProductSections, { passive: true });
    window.addEventListener('pageshow', refreshLiveProductSections, { passive: true });
    window.addEventListener('storage', (event) => {
        if (event.key === 'nextplay:catalog-updated-at') refreshLiveProductSections();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshLiveProductSections();
    });

    if ('BroadcastChannel' in window) {
        liveProductSectionsChannel = new BroadcastChannel('nextplay:storefront-updates');
        liveProductSectionsChannel.addEventListener('message', (event) => {
            if (event.data?.type === 'catalog-updated') refreshLiveProductSections();
        });
    }
};

const bootStorefront = () => {
    setupStorefrontMenus();
    setupHeroCarousels();
    setupHeroCardCarousels();
    setupHomepageSliders();
    setupProductCollectionSliders();
    setupLiveProductSections();
    setupHomepageFaqs();
    setupStorefrontSearchSuggestions();
    setupHeaderAnalytics();
    setupSingleSubmitForms();
    setupGlobalWishlistHeader();
    setupWishlistPage();
    setupProductCardWishlists();
    setupProductCardActivity();
    setupProductDetailActions();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootStorefront, { once: true });
} else {
    bootStorefront();
}

Alpine.start();
