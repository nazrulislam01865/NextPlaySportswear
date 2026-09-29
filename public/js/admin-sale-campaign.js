window.adminSaleCampaignFormV2 = (initial = {}) => ({
    campaignName: String(initial.campaignName || ''),
    status: String(initial.status || 'draft'),
    discountType: String(initial.discountType || 'percentage'),
    discountValue: Number(initial.discountValue ?? 15),
    maximumDiscount: initial.maximumDiscount ?? '',
    startDate: String(initial.startDate || ''),
    startTime: String(initial.startTime || '00:00'),
    endDate: String(initial.endDate || ''),
    endTime: String(initial.endTime || '23:59'),
    timezone: String(initial.timezone || ''),
    repeatWeekdays: Boolean(initial.repeatWeekdays),
    allWeekdays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    weekdays: Array.isArray(initial.weekdays) ? [...initial.weekdays] : [],
    applyOptions: [
        { value: 'all', label: 'All products' },
        { value: 'parent_categories', label: 'Parent categories' },
        { value: 'product_categories', label: 'Product categories' },
        { value: 'subcategories', label: 'Subcategories' },
        { value: 'products', label: 'Specific products' },
    ],
    appliesTo: String(initial.appliesTo || 'all'),
    selectedTargets: Array.isArray(initial.selectedTargets) ? initial.selectedTargets.map(item => ({ ...item })) : [],
    excludedProducts: Array.isArray(initial.excludedProducts) ? initial.excludedProducts.map(item => ({ ...item })) : [],
    showSaleBadge: initial.showSaleBadge !== false,
    showSalePage: initial.showSalePage !== false,
    priority: Math.max(1, Number(initial.priority ?? 1)),
    optionsUrl: String(initial.optionsUrl || ''),
    targetSearch: '',
    targetResults: [],
    targetPickerOpen: false,
    targetLoading: false,
    targetRequestId: 0,
    excludeSearch: '',
    excludeResults: [],
    excludePickerOpen: false,
    excludeLoading: false,
    excludeRequestId: 0,
    previewIndex: 0,
    bannerPlacements: Array.isArray(initial.bannerPlacements) ? [...initial.bannerPlacements] : ['sale_top'],
    bannerDesktopPreviewUrl: String(initial.bannerDesktopPreviewUrl || ''),
    bannerDesktopFileName: String(initial.bannerDesktopFileName || ''),
    bannerMobilePreviewUrl: String(initial.bannerMobilePreviewUrl || ''),
    bannerMobileFileName: String(initial.bannerMobileFileName || ''),
    bannerHeading: String(initial.bannerHeading || ''),
    bannerAltText: String(initial.bannerAltText || ''),
    bannerCtaLabel: String(initial.bannerCtaLabel || 'Shop Sale'),
    bannerDestinationLink: String(initial.bannerDestinationLink || '/sale'),
    removeBannerImage: false,
    removeBannerMobileImage: false,

    toggleWeekday(day) {
        if (this.weekdays.includes(day)) {
            this.weekdays = this.weekdays.filter(value => value !== day);
            return;
        }

        this.weekdays.push(day);
        this.weekdays = this.allWeekdays.filter(value => this.weekdays.includes(value));
    },

    resetTargetSelection() {
        this.selectedTargets = [];
        this.targetSearch = '';
        this.targetResults = [];
        this.targetPickerOpen = false;
        this.previewIndex = 0;
    },

    async fetchOptions(type, query) {
        if (!this.optionsUrl) return [];

        const url = new URL(this.optionsUrl, window.location.href);
        url.searchParams.set('type', type);
        if (query) url.searchParams.set('q', query);

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Unable to load promotion options.');

        const payload = await response.json();
        return Array.isArray(payload.options) ? payload.options : [];
    },

    async searchTargets() {
        if (this.appliesTo === 'all') {
            this.targetPickerOpen = false;
            this.targetResults = [];
            return;
        }

        const requestId = ++this.targetRequestId;
        this.targetLoading = true;
        this.targetPickerOpen = true;

        try {
            const options = await this.fetchOptions(this.appliesTo, this.targetSearch.trim());
            if (requestId !== this.targetRequestId) return;
            this.targetResults = options.filter(option => !this.selectedTargets.some(item => Number(item.id) === Number(option.id)));
        } catch (error) {
            if (requestId !== this.targetRequestId) return;
            this.targetResults = [];
        } finally {
            if (requestId === this.targetRequestId) this.targetLoading = false;
        }
    },

    addTarget(option) {
        if (!option || this.selectedTargets.some(item => Number(item.id) === Number(option.id))) return;
        this.selectedTargets.push({ ...option });
        if (this.appliesTo === 'products') this.previewIndex = this.selectedTargets.length - 1;
        this.targetSearch = '';
        this.targetResults = [];
        this.targetPickerOpen = false;
    },

    removeTarget(id) {
        this.selectedTargets = this.selectedTargets.filter(item => Number(item.id) !== Number(id));
        this.clampPreviewIndex();
    },

    async searchExcludedProducts() {
        const requestId = ++this.excludeRequestId;
        this.excludeLoading = true;
        this.excludePickerOpen = true;

        try {
            const options = await this.fetchOptions('excluded_products', this.excludeSearch.trim());
            if (requestId !== this.excludeRequestId) return;
            this.excludeResults = options.filter(option => !this.excludedProducts.some(item => Number(item.id) === Number(option.id)));
        } catch (error) {
            if (requestId !== this.excludeRequestId) return;
            this.excludeResults = [];
        } finally {
            if (requestId === this.excludeRequestId) this.excludeLoading = false;
        }
    },

    closeExcludePicker() {
        this.excludeRequestId += 1;
        this.excludePickerOpen = false;
        this.excludeLoading = false;
        this.excludeResults = [];
    },

    handleExcludeBlur(event) {
        const nextFocusedElement = event ? event.relatedTarget : null;
        const pickerRoot = this.$refs && this.$refs.excludePickerRoot ? this.$refs.excludePickerRoot : null;

        if (nextFocusedElement && pickerRoot && pickerRoot.contains(nextFocusedElement)) {
            return;
        }

        this.closeExcludePicker();
    },

    addExcludedProduct(option) {
        if (!option || this.excludedProducts.some(item => Number(item.id) === Number(option.id))) return;
        this.excludedProducts.push({ ...option });
        this.excludeSearch = '';
        this.closeExcludePicker();
    },

    removeExcludedProduct(id) {
        this.excludedProducts = this.excludedProducts.filter(item => Number(item.id) !== Number(id));
    },

    toggleBannerPlacement(value, checked) {
        if (checked && !this.bannerPlacements.includes(value)) this.bannerPlacements.push(value);
        if (!checked) this.bannerPlacements = this.bannerPlacements.filter(item => item !== value);
    },

    handleBannerDesktopFile(file) {
        if (!file) return;
        if (this.bannerDesktopPreviewUrl && this.bannerDesktopPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(this.bannerDesktopPreviewUrl);
        }
        this.bannerDesktopPreviewUrl = URL.createObjectURL(file);
        this.bannerDesktopFileName = String(file.name || 'Campaign desktop banner');
        this.removeBannerImage = false;
    },

    clearBannerDesktopFile() {
        if (this.bannerDesktopPreviewUrl && this.bannerDesktopPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(this.bannerDesktopPreviewUrl);
        }
        this.bannerDesktopPreviewUrl = '';
        this.bannerDesktopFileName = '';
        this.removeBannerImage = Boolean(initial.bannerDesktopPreviewUrl);
        if (this.$refs.bannerDesktopInput) this.$refs.bannerDesktopInput.value = '';
    },

    handleBannerMobileFile(file) {
        if (!file) return;
        if (this.bannerMobilePreviewUrl && this.bannerMobilePreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(this.bannerMobilePreviewUrl);
        }
        this.bannerMobilePreviewUrl = URL.createObjectURL(file);
        this.bannerMobileFileName = String(file.name || 'Campaign mobile banner');
        this.removeBannerMobileImage = false;
    },

    clearBannerMobileFile() {
        if (this.bannerMobilePreviewUrl && this.bannerMobilePreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(this.bannerMobilePreviewUrl);
        }
        this.bannerMobilePreviewUrl = '';
        this.bannerMobileFileName = '';
        this.removeBannerMobileImage = Boolean(initial.bannerMobilePreviewUrl);
        if (this.$refs.bannerMobileInput) this.$refs.bannerMobileInput.value = '';
    },

    clampPreviewIndex() {
        const length = this.previewProducts.length;
        if (length === 0) {
            this.previewIndex = 0;
            return;
        }
        if (this.previewIndex >= length) this.previewIndex = length - 1;
        if (this.previewIndex < 0) this.previewIndex = 0;
    },

    previousPreviewProduct() {
        const length = this.previewProducts.length;
        if (length < 2) return;
        this.previewIndex = (this.previewIndex - 1 + length) % length;
    },

    nextPreviewProduct() {
        const length = this.previewProducts.length;
        if (length < 2) return;
        this.previewIndex = (this.previewIndex + 1) % length;
    },

    cleanNumber(value) {
        const number = Number(value || 0);
        if (!Number.isFinite(number)) return '0';
        return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    },

    money(value, product = null) {
        const number = Math.max(0, Number(value || 0));
        const currency = String(product?.currency || 'GBP').toUpperCase();

        try {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency',
                currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(number);
        } catch (error) {
            return `£${number.toFixed(2)}`;
        }
    },

    formatDate(date) {
        const value = String(date || '').trim();
        const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        if (isoMatch) {
            const [, year, month, day] = isoMatch;
            return `${Number(day)} ${months[Number(month) - 1] || month} ${year}`;
        }

        return value || '-';
    },

    get targetFieldLabel() {
        return {
            parent_categories: 'Select parent categories',
            product_categories: 'Select product categories',
            subcategories: 'Select subcategories',
            products: 'Select products',
        }[this.appliesTo] || 'Select items';
    },

    get targetSearchPlaceholder() {
        return {
            parent_categories: 'Search parent categories...',
            product_categories: 'Search product categories...',
            subcategories: 'Search subcategories...',
            products: 'Search products...',
        }[this.appliesTo] || 'Search...';
    },

    get targetNoun() {
        return {
            parent_categories: ['parent category', 'parent categories'],
            product_categories: ['product category', 'product categories'],
            subcategories: ['subcategory', 'subcategories'],
            products: ['product', 'products'],
        }[this.appliesTo] || ['item', 'items'];
    },

    get targetSelectionSummary() {
        const count = this.selectedTargets.length;
        const noun = count === 1 ? this.targetNoun[0] : this.targetNoun[1];
        return `${count} ${noun} selected`;
    },

    get appliesSummary() {
        if (this.appliesTo === 'all') return 'All products';
        const count = this.selectedTargets.length;
        const noun = count === 1 ? this.targetNoun[0] : this.targetNoun[1];
        return `${count} ${noun}`;
    },

    get previewProducts() {
        return this.appliesTo === 'products' ? this.selectedTargets : [];
    },

    get previewProduct() {
        const products = this.previewProducts;
        if (products.length === 0) return null;
        const index = Math.min(Math.max(0, this.previewIndex), products.length - 1);
        return products[index] || null;
    },

    get previewPrice() {
        return Math.max(0, Number(this.previewProduct?.price || 0));
    },

    get previewDiscount() {
        if (!this.previewProduct) return 0;
        const value = Math.max(0, Number(this.discountValue || 0));
        let discount = this.discountType === 'fixed' ? value : this.previewPrice * (value / 100);

        const cap = Number(this.maximumDiscount || 0);
        if (cap > 0) discount = Math.min(discount, cap);

        return Math.min(this.previewPrice, Math.max(0, discount));
    },

    get previewSalePrice() {
        return Math.max(0, this.previewPrice - this.previewDiscount);
    },

    get discountBadge() {
        return this.discountType === 'fixed'
            ? `${this.money(this.discountValue, this.previewProduct)} OFF`
            : `${this.cleanNumber(this.discountValue)}% OFF`;
    },

    get discountSummary() {
        const base = this.discountType === 'fixed'
            ? `${this.money(this.discountValue, this.previewProduct)} off`
            : `${this.cleanNumber(this.discountValue)}% off`;
        const cap = Number(this.maximumDiscount || 0);
        return `${base} (max discount: ${cap > 0 ? this.money(cap, this.previewProduct) : 'none'})`;
    },

    get statusLabel() {
        return this.status === 'live' ? 'Live' : 'Draft';
    },

    get scheduleSummary() {
        const start = `${this.formatDate(this.startDate)}, ${this.startTime || '--:--'}`;
        const end = `${this.formatDate(this.endDate)}, ${this.endTime || '--:--'}`;
        return `${start} - ${end}`;
    },

    get weekdaySummary() {
        if (!this.repeatWeekdays || this.weekdays.length === 0) return '';
        return `Repeats weekly on ${this.weekdays.join(', ')}`;
    },
});
