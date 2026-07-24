import './bootstrap';

import Alpine from 'alpinejs';
import { readSheet } from 'read-excel-file/browser';

window.Alpine = Alpine;

const slugify = (value = '') => String(value)
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

window.adminSizeOptionRows = (initial = []) => ({
    rows: Array.isArray(initial) && initial.length
        ? initial.map((row, index) => ({
            client_key: row.client_key || `${Date.now()}-${index}-${Math.random().toString(36).slice(2)}`,
            label: String(row.label || ''),
            code: String(row.code || ''),
        }))
        : [{ client_key: `${Date.now()}-${Math.random().toString(36).slice(2)}`, label: '', code: '' }],
    add() {
        this.rows.push({ client_key: `${Date.now()}-${Math.random().toString(36).slice(2)}`, label: '', code: '' });
    },
    remove(index) {
        if (this.rows.length > 1) this.rows.splice(index, 1);
    },
    normalize(row) {
        if (!row.code) row.code = slugify(row.label);
    },
});

window.adminProductForm = (initial = {}) => ({
    productName: initial.productName || '',
    shortDescription: initial.shortDescription || '',
    descriptionHtml: initial.descriptionHtml || '',
    slug: initial.slug || '',
    slugTouched: Boolean(initial.slug),
    productProfile: initial.productProfile || 'standard',
    productionMethodsEnabled: Boolean(initial.productionMethodsEnabled),
    shippingMethodsEnabled: Boolean(initial.shippingMethodsEnabled),
    jerseyRosterEnabled: Boolean(initial.jerseyRosterEnabled),
    jerseyRosterPanelOpen: initial.jerseyRosterPanelOpen !== false,
    jerseyRosterOptional: initial.jerseyRosterOptional !== false,
    categoryId: String(initial.primaryCategoryId || initial.categoryId || ''),
    categoryName: initial.primaryCategoryName || '',
    isFeatured: Boolean(initial.isFeatured),
    showInCategoryPage: initial.showInCategoryPage !== false,
    categoryOptions: Array.isArray(initial.categoryOptions) ? initial.categoryOptions : [],
    categorySearch: '',
    categoryDropdownOpen: false,
    subcategoryId: String(initial.subcategoryId || ''),
    subcategories: initial.subcategories || [],
    features: initial.features?.length ? initial.features : [''],
    imageUrls: initial.imageUrls?.length ? initial.imageUrls : [],
    newImagePreviews: [],
    productImageDragging: false,
    productImageDragDepth: 0,
    productImageUploadError: '',
    mediaLibraryOpen: false,
    mediaLibraryLoading: false,
    mediaLibraryUploadBusy: false,
    mediaLibraryDragging: false,
    mediaLibraryDragDepth: 0,
    mediaLibraryItems: [],
    mediaLibrarySelectedIds: [],
    mediaLibrarySearch: '',
    mediaLibraryPage: 1,
    mediaLibraryHasMore: false,
    mediaLibraryError: '',
    mediaLibraryUploadError: '',
    mediaLibraryLastScrollLoadAt: 0,
    mediaLibraryIndexUrl: initial.mediaLibraryIndexUrl || '/admin/media-library',
    mediaLibraryStoreUrl: initial.mediaLibraryStoreUrl || '/admin/media-library',
    primaryImageSource: '',
    priceHeaders: initial.priceHeaders?.length ? initial.priceHeaders : ['Unit Price ($)', 'Setup Fee ($)'],
    priceHighlightColumn: Number(initial.priceHighlightColumn || 1),
    priceImportBusy: false,
    priceImportStatus: '',
    priceImportError: '',
    priceImportMappingOpen: false,
    priceImportFileName: '',
    priceImportMatrix: [],
    priceImportHeaderRowIndex: 0,
    priceImportHeaders: [],
    priceImportPreviewRows: [],
    priceImportQuantityMode: 'range',
    priceImportQuantityColumn: '',
    priceImportMinColumn: '',
    priceImportMaxColumn: '',
    priceImportIncludedColumns: [],
    priceImportPrimaryPriceColumn: '',
    priceImportMappingError: '',
    priceImportTargetTable: null,
    priceImportTargetLabel: '',
    pricingMode: initial.pricingMode || 'standard',
    priceRows: initial.priceRows?.length ? initial.priceRows : [{ minimum_quantity: 1, maximum_quantity: '', cells: ['0.00', '0.00'] }],
    optionGroups: initial.optionGroups?.length ? initial.optionGroups : [],
    optionGroupErrors: initial.optionGroupErrors || {},
    jerseyCustomizationTypes: initial.jerseyCustomizationTypes || {},
    customizationTypeGroups: Array.isArray(initial.customizationTypeGroups) ? initial.customizationTypeGroups : [],
    jerseyCustomizationOptions: initial.jerseyCustomizationOptions || [],
    newFeatureDialogOpen: false,
    newFeatureType: '',
    newFeatureNameError: '',
    masterItemPickerOpen: false,
    masterItemPickerGroupIndex: null,
    sizeOptionGroups: initial.sizeOptionGroups || [],
    sizeGroups: initial.sizeGroups?.length ? initial.sizeGroups : [],
    sizeGroupPickerOpen: false,
    sizeGroupPickerSearch: '',
    sizeGroupPickerContext: '',
    artworkUploadEnabled: Boolean(initial.artworkUploadEnabled),
    artworkUploadRequired: Boolean(initial.artworkUploadRequired),
    artworkUploadTitle: initial.artworkUploadTitle || 'Upload Custom Artwork',
    artworkUploadDescription: initial.artworkUploadDescription || 'Upload one or more artwork files for the production team.',
    artworkUploadMaxFiles: Number(initial.artworkUploadMaxFiles || 5),
    artworkUploadMaxFileSizeMb: Number(initial.artworkUploadMaxFileSizeMb || 15),
    artworkUploadAcceptedTypes: initial.artworkUploadAcceptedTypes || 'pdf,svg,png,jpg,jpeg,webp',
    productionHeaders: initial.productionHeaders?.length ? initial.productionHeaders : ['Standard Production'],
    productionRows: initial.productionRows?.length ? initial.productionRows : [{ range: '1+', cells: [] }],
    shippingMethods: initial.shippingMethods?.length ? initial.shippingMethods : [],
    rosterFields: initial.rosterFields?.length ? initial.rosterFields : [
        { key: 'name', label: 'Player name', type: 'text', max_length: 60, required: false, enabled: true },
        { key: 'number', label: 'Player number', type: 'number', max_length: 4, required: false, enabled: true },
        { key: 'front', label: 'Front text / position', type: 'text', max_length: 80, required: false, enabled: false },
        { key: 'back', label: 'Back text / position', type: 'text', max_length: 80, required: false, enabled: false },
    ],
    faqs: initial.faqs?.length ? initial.faqs : [{ question: '', answer: '', is_active: true }],
    steps: [
        { id: 'header', label: 'Basics' },
        { id: 'pricing', label: 'Pricing' },
        { id: 'options', label: 'Options' },
        { id: 'artwork', label: 'Personalization' },
        { id: 'fulfillment', label: 'Fulfillment' },
        { id: 'description', label: 'Description' },
        { id: 'faq', label: 'FAQ' },
    ],
    activeStep: 'header',
    stepObserver: null,
    autoAdvanceReady: false,
    autoAdvanceTimer: null,
    autoAdvancedSteps: [],
    autoScrollingUntil: 0,
    checklistItems: [
        { key: 'title', label: 'Add product title' },
        { key: 'summary', label: 'Add product summary' },
        { key: 'category', label: 'Select a category' },
        { key: 'image', label: 'Add at least 1 image' },
        { key: 'pricing', label: 'Set pricing tiers' },
        { key: 'size', label: 'Add size options' },
        { key: 'features', label: '(Optional) Add features' },
        { key: 'production', label: 'Set production options' },
        { key: 'description', label: 'Write description' },
    ],

    init() {
        this.normalizePriceRows();
        this.normalizeProductionTable();
        this.optionGroups.forEach(group => {
            group.client_key ||= this.clientKey();
            group.jersey_customization_type ||= this.inferJerseyCustomizationType(group.name);
            if (group.jersey_customization_type && this.jerseyCustomizationTypes[group.jersey_customization_type]) {
                group.name = this.jerseyCustomizationTypes[group.jersey_customization_type];
            }
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
                value.jersey_customization_option_id ||= '';
                value.image_url ||= '';
                value.image_previews = Array.isArray(value.image_previews) ? value.image_previews : (value.image_preview ? [value.image_preview] : []);
                value.image_error = false;
                value.color_hex ||= '';
                value.charge_type ||= 'per_unit';
                value.is_default = this.booleanValue(value.is_default, false);
                value.is_active = this.booleanValue(value.is_active, true);
                value.clear_images = this.booleanValue(value.clear_images, false);
                const masterItem = this.masterItemById(value.jersey_customization_option_id);
                if (masterItem) this.hydrateValueFromMaster(value, masterItem);
                value.primary_image_url ||= value.image_previews?.[0] || value.image_url || '';
            });
        });
        this.sizeGroups.forEach(group => {
            group.client_key ||= this.clientKey();
            group.existing_id ||= '';
            group.size_option_group_id ||= '';
            const master = (this.sizeOptionGroups || []).find(item => Number(item.id) === Number(group.size_option_group_id || 0));
            if (master) {
                group.name = String(master.name || '');
                group.code = String(master.slug || '');
                group.audience_label = String(master.audience_label || '');
                group.description_html = String(master.description_html || '');
                group.sizes = Array.isArray(master.sizes) ? [...master.sizes] : [];
                group.chart_enabled = Boolean(master.chart_enabled);
                group.chart_html = String(master.chart_html || '');
                group.chart_title = String(master.chart_title || '');
                group.chart_note = String(master.chart_note || '');
                group.chart_columns = Array.isArray(master.chart_columns) ? [...master.chart_columns] : [];
                group.chart_rows = Array.isArray(master.chart_rows) ? master.chart_rows.map(row => [...row]) : [];
                group.chart_image_url = '';
                group.chart_image_preview = String(master.chart_image_preview || '');
            } else {
                group.sizes = Array.isArray(group.sizes) ? group.sizes : String(group.sizes_text || '').split(',').map(value => value.trim()).filter(Boolean);
                group.chart_enabled = Boolean(group.chart_enabled);
                group.chart_html ||= '';
                group.chart_title ||= `${group.name || 'Product'} Size Chart`;
                group.chart_note ||= '';
                group.chart_columns = Array.isArray(group.chart_columns) ? group.chart_columns : [];
                group.chart_rows = Array.isArray(group.chart_rows) ? group.chart_rows : [];
                group.chart_image_url ||= '';
                group.chart_image_preview ||= group.chart_image_url || '';
                group.description_html ||= '';
            }
        });
        this.shippingMethods.forEach(method => {
            method.charge_type ||= 'per_unit';
            method.is_default = Boolean(method.is_default);
            method.is_active = method.is_active !== false;
        });
        this.imageUrls = (this.imageUrls || []).map(image => ({
            client_key: image.client_key || this.clientKey(),
            existing_id: image.existing_id || '',
            media_library_image_id: image.media_library_image_id || '',
            url: String(image.url || ''),
            preview: String(image.preview || ''),
            name: String(image.name || image.alt || ''),
            is_primary: this.booleanValue(image.is_primary, false),
        }));
        const primaryUrlIndex = this.imageUrls.findIndex(image => image.is_primary);
        if (primaryUrlIndex >= 0) this.primaryImageSource = `url:${primaryUrlIndex}`;
        else if (this.imageUrls.some(image => image.url || image.preview)) this.primaryImageSource = `url:${this.imageUrls.findIndex(image => image.url || image.preview)}`;
        this.syncImagePrimaryFlags();
        this.$nextTick(() => {
            this.setupStepperObserver();
            this.autoAdvanceReady = true;
            this.handleProgressChange(false);
        });

        if (Object.keys(this.optionGroupErrors || {}).length > 0) {
            this.$nextTick(() => {
                document.querySelector('[data-option-group-error="true"]')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            });
        }
    },
    setupStepperObserver() {
        const sections = this.steps
            .map(step => document.getElementById(step.id))
            .filter(Boolean);

        if (!sections.length) return;

        const syncActiveFromScroll = () => {
            if (Date.now() < this.autoScrollingUntil) return;

            const offset = this.stepScrollOffset();
            let active = sections[0];
            sections.forEach(section => {
                if (section.getBoundingClientRect().top <= offset) active = section;
            });
            if (active?.id && this.activeStep !== active.id) {
                this.activeStep = active.id;
            }
            this.keepActiveStepVisible();
        };

        if ('IntersectionObserver' in window) {
            if (this.stepObserver) this.stepObserver.disconnect();
            this.stepObserver = new IntersectionObserver((entries) => {
                if (Date.now() < this.autoScrollingUntil) return;

                const visible = entries
                    .filter(entry => entry.isIntersecting)
                    .sort((a, b) => {
                        const aTop = Math.abs(a.boundingClientRect.top);
                        const bTop = Math.abs(b.boundingClientRect.top);
                        return aTop - bTop || b.intersectionRatio - a.intersectionRatio;
                    })[0];
                if (visible?.target?.id && this.activeStep !== visible.target.id) {
                    this.activeStep = visible.target.id;
                }
                this.keepActiveStepVisible();
            }, {
                root: null,
                rootMargin: '-15% 0px -65% 0px',
                threshold: [0.01, 0.12, 0.25, 0.5],
            });
            sections.forEach(section => this.stepObserver.observe(section));
        }

        window.addEventListener('scroll', syncActiveFromScroll, { passive: true });
        window.addEventListener('resize', syncActiveFromScroll, { passive: true });
        syncActiveFromScroll();
    },
    stepScrollOffset() {
        return window.innerWidth < 768 ? 118 : 145;
    },
    visualActiveStepId() {
        // Keep the highlighted step exactly where the admin is working.
        // Previously this moved the visual state to the next incomplete step as soon as
        // the current section became complete, which felt like the form was jumping.
        return this.activeStep;
    },
    goToStep(stepId, event = null) {
        if (event) event.preventDefault();
        clearTimeout(this.autoAdvanceTimer);
        this.activeStep = stepId;
        this.autoScrollingUntil = Date.now() + 900;
        this.keepActiveStepVisible(stepId);

        const target = document.getElementById(stepId);
        if (!target) return;

        const top = target.getBoundingClientRect().top + window.scrollY - this.stepScrollOffset();
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });

        window.setTimeout(() => {
            this.activeStep = stepId;
            this.keepActiveStepVisible(stepId);
        }, 650);
    },
    keepActiveStepVisible(stepId = null) {
        this.$nextTick(() => {
            const activeId = stepId || this.visualActiveStepId();
            const activeItem = document.querySelector(`.np-stepper__item[href="#${activeId}"]`);
            const stepper = activeItem?.closest('.np-stepper');

            if (!activeItem || !stepper) return;

            const itemLeft = activeItem.offsetLeft;
            const itemWidth = activeItem.offsetWidth;
            const targetLeft = itemLeft - (stepper.clientWidth / 2) + (itemWidth / 2);

            stepper.scrollTo({
                left: Math.max(0, targetLeft),
                behavior: 'smooth',
            });
        });
    },
    handleProgressChange(allowAutoAdvance = false) {
        if (!this.autoAdvanceReady) return;
        clearTimeout(this.autoAdvanceTimer);
        this.autoAdvanceTimer = setTimeout(() => {
            // Do not auto-scroll to the next section while the admin is typing.
            // The stepper should update progress only; navigation happens only when a
            // step tab is clicked manually.
            this.keepActiveStepVisible();
        }, 250);
    },
    advanceWhenCurrentStepComplete() {
        const currentStepId = this.activeStep;
        const currentIndex = this.steps.findIndex(step => step.id === currentStepId);
        if (currentIndex < 0 || currentIndex >= this.steps.length - 1) return;
        if (!this.isStepComplete(currentStepId)) return;
        if (this.autoAdvancedSteps.includes(currentStepId)) return;

        const nextStep = this.steps.slice(currentIndex + 1).find(step => !this.isStepComplete(step.id)) || this.steps[currentIndex + 1];
        if (!nextStep) return;

        this.autoAdvancedSteps.push(currentStepId);
        this.goToStep(nextStep.id);
    },
    isStepActive(stepId) {
        return this.visualActiveStepId() === stepId;
    },
    isStepComplete(stepId) {
        if (stepId === 'header') {
            return this.checklistDone('title')
                && this.checklistDone('summary')
                && this.checklistDone('category')
                && this.checklistDone('image');
        }
        if (stepId === 'pricing') return this.checklistDone('pricing');
        if (stepId === 'options') return this.checklistDone('size') || this.checklistDone('features') || this.jerseyRosterEnabled;
        if (stepId === 'artwork') {
            return this.artworkUploadEnabled
                && this.textFilled(this.artworkUploadTitle)
                && this.textFilled(this.artworkUploadAcceptedTypes)
                && Number(this.artworkUploadMaxFileSizeMb || 0) > 0;
        }
        if (stepId === 'fulfillment') return this.checklistDone('production');
        if (stepId === 'description') return this.checklistDone('description');
        return false;
    },
    textFilled(value) {
        return String(value || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim().length > 0;
    },
    hasProductImage() {
        return this.newImagePreviews.length > 0 || this.imageUrls.some(image => image.url || image.preview || image.existing_id);
    },
    hasValidPricing() {
        return this.priceRows.some(row => {
            const minimum = Number(row?.minimum_quantity);
            const hasMinimum = Number.isInteger(minimum) && minimum >= 1;
            const hasValue = (row?.cells || []).some(cell => {
                if (!this.textFilled(cell)) return false;
                const numeric = Number(String(cell).replace(/[$,\s]/g, ''));
                return Number.isFinite(numeric) && numeric > 0;
            });
            return hasMinimum && hasValue;
        });
    },
    hasProductionOptions() {
        return this.productionRows.some(row => (row.cells || []).some(cell => Boolean(cell.enabled)));
    },
    checklistDone(key) {
        if (key === 'title') return this.textFilled(this.productName);
        if (key === 'summary') return this.textFilled(this.shortDescription);
        if (key === 'category') return this.textFilled(this.categoryId);
        if (key === 'image') return this.hasProductImage();
        if (key === 'pricing') return this.hasValidPricing();
        if (key === 'size') return this.sizeGroups.length > 0;
        if (key === 'features') return this.optionGroups.length > 0;
        if (key === 'production') return this.hasProductionOptions();
        if (key === 'description') return this.textFilled(this.descriptionHtml);
        return false;
    },
    selectedCategoryName() {
        if (!this.categoryId) return '';
        const selected = this.categoryOptions.find(category => String(category.id) === String(this.categoryId));
        return selected?.label || this.categoryName || '';
    },
    filteredCategories() {
        const query = String(this.categorySearch || '').trim().toLowerCase();
        return this.categoryOptions
            .filter(category => {
                if (!query) return true;
                return [category.label, category.name, category.slug]
                    .filter(Boolean)
                    .some(value => String(value).toLowerCase().includes(query));
            })
            .slice(0, 80);
    },
    toggleCategoryDropdown() {
        this.categoryDropdownOpen = !this.categoryDropdownOpen;
        if (this.categoryDropdownOpen) {
            this.categorySearch = '';
            this.$nextTick(() => this.$refs.categorySearchInput?.focus());
        }
    },
    closeCategoryDropdown() {
        this.categoryDropdownOpen = false;
    },
    selectCategory(id, label = '') {
        this.categoryId = id === '' || id === null ? '' : String(id);
        this.categoryName = label || this.selectedCategoryName();
        this.closeCategoryDropdown();
        this.handleProgressChange();
    },
    optionGroupErrorMessages(index) {
        const messages = this.optionGroupErrors?.[String(index)] ?? this.optionGroupErrors?.[index] ?? [];
        return Array.isArray(messages) ? messages.filter(Boolean) : (messages ? [String(messages)] : []);
    },
    optionGroupHasError(index) {
        return this.optionGroupErrorMessages(index).length > 0;
    },
    booleanValue(value, fallback = false) {
        if (value === undefined || value === null || value === '') return fallback;
        return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
    },
    updateSlug() { if (!this.slugTouched) this.slug = slugify(this.productName); },
    touchSlug() { this.slugTouched = true; this.slug = slugify(this.slug); },
    visibleSubcategories() { return this.subcategories.filter(item => String(item.parent_id) === String(this.categoryId)); },
    addFeature() { this.features.push(''); },
    addImageUrl() {
        this.imageUrls.push({ client_key: this.clientKey(), existing_id: '', media_library_image_id: '', url: '', preview: '', name: '', is_primary: false });
    },
    setPrimaryImage(index) {
        this.primaryImageSource = `url:${index}`;
        this.syncImagePrimaryFlags();
    },
    setPrimaryUploadedImage(index) {
        this.primaryImageSource = `upload:${index}`;
        this.syncImagePrimaryFlags();
    },
    isNewImagePrimary(index) {
        return this.primaryImageSource === `upload:${index}`;
    },
    newImagePrimaryIndex() {
        if (!this.primaryImageSource.startsWith('upload:')) return '';
        const index = Number(this.primaryImageSource.split(':')[1]);
        return Number.isInteger(index) && index >= 0 && index < this.newImagePreviews.length ? index : '';
    },
    syncImagePrimaryFlags() {
        const urlIndex = this.primaryImageSource.startsWith('url:')
            ? Number(this.primaryImageSource.split(':')[1])
            : -1;
        this.imageUrls.forEach((item, itemIndex) => item.is_primary = itemIndex === urlIndex);
    },
    firstAvailableImageSource() {
        if (this.newImagePreviews.length) return 'upload:0';
        const urlIndex = this.imageUrls.findIndex(image => image.url || image.preview);
        if (urlIndex >= 0) return `url:${urlIndex}`;
        return '';
    },
    removeImageUrl(index) {
        this.imageUrls.splice(index, 1);
        if (this.primaryImageSource.startsWith('url:')) {
            const selectedIndex = Number(this.primaryImageSource.split(':')[1]);
            if (selectedIndex === index) this.primaryImageSource = this.firstAvailableImageSource();
            else if (selectedIndex > index) this.primaryImageSource = `url:${selectedIndex - 1}`;
        }
        this.syncImagePrimaryFlags();
    },
    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },
    isFileDrag(event) {
        return Array.from(event?.dataTransfer?.types || []).includes('Files');
    },
    preventFileDropNavigation(event) {
        if (this.isFileDrag(event)) {
            event.preventDefault();
        }
    },
    filesFromTransfer(dataTransfer) {
        const transfer = dataTransfer || {};
        const fromItems = Array.from(transfer.items || [])
            .filter(item => item.kind === 'file')
            .map(item => item.getAsFile())
            .filter(Boolean);

        return fromItems.length ? fromItems : Array.from(transfer.files || []);
    },
    imageFileExtension(file) {
        return String(file?.name || '').split('.').pop().toLowerCase();
    },
    isAllowedImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        const type = String(file?.type || '').toLowerCase();
        return allowedTypes.includes(type) || (type === '' && allowedExtensions.includes(this.imageFileExtension(file)));
    },
    chooseProductImages() {
        this.$refs.productImageInput?.click();
    },
    startProductImageDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.productImageDragDepth += 1;
        this.productImageDragging = true;
    },
    endProductImageDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.productImageDragDepth = Math.max(0, this.productImageDragDepth - 1);
        this.productImageDragging = this.productImageDragDepth > 0;
    },
    startMediaLibraryDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.mediaLibraryDragDepth += 1;
        this.mediaLibraryDragging = true;
    },
    endMediaLibraryDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.mediaLibraryDragDepth = Math.max(0, this.mediaLibraryDragDepth - 1);
        this.mediaLibraryDragging = this.mediaLibraryDragDepth > 0;
    },
    mediaLibraryUrl(page = 1) {
        const url = new URL(this.mediaLibraryIndexUrl, window.location.origin);
        url.searchParams.set('page', page);
        // Keep the product picker fast and readable. It should load one fixed-size page
        // first, then load more only when the admin scrolls or clicks Load more.
        url.searchParams.set('per_page', 12);
        if (String(this.mediaLibrarySearch || '').trim()) {
            url.searchParams.set('q', String(this.mediaLibrarySearch || '').trim());
        }
        return url.toString();
    },
    openMediaLibrary() {
        this.mediaLibraryOpen = true;
        this.mediaLibrarySelectedIds = [];

        if (!this.mediaLibraryItems.length && !this.mediaLibraryLoading) {
            this.loadMediaLibrary(false);
        }
    },
    closeMediaLibrary() {
        this.mediaLibraryOpen = false;
        this.mediaLibrarySelectedIds = [];
        this.mediaLibraryError = '';
        this.mediaLibraryUploadError = '';
    },
    async loadMediaLibrary(append = false, options = {}) {
        if (this.mediaLibraryLoading) return;
        if (append && !this.mediaLibraryHasMore) return;

        const page = append ? this.mediaLibraryPage + 1 : 1;
        const requestSearch = String(this.mediaLibrarySearch || '').trim();

        this.mediaLibraryLoading = true;
        this.mediaLibraryError = '';

        if (!append) {
            this.mediaLibraryPage = 1;
            this.mediaLibraryHasMore = false;
        }

        try {
            const response = await fetch(this.mediaLibraryUrl(page), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Image gallery could not be loaded.');

            const payload = await response.json();
            const items = (Array.isArray(payload.data) ? payload.data : []).filter(item => String(item?.url || '').trim() && !String(item.url || '').includes('product-placeholder.svg'));

            // Ignore stale search responses when the user typed a newer search term.
            if (requestSearch !== String(this.mediaLibrarySearch || '').trim()) {
                return;
            }

            let firstFreshId = null;

            if (append) {
                const knownIds = new Set(this.mediaLibraryItems.map(item => Number(item.id)));
                const freshItems = items.filter(item => !knownIds.has(Number(item.id)));
                firstFreshId = freshItems[0]?.id || null;
                this.mediaLibraryItems = [...this.mediaLibraryItems, ...freshItems];
            } else {
                this.mediaLibraryItems = items;
                this.$nextTick(() => {
                    const scroller = this.$refs.mediaLibraryScroller;
                    if (scroller) scroller.scrollTop = 0;
                });
            }

            this.mediaLibraryPage = Number(payload.meta?.current_page || page);
            this.mediaLibraryHasMore = Boolean(payload.meta?.has_more);

            if (append && options?.scrollToNew && firstFreshId) {
                this.$nextTick(() => {
                    const scroller = this.$refs.mediaLibraryScroller;
                    const nextCard = scroller?.querySelector(`[data-media-id="${firstFreshId}"]`);
                    if (nextCard) {
                        nextCard.scrollIntoView({ block: 'start', inline: 'nearest' });
                    }
                });
            }
        } catch (error) {
            this.mediaLibraryError = error instanceof Error ? error.message : 'Image gallery could not be loaded.';
        } finally {
            this.mediaLibraryLoading = false;
        }
    },
    handleMediaLibraryScroll(event) {
        const scroller = event?.target;
        if (!scroller || this.mediaLibraryLoading || !this.mediaLibraryHasMore || !this.mediaLibraryItems.length) return;

        const nearBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 180;
        const now = Date.now();
        if (nearBottom && now - this.mediaLibraryLastScrollLoadAt > 700) {
            this.mediaLibraryLastScrollLoadAt = now;
            this.loadMediaLibrary(true, { scrollToNew: false });
        }
    },
    checkMediaLibraryAutoLoad() {
        this.$nextTick(() => {
            const scroller = this.$refs.mediaLibraryScroller;
            if (!this.mediaLibraryOpen || !scroller || this.mediaLibraryLoading || !this.mediaLibraryHasMore || !this.mediaLibraryItems.length) return;

            const canScroll = scroller.scrollHeight > scroller.clientHeight + 24;
            if (!canScroll) this.loadMediaLibrary(true);
        });
    },
    async uploadMediaLibraryFiles(fileList) {
        const files = Array.from(fileList || []);
        if (!files.length) return;

        const invalid = files.find(file => !this.isAllowedImageFile(file) || file.size > 5 * 1024 * 1024);
        if (invalid || files.length > 20) {
            this.mediaLibraryUploadError = 'Choose up to 20 JPG, PNG, WebP, or AVIF images, each no larger than 5 MB.';
            return;
        }

        const formData = new FormData();
        files.forEach(file => formData.append('images[]', file));
        this.mediaLibraryUploadBusy = true;
        this.mediaLibraryUploadError = '';

        try {
            const response = await fetch(this.mediaLibraryStoreUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                credentials: 'same-origin',
                body: formData,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message || Object.values(payload.errors || {})?.flat?.()?.[0] || 'Images could not be uploaded to the gallery.';
                throw new Error(message);
            }

            const items = (Array.isArray(payload.data) ? payload.data : []).filter(item => String(item?.url || '').trim() && !String(item.url || '').includes('product-placeholder.svg'));
            this.mediaLibraryItems = [...items, ...this.mediaLibraryItems];
            items.forEach(item => {
                const imageId = Number(item.id);
                if (imageId && !this.mediaLibrarySelectedIds.includes(imageId)) this.mediaLibrarySelectedIds.push(imageId);
            });
        } catch (error) {
            this.mediaLibraryUploadError = error instanceof Error ? error.message : 'Images could not be uploaded to the gallery.';
        } finally {
            this.mediaLibraryUploadBusy = false;
            if (this.$refs.mediaLibraryUploadInput) this.$refs.mediaLibraryUploadInput.value = '';
        }
    },
    dropMediaLibraryImages(event) {
        event.preventDefault();
        event.stopPropagation();
        this.mediaLibraryDragDepth = 0;
        this.mediaLibraryDragging = false;
        this.uploadMediaLibraryFiles(this.filesFromTransfer(event.dataTransfer));
    },
    isMediaLibrarySelected(id) {
        return this.mediaLibrarySelectedIds.includes(Number(id));
    },
    toggleMediaLibrarySelection(id) {
        const imageId = Number(id);
        if (!imageId) return;

        if (this.mediaLibrarySelectedIds.includes(imageId)) {
            this.mediaLibrarySelectedIds = this.mediaLibrarySelectedIds.filter(item => item !== imageId);
        } else {
            this.mediaLibrarySelectedIds.push(imageId);
        }
    },
    addSelectedMediaLibraryImages() {
        const existingMediaIds = new Set(this.imageUrls.map(image => Number(image.media_library_image_id || 0)).filter(Boolean));
        const selectedImages = this.mediaLibraryItems.filter(image => this.mediaLibrarySelectedIds.includes(Number(image.id)));
        let added = 0;

        selectedImages.forEach(image => {
            const imageId = Number(image.id);
            if (!imageId || existingMediaIds.has(imageId)) return;

            this.imageUrls.push({
                client_key: this.clientKey(),
                existing_id: '',
                media_library_image_id: imageId,
                url: '',
                preview: String(image.url || ''),
                name: String(image.alt_text || image.name || 'Gallery image'),
                is_primary: false,
            });
            existingMediaIds.add(imageId);
            added += 1;
        });

        if (!this.primaryImageSource && added > 0) {
            this.primaryImageSource = `url:${this.imageUrls.length - added}`;
        }

        this.syncImagePrimaryFlags();
        this.handleProgressChange();
        this.closeMediaLibrary();
    },
    previewProductImages(event) {
        this.productImageUploadError = '';
        this.appendProductImages(event.target.files, event.target);
    },
    dropProductImages(event) {
        event.preventDefault();
        event.stopPropagation();
        this.productImageDragDepth = 0;
        this.productImageDragging = false;
        this.productImageUploadError = '';
        this.appendProductImages(this.filesFromTransfer(event.dataTransfer), this.$refs.productImageInput);
    },
    productImageSignature(file) {
        return [file?.name || '', file?.size || 0, file?.lastModified || 0].join(':');
    },
    appendProductImages(fileList, input = null) {
        const files = Array.from(fileList || []);
        if (!files.length) {
            this.syncProductImageInput();
            return;
        }

        const invalid = files.find(file => !this.isAllowedImageFile(file) || file.size > 5 * 1024 * 1024);
        const signatures = new Set(this.newImagePreviews.map(image => image.signature || this.productImageSignature(image.file)));
        const uniqueFiles = files.filter(file => {
            const signature = this.productImageSignature(file);
            if (signatures.has(signature)) return false;
            signatures.add(signature);
            return true;
        });

        if (invalid || this.newImagePreviews.length + uniqueFiles.length > 20) {
            this.syncProductImageInput();
            this.productImageUploadError = 'Choose up to 20 JPG, PNG, WebP, or AVIF images, each no larger than 5 MB.';
            window.alert(this.productImageUploadError);
            return;
        }

        const addedImages = uniqueFiles.map(file => ({
            client_key: this.clientKey(),
            file,
            signature: this.productImageSignature(file),
            name: String(file.name || 'Product image').slice(0, 255),
            url: URL.createObjectURL(file),
            sizeLabel: file.size >= 1024 * 1024
                ? `${(file.size / (1024 * 1024)).toFixed(2)} MB`
                : `${Math.max(1, Math.round(file.size / 1024))} KB`,
        }));

        this.newImagePreviews.push(...addedImages);

        if (!this.primaryImageSource && this.newImagePreviews.length) {
            this.primaryImageSource = 'upload:0';
        }

        this.syncProductImageInput(input);
        this.syncImagePrimaryFlags();
        this.handleProgressChange();
    },
    syncProductImageInput(input = null) {
        const target = input || this.$refs.productImageInput;
        if (!target) return false;
        if (typeof DataTransfer === 'undefined') {
            this.productImageUploadError = 'This browser cannot attach dropped files to the product form. Use the Upload from device button.';
            return false;
        }

        const transfer = new DataTransfer();
        this.newImagePreviews.forEach(image => {
            if (image.file) transfer.items.add(image.file);
        });
        target.files = transfer.files;
        return true;
    },
    removeProductImage(index) {
        const removed = this.newImagePreviews.splice(index, 1)[0];
        if (removed?.url) URL.revokeObjectURL(removed.url);

        if (this.primaryImageSource.startsWith('upload:')) {
            const selectedIndex = Number(this.primaryImageSource.split(':')[1]);
            if (selectedIndex === index) this.primaryImageSource = this.firstAvailableImageSource();
            else if (selectedIndex > index) this.primaryImageSource = `upload:${selectedIndex - 1}`;
        }

        this.syncProductImageInput();
        this.syncImagePrimaryFlags();
        this.handleProgressChange(false);
    },
    normalizeSpreadsheetHeader(value) {
        return String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    },
    parseQuantityRange(value) {
        const text = String(value || '').replace(/,/g, '').trim();
        const numbers = [...text.matchAll(/\d+/g)].map(match => Number(match[0]));
        if (!numbers.length || numbers[0] < 1) throw new Error(`Invalid quantity range: ${text || 'blank'}.`);
        const openEnded = /\+|and\s+up|or\s+more|above|over|no\s+limit|unlimited/i.test(text);
        return {
            minimum_quantity: numbers[0],
            // A single value is a quantity breakpoint, not a one-piece range.
            // Its maximum is generated after all rows are sorted by using the
            // next row's minimum quantity minus one.
            maximum_quantity: openEnded ? '' : (numbers[1] ?? ''),
        };
    },
    spreadsheetInteger(value, { allowBlank = false } = {}) {
        const text = String(value ?? '').replace(/,/g, '').trim();
        if (text === '' && allowBlank) return '';
        if (/\+|and\s+up|or\s+more|above|over|no\s+limit|unlimited/i.test(text) && allowBlank) return '';
        const match = text.match(/\d+/);
        if (!match) return null;
        const parsed = Number(match[0]);
        return Number.isInteger(parsed) && parsed >= 1 ? parsed : null;
    },
    spreadsheetRows(matrix) {
        return (matrix || [])
            .map(row => Array.isArray(row) ? row.map(cell => String(cell ?? '').trim()) : [])
            .filter(row => row.some(cell => cell !== ''));
    },
    detectSpreadsheetHeaderRow(rows) {
        const limit = Math.min(rows.length - 1, 20);
        let bestIndex = 0;
        let bestScore = -Infinity;

        for (let index = 0; index < limit; index += 1) {
            const row = rows[index] || [];
            const next = rows[index + 1] || [];
            const nonEmpty = row.filter(cell => cell !== '').length;
            if (nonEmpty < 2) continue;
            const textCells = row.filter(cell => /[a-z]/i.test(cell)).length;
            const numericCells = row.filter(cell => /^[$€£¥]?\s*[\d,.%+-]+$/.test(cell)).length;
            const nextNonEmpty = next.filter(cell => cell !== '').length;
            const score = (nonEmpty * 3) + (textCells * 2) - numericCells + (nextNonEmpty >= 2 ? 3 : 0);
            if (score > bestScore) {
                bestScore = score;
                bestIndex = index;
            }
        }

        return bestIndex;
    },
    uniqueSpreadsheetHeaders(rawHeaders, columnCount) {
        const seen = new Map();
        return Array.from({ length: columnCount }, (_, index) => {
            const base = String(rawHeaders[index] || '').trim() || `Column ${index + 1}`;
            const count = (seen.get(base.toLowerCase()) || 0) + 1;
            seen.set(base.toLowerCase(), count);
            return count === 1 ? base : `${base} (${count})`;
        });
    },
    spreadsheetColumnLetter(index) {
        let value = Number(index) + 1;
        let output = '';
        while (value > 0) {
            value -= 1;
            output = String.fromCharCode(65 + (value % 26)) + output;
            value = Math.floor(value / 26);
        }
        return output;
    },
    priceImportColumnOptions() {
        return this.priceImportHeaders.map((header, index) => ({
            index: String(index),
            label: `${header} · Column ${this.spreadsheetColumnLetter(index)}`,
        }));
    },
    priceImportHeaderRowChoices() {
        return Array.from({ length: Math.min(this.priceImportMatrix.length, 20) }, (_, index) => index);
    },
    priceImportSelectedColumnOptions() {
        return this.priceImportColumnOptions().filter(column => this.priceImportIncludedColumns.includes(column.index));
    },
    isMappedQuantityColumn(index) {
        const value = String(index);
        if (this.priceImportQuantityMode === 'range') return value === String(this.priceImportQuantityColumn);
        return value === String(this.priceImportMinColumn) || value === String(this.priceImportMaxColumn);
    },
    togglePriceImportColumn(index, checked) {
        const value = String(index);
        if (checked && !this.priceImportIncludedColumns.includes(value)) this.priceImportIncludedColumns.push(value);
        if (!checked) this.priceImportIncludedColumns = this.priceImportIncludedColumns.filter(item => item !== value);
        if (!this.priceImportIncludedColumns.includes(String(this.priceImportPrimaryPriceColumn))) {
            this.priceImportPrimaryPriceColumn = this.priceImportIncludedColumns[0] || '';
        }
    },
    refreshPriceImportQuantityMapping() {
        this.priceImportIncludedColumns = this.priceImportIncludedColumns.filter(index => !this.isMappedQuantityColumn(index));
        if (!this.priceImportIncludedColumns.includes(String(this.priceImportPrimaryPriceColumn))) {
            this.priceImportPrimaryPriceColumn = this.priceImportIncludedColumns[0] || '';
        }
        this.priceImportMappingError = '';
    },
    inferRangeColumn(headers, dataRows) {
        const normalized = headers.map(header => this.normalizeSpreadsheetHeader(header));
        const aliases = ['quantity', 'qty', 'quantity range', 'qty range', 'pieces', 'piece range', 'order quantity', 'volume'];
        const aliasIndex = normalized.findIndex(header => aliases.includes(header));
        if (aliasIndex >= 0) return aliasIndex;

        let bestIndex = -1;
        let bestRatio = 0;
        headers.forEach((_, index) => {
            const samples = dataRows.slice(0, 12).map(row => row[index]).filter(value => String(value || '').trim() !== '');
            if (!samples.length) return;
            const matches = samples.filter(value => {
                const text = String(value || '').trim();
                return /\d/.test(text) && (/[-–—+]|\bto\b|and\s+up|or\s+more|above|over/i.test(text));
            }).length;
            const ratio = matches / samples.length;
            if (ratio > bestRatio) {
                bestRatio = ratio;
                bestIndex = index;
            }
        });
        return bestRatio >= 0.5 ? bestIndex : -1;
    },
    inferSplitQuantityColumns(headers) {
        const normalized = headers.map(header => this.normalizeSpreadsheetHeader(header));
        const minAliases = ['min qty', 'minimum qty', 'min quantity', 'minimum quantity', 'from qty', 'from quantity', 'minimum', 'from'];
        const maxAliases = ['max qty', 'maximum qty', 'max quantity', 'maximum quantity', 'to qty', 'to quantity', 'maximum', 'to'];
        return {
            min: normalized.findIndex(header => minAliases.includes(header)),
            max: normalized.findIndex(header => maxAliases.includes(header)),
        };
    },
    inferPrimaryPriceColumn(headers, dataRows, candidateIndexes) {
        const headerMatch = candidateIndexes.find(index => /unit|price|each|custom|blank|standard|base|amount|cost/i.test(headers[index]) && !/saving|discount|shipping|total|quantity|qty|percent/i.test(headers[index]));
        if (headerMatch !== undefined) return headerMatch;

        let bestIndex = candidateIndexes[0] ?? -1;
        let bestRatio = -1;
        candidateIndexes.forEach(index => {
            const samples = dataRows.slice(0, 20).map(row => String(row[index] ?? '').trim()).filter(Boolean);
            if (!samples.length) return;
            const numeric = samples.filter(value => {
                const clean = value.replace(/[$€£¥,%\s]/g, '').replace(/,/g, '');
                return clean !== '' && Number.isFinite(Number(clean));
            }).length;
            const ratio = numeric / samples.length;
            if (ratio > bestRatio) {
                bestRatio = ratio;
                bestIndex = index;
            }
        });
        return bestIndex;
    },
    setupPriceImportMapping(headerRowIndex = 0) {
        const index = Math.max(0, Math.min(Number(headerRowIndex) || 0, this.priceImportMatrix.length - 1));
        this.priceImportHeaderRowIndex = index;

        const dataRows = this.priceImportMatrix.slice(index + 1).filter(row => row.some(cell => cell !== ''));
        const usedColumnCount = Math.max(
            this.priceImportMatrix[index]?.length || 0,
            ...dataRows.slice(0, 50).map(row => row.reduce((last, cell, cellIndex) => cell !== '' ? cellIndex + 1 : last, 0)),
        );
        if (usedColumnCount < 2) throw new Error('The selected header row must contain at least two columns.');

        this.priceImportHeaders = this.uniqueSpreadsheetHeaders(this.priceImportMatrix[index] || [], usedColumnCount);
        this.priceImportPreviewRows = dataRows.slice(0, 6).map(row => Array.from({ length: usedColumnCount }, (_, cellIndex) => String(row[cellIndex] ?? '')));

        const split = this.inferSplitQuantityColumns(this.priceImportHeaders);
        const range = this.inferRangeColumn(this.priceImportHeaders, dataRows);
        if (split.min >= 0) {
            this.priceImportQuantityMode = 'split';
            this.priceImportMinColumn = String(split.min);
            this.priceImportMaxColumn = split.max >= 0 ? String(split.max) : '';
            this.priceImportQuantityColumn = '';
        } else {
            this.priceImportQuantityMode = 'range';
            this.priceImportQuantityColumn = range >= 0 ? String(range) : '0';
            this.priceImportMinColumn = '';
            this.priceImportMaxColumn = '';
        }

        const quantityIndexes = new Set(
            this.priceImportQuantityMode === 'split'
                ? [this.priceImportMinColumn, this.priceImportMaxColumn].filter(value => value !== '')
                : [this.priceImportQuantityColumn],
        );
        this.priceImportIncludedColumns = this.priceImportHeaders
            .map((_, columnIndex) => String(columnIndex))
            .filter(columnIndex => !quantityIndexes.has(columnIndex))
            .filter(columnIndex => dataRows.some(row => String(row[Number(columnIndex)] ?? '').trim() !== ''));

        const includedNumbers = this.priceImportIncludedColumns.map(Number);
        const primary = this.inferPrimaryPriceColumn(this.priceImportHeaders, dataRows, includedNumbers);
        this.priceImportPrimaryPriceColumn = primary >= 0 ? String(primary) : (this.priceImportIncludedColumns[0] || '');
        this.priceImportMappingError = '';
    },
    preparePriceImportMapping(matrix, fileName, targetTable = null, targetLabel = '') {
        const rows = this.spreadsheetRows(matrix);
        if (rows.length < 2) throw new Error('The spreadsheet must contain a header row and at least one data row.');
        if (rows.length > 501) throw new Error('A maximum of 500 spreadsheet rows can be reviewed at once.');
        this.priceImportMatrix = rows;
        this.priceImportFileName = fileName;
        this.priceImportTargetTable = targetTable || null;
        this.priceImportTargetLabel = targetLabel || '';
        this.setupPriceImportMapping(this.detectSpreadsheetHeaderRow(rows));
        this.priceImportMappingOpen = true;
    },
    priceImportDialogTitle() {
        return this.priceImportTargetTable
            ? `Map the ${this.priceImportTargetLabel || 'fabric'} price table`
            : 'Map the uploaded price table';
    },
    priceImportDialogDescription() {
        return this.priceImportTargetTable
            ? 'Import this spreadsheet into the selected fabric only. It will replace this fabric price table, not the default product price table.'
            : 'No predefined Excel structure is required. Select how this spreadsheet should become the customer-visible price table.';
    },
    isImportingFabricTable(table) {
        return this.priceImportBusy && this.priceImportTargetTable === table;
    },
    closePriceImportMapping() {
        this.priceImportMappingOpen = false;
        this.priceImportMappingError = '';
        this.priceImportTargetTable = null;
        this.priceImportTargetLabel = '';
    },
    applyPriceImportMapping() {
        this.priceImportMappingError = '';
        try {
            const dataRows = this.priceImportMatrix
                .slice(Number(this.priceImportHeaderRowIndex) + 1)
                .filter(row => row.some(cell => String(cell ?? '').trim() !== ''));
            if (!dataRows.length) throw new Error('No data rows were found below the selected header row.');

            let rangeColumn = null;
            let minColumn = null;
            let maxColumn = null;
            if (this.priceImportQuantityMode === 'range') {
                rangeColumn = Number(this.priceImportQuantityColumn);
                if (!Number.isInteger(rangeColumn)) throw new Error('Choose the column that contains quantity ranges.');
            } else {
                minColumn = Number(this.priceImportMinColumn);
                maxColumn = this.priceImportMaxColumn === '' ? null : Number(this.priceImportMaxColumn);
                if (!Number.isInteger(minColumn)) throw new Error('Choose the minimum quantity column.');
                if (maxColumn !== null && !Number.isInteger(maxColumn)) throw new Error('Choose a valid maximum quantity column.');
                if (maxColumn !== null && maxColumn === minColumn) throw new Error('Minimum and maximum quantity must use different columns.');
            }

            const mappedQuantity = new Set(
                this.priceImportQuantityMode === 'range'
                    ? [String(rangeColumn)]
                    : [String(minColumn), ...(maxColumn === null ? [] : [String(maxColumn)])],
            );
            const valueIndexes = this.priceImportIncludedColumns
                .map(Number)
                .filter(index => Number.isInteger(index) && !mappedQuantity.has(String(index)));
            if (!valueIndexes.length) throw new Error('Select at least one storefront price-table column.');
            if (valueIndexes.length > 19) throw new Error('A maximum of 19 storefront columns can be imported.');

            const primaryIndex = Number(this.priceImportPrimaryPriceColumn);
            if (!valueIndexes.includes(primaryIndex)) throw new Error('Choose a primary live price column from the selected storefront columns.');

            const importedRows = [];
            dataRows.forEach((row, sourceIndex) => {
                const values = valueIndexes.map(index => String(row[index] ?? '').trim());
                const quantityRaw = this.priceImportQuantityMode === 'range'
                    ? String(row[rangeColumn] ?? '').trim()
                    : String(row[minColumn] ?? '').trim();
                if (quantityRaw === '' && values.every(value => value === '')) return;

                let range;
                if (this.priceImportQuantityMode === 'range') {
                    range = this.parseQuantityRange(row[rangeColumn]);
                } else {
                    const minimum = this.spreadsheetInteger(row[minColumn]);
                    const maximum = maxColumn === null ? '' : this.spreadsheetInteger(row[maxColumn], { allowBlank: true });
                    if (!Number.isInteger(minimum)) throw new Error(`Spreadsheet row ${Number(this.priceImportHeaderRowIndex) + sourceIndex + 2} has an invalid minimum quantity.`);
                    if (maximum !== '' && (!Number.isInteger(maximum) || maximum < minimum)) {
                        throw new Error(`Spreadsheet row ${Number(this.priceImportHeaderRowIndex) + sourceIndex + 2} has an invalid maximum quantity.`);
                    }
                    range = { minimum_quantity: minimum, maximum_quantity: maximum };
                }

                if (values.every(value => value === '')) {
                    throw new Error(`Spreadsheet row ${Number(this.priceImportHeaderRowIndex) + sourceIndex + 2} has a quantity but no selected storefront values.`);
                }
                importedRows.push({ ...range, cells: values });
            });

            if (!importedRows.length) throw new Error('No usable price rows were found using the selected mapping.');
            if (importedRows.length > 200) throw new Error('A maximum of 200 price rows can be imported.');

            importedRows.forEach((row, index) => {
                const minimum = Number(row.minimum_quantity);
                if (!Number.isInteger(minimum) || minimum < 1) {
                    row.minimum_quantity = '';
                    row.maximum_quantity = '';
                    return;
                }

                const nextMinimum = Number(importedRows[index + 1]?.minimum_quantity);
                if (Number.isInteger(nextMinimum) && nextMinimum > minimum && String(row.maximum_quantity ?? '').trim() === '') {
                    row.maximum_quantity = nextMinimum - 1;
                    return;
                }

                const maximum = Number(row.maximum_quantity);
                if (String(row.maximum_quantity ?? '').trim() !== '' && (!Number.isInteger(maximum) || maximum < minimum)) {
                    row.maximum_quantity = '';
                }
            });

            const importedHeaders = valueIndexes.map(index => this.priceImportHeaders[index]);
            const importedHighlightColumn = valueIndexes.indexOf(primaryIndex) + 1;
            const targetTable = this.priceImportTargetTable;
            const statusMessage = `${importedRows.length} row${importedRows.length === 1 ? '' : 's'} and ${importedHeaders.length} column${importedHeaders.length === 1 ? '' : 's'} generated from ${this.priceImportFileName}. Quantity maximums were completed automatically from the next row's starting quantity.`;

            if (targetTable) {
                targetTable.headers = importedHeaders;
                targetTable.rows = importedRows.map(row => ({
                    minimum_quantity: row.minimum_quantity,
                    maximum_quantity: row.maximum_quantity,
                    maximum_quantity_auto: String(row.maximum_quantity ?? '').trim() === '',
                    cells: Array.isArray(row.cells) ? row.cells.slice() : [],
                }));
                targetTable.highlight_column = importedHighlightColumn;
                targetTable.has_custom_pricing = true;
                targetTable.import_status = statusMessage;
                targetTable.import_error = '';
                if (typeof this.normalizeFabricPriceTable === 'function') {
                    this.normalizeFabricPriceTable(targetTable);
                }
                this.priceImportStatus = '';
                this.priceImportError = '';
            } else {
                this.priceHeaders = importedHeaders;
                this.priceRows = importedRows;
                this.priceHighlightColumn = importedHighlightColumn;
                this.normalizePriceRows();
                this.priceImportStatus = statusMessage;
                this.priceImportError = '';
            }
            this.closePriceImportMapping();
        } catch (error) {
            this.priceImportMappingError = error instanceof Error ? error.message : 'The selected spreadsheet mapping could not be applied.';
        }
    },
    parseCsvMatrix(text) {
        const rows = [];
        let row = [];
        let cell = '';
        let quoted = false;
        const source = String(text || '').replace(/^\uFEFF/, '');

        for (let index = 0; index < source.length; index += 1) {
            const character = source[index];
            if (quoted) {
                if (character === '"' && source[index + 1] === '"') {
                    cell += '"';
                    index += 1;
                } else if (character === '"') quoted = false;
                else cell += character;
                continue;
            }
            if (character === '"') quoted = true;
            else if (character === ',') {
                row.push(cell);
                cell = '';
            } else if (character === '\n') {
                row.push(cell.replace(/\r$/, ''));
                rows.push(row);
                row = [];
                cell = '';
            } else cell += character;
        }
        row.push(cell.replace(/\r$/, ''));
        if (row.some(value => value !== '') || rows.length === 0) rows.push(row);
        return rows;
    },
    async importPriceTable(event, targetTable = null, targetLabel = '') {
        const file = event.target.files?.[0];
        if (!file) return;
        this.priceImportBusy = true;
        this.priceImportStatus = '';
        this.priceImportError = '';
        this.priceImportMappingError = '';
        this.priceImportTargetTable = targetTable || null;
        this.priceImportTargetLabel = targetLabel || '';
        if (targetTable) {
            targetTable.import_status = '';
            targetTable.import_error = '';
        }

        try {
            if (file.size > 5 * 1024 * 1024) throw new Error('The spreadsheet must not exceed 5 MB.');
            const extension = String(file.name || '').split('.').pop().toLowerCase();
            if (!['xlsx', 'csv'].includes(extension)) throw new Error('Upload an XLSX or CSV spreadsheet.');
            const matrix = extension === 'csv'
                ? this.parseCsvMatrix(await file.text())
                : await readSheet(file);
            this.preparePriceImportMapping(matrix, file.name, targetTable, targetLabel);
        } catch (error) {
            const message = error instanceof Error ? error.message : 'The price table could not be imported.';
            if (targetTable) targetTable.import_error = message;
            else this.priceImportError = message;
        } finally {
            this.priceImportBusy = false;
            event.target.value = '';
        }
    },
    importFabricPriceTable(event, table, label = '') {
        if (!table) return;
        table.has_custom_pricing = true;
        return this.importPriceTable(event, table, label);
    },
    addPriceHeader() { this.priceHeaders.push('New Column'); this.normalizePriceRows(); },
    removePriceHeader(index) {
        if (this.priceHeaders.length <= 1) return;
        this.priceHeaders.splice(index, 1);
        this.priceRows.forEach(row => row.cells.splice(index, 1));
    },
    addPriceRow() {
        this.priceRows.push({ minimum_quantity: '', maximum_quantity: '', maximum_quantity_auto: true, cells: this.priceHeaders.map(() => '') });
        this.recalculatePriceMaximums();
    },
    removePriceRow(index) {
        this.priceRows.splice(index, 1);
        this.recalculatePriceMaximums();
    },
    normalizePriceRows() {
        this.priceRows = this.priceRows.map(row => {
            const sourceCells = Array.isArray(row) ? row.slice(1) : (Array.isArray(row.cells) ? row.cells : []);
            const maximum = Array.isArray(row) ? '' : (row.maximum_quantity ?? '');
            return {
                minimum_quantity: Array.isArray(row) ? (row[0] || '') : (row.minimum_quantity ?? ''),
                maximum_quantity: maximum,
                maximum_quantity_auto: Array.isArray(row) ? true : (row.maximum_quantity_auto ?? String(maximum ?? '').trim() === ''),
                cells: this.priceHeaders.map((_, index) => sourceCells[index] ?? ''),
            };
        });
        this.recalculatePriceMaximums();
    },
    calculatedMaximumForPriceRow(index) {
        const row = this.priceRows[index] || {};
        const minimum = Number(row.minimum_quantity);
        if (!Number.isInteger(minimum) || minimum < 1) return '';

        const nextRow = this.priceRows.slice(index + 1).find(item => {
            const nextMinimum = Number(item?.minimum_quantity);
            return Number.isInteger(nextMinimum) && nextMinimum >= 1;
        });
        const nextMinimum = Number(nextRow?.minimum_quantity);
        return Number.isInteger(nextMinimum) && nextMinimum > minimum
            ? nextMinimum - 1
            : minimum;
    },
    markPriceMaximumManual(row) {
        row.maximum_quantity_auto = String(row.maximum_quantity ?? '').trim() === '';
    },
    recalculatePriceMaximums() {
        this.priceRows.forEach((row, index) => {
            const minimum = Number(row.minimum_quantity);
            const maximum = Number(row.maximum_quantity);
            if (!Number.isInteger(minimum) || minimum < 1) {
                if (row.maximum_quantity_auto || String(row.maximum_quantity ?? '').trim() === '') row.maximum_quantity = '';
                return;
            }

            if (row.maximum_quantity_auto || String(row.maximum_quantity ?? '').trim() === '') {
                row.maximum_quantity = this.calculatedMaximumForPriceRow(index);
                row.maximum_quantity_auto = true;
                return;
            }

            if (!Number.isInteger(maximum) || maximum < minimum) {
                row.maximum_quantity = '';
                row.maximum_quantity_auto = true;
            }
        });
    },
    quantityRangeLabel(row) {
        const minimum = Number(row?.minimum_quantity);
        if (!Number.isInteger(minimum) || minimum < 1) return 'Enter the starting quantity';
        const maximum = Number(row?.maximum_quantity);
        return row.maximum_quantity === '' || !Number.isInteger(maximum)
            ? `${minimum}+`
            : `${minimum} – ${maximum}`;
    },
    optionValueTemplate(overrides = {}) {
        return {
            existing_id: '', jersey_customization_option_id: '', client_key: this.clientKey(), label: '', code: '', description: '',
            color_hex: '', image_url: '', image_previews: [], primary_image_url: '', image_error: false, clear_images: false,
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
    inferJerseyCustomizationType(name) {
        const plain = String(name || '').trim().toLowerCase();
        const normalized = plain.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        const direct = Object.entries(this.jerseyCustomizationTypes)
            .find(([type, label]) => type === normalized || String(label).trim().toLowerCase() === plain)?.[0];
        if (direct) return direct;
        if (/\bsize\b/.test(plain)) return '';
        if (/shorts?.*colou?r|colou?r.*shorts?/.test(plain)) return 'shorts_color';
        if (/shorts?.*(fabric|material)|(fabric|material).*shorts?/.test(plain)) return 'shorts_fabric';

        if (/shorts?.*pocket|pocket.*shorts?/.test(plain)) return 'shorts_pocket_option';
        if (/uniform.*type|type.*uniform/.test(plain)) return 'uniform_type';
        if (/reversible|standard/.test(plain) && /uniform/.test(plain)) return 'uniform_style';
        if (/uniform.*neck|neck.*uniform|uniform.*collar|collar.*uniform/.test(plain)) return 'uniform_neckline';
        if (/uniform.*sleeve|sleeve.*uniform/.test(plain)) return 'uniform_sleeve';

        if (/uniform.*pocket|pocket.*uniform/.test(plain)) return 'uniform_pocket';
        if (/pants?.*colou?r|colou?r.*pants?/.test(plain)) return 'pants_color';
        if (/pants?.*(fabric|material)|(fabric|material).*pants?/.test(plain)) return 'pants_fabric';
        if (/calf|pants?.*style|style.*pants?/.test(plain)) return 'pants_calf_style';

        if (/quarter[-\s_]*zip.*colou?r|colou?r.*quarter[-\s_]*zip/.test(plain)) return 'quarter_zip_color';
        if (/quarter[-\s_]*zip.*(fabric|material)|(fabric|material).*quarter[-\s_]*zip/.test(plain)) return 'quarter_zip_fabric';
        if (/quarter[-\s_]*zip.*zipper|zipper.*quarter[-\s_]*zip/.test(plain)) return 'quarter_zip_zipper';
        if (/quarter[-\s_]*zip.*sleeve|sleeve.*quarter[-\s_]*zip/.test(plain)) return 'quarter_zip_sleeve';

        if (/tank[-\s_]*top.*colou?r|colou?r.*tank[-\s_]*top/.test(plain)) return 'tank_top_color';
        if (/tank[-\s_]*top.*(fabric|material)|(fabric|material).*tank[-\s_]*top/.test(plain)) return 'tank_top_fabric';
        if (/tank[-\s_]*top.*style|style.*tank[-\s_]*top/.test(plain)) return 'tank_top_style';

        if (/compression.*wear.*colou?r|colou?r.*compression.*wear/.test(plain)) return 'compression_wear_color';
        if (/compression.*wear.*(material|fabric)|(material|fabric).*compression.*wear/.test(plain)) return 'compression_wear_materials';
        if (/compression.*wear.*pattern|pattern.*compression.*wear/.test(plain)) return 'compression_wear_pattern';
        if (/socks?.*colou?r|colou?r.*socks?/.test(plain)) return 'socks_color';
        if (/socks?.*pattern|pattern.*socks?/.test(plain)) return 'socks_pattern';
        if (/socks?.*(material|construction)|(material|construction).*socks?/.test(plain)) return 'socks_material_construction';
        if (/collar|neck/.test(plain)) return 'neck_and_collar';
        if (/fabric|material/.test(plain)) return 'fabric';
        if (/colou?r/.test(plain)) return 'color';
        if (/sleeve|cuff/.test(plain)) return 'sleeves_and_cuffs';
        if (/jersey\s*style|uniform\s*style|\bstyle\b/.test(plain)) return 'jersey_style';
        return '';
    },
    masterItemById(id) {
        const numericId = Number(id || 0);
        return this.jerseyCustomizationOptions.find(item => Number(item.id) === numericId) || null;
    },
    masterItemPrimaryImage(item) {
        if (!item) return '';
        const images = Array.isArray(item.images) ? item.images : [];
        return images.find(image => image.is_primary)?.url || images[0]?.url || '';
    },
    hydrateValueFromMaster(value, item) {
        value.jersey_customization_option_id = Number(item.id);
        value.label = String(item.name || '');
        value.code = String(item.slug || slugify(item.name));
        value.description = String(item.description || '');
        value.color_hex = String(item.color_hex || '');
        value.image_previews = (Array.isArray(item.images) ? item.images : []).map(image => image.url).filter(Boolean);
        value.primary_image_url = this.masterItemPrimaryImage(item);
        value.image_url = value.primary_image_url || '';
        value.clear_images = false;
        return value;
    },
    defaultInputStyleForFeature(type) {
        if (String(type || '').includes('color')) return 'swatch';
        const options = this.jerseyCustomizationOptions.filter(item => item.type === type);
        return options.some(item => this.masterItemPrimaryImage(item)) ? 'image' : 'buttons';
    },
    openNewFeatureDialog() {
        this.newFeatureType = '';
        this.newFeatureNameError = '';
        this.newFeatureDialogOpen = true;
        this.$nextTick(() => this.$refs.newFeatureNameInput?.focus());
    },
    closeNewFeatureDialog() {
        this.newFeatureDialogOpen = false;
        this.newFeatureType = '';
        this.newFeatureNameError = '';
    },
    confirmNewFeature() {
        const type = String(this.newFeatureType || '');
        const name = String(this.jerseyCustomizationTypes[type] || '');

        if (!type || !name) {
            this.newFeatureNameError = 'Choose a feature name before continuing.';
            this.$nextTick(() => this.$refs.newFeatureNameInput?.focus());
            return;
        }

        const duplicate = this.optionGroups.some(group => String(group.jersey_customization_type || '') === type);
        if (duplicate) {
            this.newFeatureNameError = 'This customization feature is already added to the product.';
            return;
        }

        const group = this.addOptionGroup(type, name);
        this.closeNewFeatureDialog();

        this.$nextTick(() => {
            const selector = `[data-option-group-key="${group.client_key}"]`;
            document.querySelector(selector)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    },
    addOptionGroup(type, name) {
        const group = {
            client_key: this.clientKey(), name, code: this.uniqueOptionGroupCode(type || name), jersey_customization_type: type,
            section: 'product', type: this.defaultInputStyleForFeature(type), display_mode: 'customer',
            fixed_value_code: '', fixed_text_value: '', show_in_summary: true, use_as_filter: false, catalog_attribute_id: '', description: '',
            placeholder: '', is_required: false, minimum_selections: '', maximum_selections: '', accepted_file_types: '', maximum_file_size_mb: 15,
            is_active: true, values: [],
        };

        this.optionGroups.push(group);
        return group;
    },
    activeMasterItemGroup() {
        return Number.isInteger(this.masterItemPickerGroupIndex) ? this.optionGroups[this.masterItemPickerGroupIndex] || null : null;
    },
    openMasterItemPicker(groupIndex) {
        if (!this.optionGroups[groupIndex]) return;
        this.masterItemPickerGroupIndex = groupIndex;
        this.masterItemPickerOpen = true;
    },
    closeMasterItemPicker() {
        this.masterItemPickerOpen = false;
        this.masterItemPickerGroupIndex = null;
    },
    availableMasterItems() {
        const group = this.activeMasterItemGroup();
        if (!group?.jersey_customization_type) return [];
        return this.jerseyCustomizationOptions.filter(item => item.type === group.jersey_customization_type);
    },
    isMasterItemSelected(itemId) {
        const group = this.activeMasterItemGroup();
        return Boolean(group?.values?.some(value => Number(value.jersey_customization_option_id) === Number(itemId)));
    },
    selectMasterItem(item) {
        const group = this.activeMasterItemGroup();
        if (!group || !item || item.type !== group.jersey_customization_type || this.isMasterItemSelected(item.id)) return;
        const value = this.optionValueTemplate({
            jersey_customization_option_id: Number(item.id),
            is_default: group.values.length === 0,
        });
        this.hydrateValueFromMaster(value, item);
        group.values.push(value);
        this.handleProgressChange();
    },
    removeOptionGroup(index) {
        this.optionGroups.splice(index, 1);
        this.handleProgressChange(false);
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
    openSizeGroupPicker() {
        this.sizeGroupPickerSearch = '';
        const selectedGroups = [...new Set((this.optionGroups || [])
            .map(group => this.customizationGroupForType(group.jersey_customization_type))
            .filter(Boolean))];
        this.sizeGroupPickerContext = selectedGroups.length === 1 ? selectedGroups[0] : '';
        this.sizeGroupPickerOpen = true;
        document.documentElement.classList.add('overflow-hidden');
    },
    closeSizeGroupPicker() {
        this.sizeGroupPickerOpen = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
    customizationGroupForType(type) {
        for (const group of this.customizationTypeGroups || []) {
            const match = (group.types || []).find(item => item.value === type);
            if (match) return match.group || '';
        }
        return '';
    },
    filteredSizeOptionGroups() {
        const query = String(this.sizeGroupPickerSearch || '').trim().toLowerCase();
        const context = String(this.sizeGroupPickerContext || '');
        return (this.sizeOptionGroups || []).filter(group => {
            if (context && String(group.customization_group || '') !== context) return false;
            return !query
                || String(group.name || '').toLowerCase().includes(query)
                || String(group.audience_label || '').toLowerCase().includes(query)
                || String(group.customization_group_label || '').toLowerCase().includes(query)
                || (group.sizes || []).some(size => String(size).toLowerCase().includes(query));
        });
    },
    isSizeGroupSelected(id) {
        return this.sizeGroups.some(group => Number(group.size_option_group_id || 0) === Number(id));
    },
    selectSizeGroup(master) {
        if (!master || this.isSizeGroupSelected(master.id)) return;
        this.sizeGroups.push({
            client_key: this.clientKey(),
            existing_id: '',
            size_option_group_id: Number(master.id),
            name: String(master.name || ''),
            code: String(master.slug || ''),
            audience_label: String(master.audience_label || ''),
            customization_group_label: String(master.customization_group_label || ''),
            description_html: String(master.description_html || ''),
            sizes: Array.isArray(master.sizes) ? [...master.sizes] : [],
            chart_enabled: Boolean(master.chart_enabled),
            chart_html: String(master.chart_html || ''),
            chart_title: String(master.chart_title || ''),
            chart_note: String(master.chart_note || ''),
            chart_columns: Array.isArray(master.chart_columns) ? [...master.chart_columns] : [],
            chart_rows: Array.isArray(master.chart_rows) ? master.chart_rows.map(row => [...row]) : [],
            chart_image_url: '',
            chart_image_preview: String(master.chart_image_preview || ''),
            is_active: true,
        });
        this.handleProgressChange();
    },
    productionCellTemplate(overrides = {}) {
        return {
            enabled: false,
            description: '',
            price_adjustment: 0,
            production_time: '1 day',
            minimum_days: 1,
            maximum_days: 1,
            ...overrides,
        };
    },
    normalizeProductionTable() {
        this.productionHeaders = (Array.isArray(this.productionHeaders) ? this.productionHeaders : [])
            .map(header => String(header || ''));
        if (!this.productionHeaders.length) this.productionHeaders = ['Standard Production'];

        this.productionRows = (Array.isArray(this.productionRows) ? this.productionRows : [])
            .map(row => {
                const sourceCells = Array.isArray(row?.cells) ? row.cells : [];
                return {
                    client_key: row?.client_key || this.clientKey(),
                    range: String(row?.range || ''),
                    cells: this.productionHeaders.map((_, index) => this.productionCellTemplate({
                        ...(sourceCells[index] || {}),
                        enabled: this.booleanValue(sourceCells[index]?.enabled, false),
                        price_adjustment: Math.max(0, Number(sourceCells[index]?.price_adjustment || 0)),
                        production_time: String(
                            sourceCells[index]?.production_time
                            || this.formatProductionTime(
                                sourceCells[index]?.minimum_days ?? 1,
                                sourceCells[index]?.maximum_days ?? sourceCells[index]?.minimum_days ?? 1,
                            )
                        ),
                        minimum_days: Math.max(0, Number(sourceCells[index]?.minimum_days ?? 1)),
                        maximum_days: Math.max(
                            Math.max(0, Number(sourceCells[index]?.minimum_days ?? 1)),
                            Number(sourceCells[index]?.maximum_days ?? sourceCells[index]?.minimum_days ?? 1),
                        ),
                    })),
                };
            });
        if (!this.productionRows.length) this.addProductionRow();
    },
    addProductionHeader() {
        if (this.productionHeaders.length >= 12) return;
        this.productionHeaders.push(`Production Option ${this.productionHeaders.length + 1}`);
        this.productionRows.forEach(row => row.cells.push(this.productionCellTemplate()));
    },
    removeProductionHeader(index) {
        if (this.productionHeaders.length <= 1) return;
        this.productionHeaders.splice(index, 1);
        this.productionRows.forEach(row => row.cells.splice(index, 1));
    },
    addProductionRow() {
        if (this.productionRows.length >= 100) return;
        this.productionRows.push({
            client_key: this.clientKey(),
            range: '',
            cells: this.productionHeaders.map(() => this.productionCellTemplate()),
        });
        this.handleProgressChange(false);
    },
    removeProductionRow(index) {
        this.productionRows.splice(index, 1);
    },
    formatProductionTime(minimum, maximum) {
        const min = Math.max(0, Number(minimum || 0));
        const max = Math.max(min, Number(maximum ?? min));
        if (min === 0 && max === 0) return 'To be confirmed';
        return min === max ? `${min} ${min === 1 ? 'day' : 'days'}` : `${min}-${max} days`;
    },
    productionCellSummary(cell) {
        if (!cell?.enabled) return 'Not offered';
        const charge = Number(cell.price_adjustment || 0) > 0
            ? `+$${Number(cell.price_adjustment).toFixed(2)} / piece`
            : 'Included';
        const time = String(cell.production_time || this.formatProductionTime(cell.minimum_days, cell.maximum_days)).trim();
        return `${charge} · ${time}`;
    },
    addShippingMethod() { this.shippingMethods.push({ name: '', code: '', description: '', price_adjustment: 0, charge_type: 'per_unit', minimum_days: 1, maximum_days: 1, is_default: this.shippingMethods.length === 0, is_active: true }); },
    setDefaultShipping(index) { this.shippingMethods.forEach((method, methodIndex) => method.is_default = methodIndex === index); },
    addRosterField() { this.rosterFields.push({ key: '', label: '', type: 'text', max_length: 60, required: false, enabled: true }); },
    addFaq() { this.faqs.push({ question: '', answer: '', is_active: true }); },
});

window.productSpecificationEditor = (initial = '', autoValues = {}) => ({
    value: String(initial || ''),
    autoValues: autoValues || {},
    templateLabels: ['SKU', 'Product Type', 'Fabric', 'Fit', 'Customization', 'Size Range', 'MOQ', 'Lead Time'],
    fabricLikeLabels: ['Fabric', 'Material', 'Materials', 'Metarial', 'Metarials', 'Meterial', 'Meterials'],
    knownLabels: [
        'SKU', 'Product Type', 'Fabric', 'Material', 'Materials', 'Metarial', 'Metarials', 'Meterial', 'Meterials', 'Fabric GSM', 'GSM', 'Width', 'Fit',
        'Customization', 'Imprint Method', 'Attachment', 'Size Range', 'MOQ', 'Lead Time',
        'Shipping Time', 'Usage', 'Standard Length',
    ],
    aliases: {
        'sku': 'SKU',
        'style no': 'SKU',
        'style number': 'SKU',
        'product type': 'Product Type',
        'product': 'Product Type',
        'fabric': 'Fabric',
        'fabric gsm': 'Fabric GSM',
        'fabric weight': 'Fabric GSM',
        'gsm': 'GSM',
        'material': 'Material',
        'materials': 'Materials',
        'metarial': 'Metarial',
        'metarials': 'Metarials',
        'meterial': 'Meterial',
        'meterials': 'Meterials',
        'fit': 'Fit',
        'width': 'Width',
        'customization': 'Customization',
        'customisation': 'Customization',
        'printing': 'Customization',
        'print method': 'Customization',
        'imprint method': 'Imprint Method',
        'decoration': 'Customization',
        'attachment': 'Attachment',
        'size range': 'Size Range',
        'sizes': 'Size Range',
        'size': 'Size Range',
        'moq': 'MOQ',
        'minimum order': 'MOQ',
        'minimum order quantity': 'MOQ',
        'lead time': 'Lead Time',
        'lead-time': 'Lead Time',
        'production time': 'Lead Time',
        'shipping time': 'Shipping Time',
        'usage': 'Usage',
        'standard length': 'Standard Length',
    },
    get template() {
        return this.templateLabels.map((label) => `${label}:`).join('\n');
    },
    init() {
        if (!this.value.trim()) {
            this.value = this.template;
        }

        this.$refs.editor.innerText = this.value;
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
    clearFormat() {
        this.command('removeFormat');
    },
    resetTemplate() {
        this.value = this.template;
        this.$refs.editor.innerText = this.value;
        this.sync();
    },
    handlePaste(event) {
        const clipboard = event.clipboardData || window.clipboardData;
        if (!clipboard) return;

        const plain = clipboard.getData('text/plain') || '';
        const html = clipboard.getData('text/html') || '';
        const rows = this.rowsFromPastedHtml(html);

        if (!plain.trim() && rows.length === 0) return;

        event.preventDefault();
        const text = rows.length > 0
            ? rows.map((row) => `${row.label}: ${row.value}`.trim()).join('\n')
            : plain;

        document.execCommand('insertText', false, text);
        this.$nextTick(() => this.sync());
    },
    sync() {
        this.value = this.$refs.editor?.innerText || '';
    },
    rowsFromPastedHtml(html = '') {
        if (!html.trim()) return [];

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const rows = [];

        wrapper.querySelectorAll('table tr').forEach((tr) => {
            const cells = Array.from(tr.querySelectorAll('th,td'))
                .map((cell) => this.cleanText(cell.innerText || cell.textContent || ''))
                .filter(Boolean);

            if (cells.length < 2) return;

            const label = this.normalizeLabel(cells[0]);
            const value = this.cleanText(cells.slice(1).join(' '));

            if (label && !this.isHeaderLabel(label) && !this.isHeaderLabel(value)) {
                rows.push({ label, value });
            }
        });

        return this.uniqueRows(rows);
    },
    rowsFromText(text = '') {
        const lines = String(text || '')
            .replace(/\u00a0/g, ' ')
            .split(/\r?\n|\r/)
            .map((line) => line.trim())
            .filter(Boolean);

        const rows = [];

        lines.forEach((line) => {
            if (/^detail\s+information$/i.test(line) || /^(detail|information)$/i.test(line)) return;

            const flatRows = this.rowsFromFlatText(line);
            if (flatRows.length > 1) {
                rows.push(...flatRows);
                return;
            }

            const tabParts = line.split(/\t+/).map((part) => this.cleanText(part)).filter(Boolean);
            if (tabParts.length >= 2) {
                const label = this.normalizeLabel(tabParts[0]);
                if (label && !this.isHeaderLabel(label)) {
                    rows.push({ label, value: tabParts.slice(1).join(' ') });
                    return;
                }
            }

            if (line.includes(':')) {
                const [rawLabel, ...rest] = line.split(':');
                const label = this.normalizeLabel(rawLabel);
                if (label && !this.isHeaderLabel(label)) {
                    rows.push({ label, value: this.cleanText(rest.join(':')) });
                    return;
                }
            }

            const matched = this.rowFromKnownLabelPrefix(line);
            if (matched) rows.push(matched);
        });

        return this.uniqueRows(rows);
    },
    rowsFromFlatText(text = '') {
        const labels = this.knownLabels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const pattern = new RegExp(`(${labels.join('|')})\\s*:`, 'gi');
        const matches = Array.from(String(text || '').matchAll(pattern));
        const rows = [];

        matches.forEach((match, index) => {
            const label = this.normalizeLabel(match[1]);
            if (!label) return;

            const start = match.index + match[0].length;
            const end = matches[index + 1]?.index ?? String(text).length;
            const value = this.cleanText(String(text).slice(start, end));

            rows.push({ label, value });
        });

        return this.uniqueRows(rows);
    },
    rowFromKnownLabelPrefix(line) {
        const clean = this.cleanText(line);
        const lower = clean.toLowerCase();

        const label = this.knownLabels.find((item) => {
            const key = item.toLowerCase();
            return lower === key || lower.startsWith(`${key} `) || lower.startsWith(`${key}\t`);
        });

        if (!label) return null;

        return {
            label,
            value: this.cleanText(clean.slice(label.length)),
        };
    },
    uniqueRows(rows) {
        const map = new Map();

        rows.forEach((row) => {
            const label = this.normalizeLabel(row.label);
            if (!label) return;

            const value = this.cleanText(row.value || '');
            const key = this.isFabricLikeLabel(label) ? 'fabric-like' : label.toLowerCase();

            if (!map.has(key) || value) {
                map.set(key, { label, value });
            }
        });

        return Array.from(map.values());
    },
    normalizeLabel(label = '') {
        const clean = this.cleanText(label).replace(/:$/, '').trim();
        if (!clean) return '';

        const lower = clean.toLowerCase();
        if (this.aliases[lower]) return this.aliases[lower];

        return clean
            .split(' ')
            .map((word) => word.length <= 3 && word === word.toUpperCase() ? word : word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    },
    isHeaderLabel(value = '') {
        return ['detail', 'information'].includes(String(value).trim().toLowerCase());
    },
    isFabricLikeLabel(label = '') {
        return this.fabricLikeLabels.includes(this.normalizeLabel(label));
    },
    cleanText(value = '') {
        return String(value)
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+/g, ' ')
            .replace(/\s+$/g, '')
            .replace(/^\s+/g, '');
    },
    previewRows() {
        const manualRows = new Map(
            this.rowsFromText(this.value).map((row) => [row.label, row.value])
        );
        const fabricDisplayLabel = this.fabricLikeLabels.find((label) => String(manualRows.get(label) || '').trim()) || 'Fabric';
        const orderedLabels = this.knownLabels.map((label) => label === 'Fabric' ? fabricDisplayLabel : label)
            .filter((label, index, labels) => labels.indexOf(label) === index);

        const rows = orderedLabels.map((label) => {
            if (this.fabricLikeLabels.includes(label) && label !== fabricDisplayLabel) return null;

            const manualValue = String(manualRows.get(label) || '').trim();
            const autoValue = this.isFabricLikeLabel(label)
                ? String(this.autoValues[label] || this.autoValues.Fabric || '').trim()
                : String(this.autoValues[label] || '').trim();
            const finalValue = manualValue || autoValue;

            if (!finalValue) return null;

            return { label, value: finalValue };
        }).filter(Boolean);

        manualRows.forEach((value, label) => {
            if (!orderedLabels.includes(label) && String(value || '').trim()) {
                rows.push({ label, value: String(value).trim() });
            }
        });

        return rows;
    },
});

window.adminRichEditor = (initial = '', name = '') => ({
    value: initial || '',
    name,
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
        this.$dispatch('admin-rich-editor-updated', { name: this.name, value: this.value });
    },
});



const rosterExcludedProductProfiles = ['bag', 'headwear', 'drinkware', 'drinkwear', 'lanyard', 'lyniard', 'headband'];
const rosterSupportsProductProfile = (profile = '') => !rosterExcludedProductProfiles.includes(String(profile || '').trim().toLowerCase());

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
            if (shipping.charge_type === 'master_method') {
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
                } else if (shipping.charge_type !== 'included') {
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


window.adminMediaLibraryManager = (initial = {}) => ({
    indexUrl: initial.indexUrl || '/admin/media-library',
    storeUrl: initial.storeUrl || '/admin/media-library',
    totalImages: Number(initial.totalImages || 0),
    scope: initial.scope || '',
    perPage: Number(initial.perPage || 24),
    emptyMessage: initial.emptyMessage || 'No media files found. Upload files above to start the gallery.',
    items: [],
    selectedIds: [],
    search: '',
    page: 1,
    hasMore: false,
    loading: false,
    uploadBusy: false,
    dragging: false,
    dragDepth: 0,
    error: '',
    uploadError: '',
    init() {
        this.load(false);
    },
    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
    imageFileExtension(file) {
        return String(file?.name || '').split('.').pop().toLowerCase();
    },
    isAllowedImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        const type = String(file?.type || '').toLowerCase();
        return allowedTypes.includes(type) || (type === '' && allowedExtensions.includes(this.imageFileExtension(file)));
    },
    isFileDrag(event) {
        return Array.from(event?.dataTransfer?.types || []).includes('Files');
    },
    filesFromTransfer(dataTransfer) {
        const transfer = dataTransfer || {};
        const fromItems = Array.from(transfer.items || [])
            .filter(item => item.kind === 'file')
            .map(item => item.getAsFile())
            .filter(Boolean);

        return fromItems.length ? fromItems : Array.from(transfer.files || []);
    },
    startDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.dragDepth += 1;
        this.dragging = true;
    },
    endDrag(event) {
        if (!this.isFileDrag(event)) return;
        this.dragDepth = Math.max(0, this.dragDepth - 1);
        this.dragging = this.dragDepth > 0;
    },
    drop(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dragDepth = 0;
        this.dragging = false;
        this.upload(this.filesFromTransfer(event.dataTransfer));
    },
    url(page = 1) {
        const url = new URL(this.indexUrl, window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('per_page', Math.max(6, Math.min(48, Number(this.perPage || 24))));
        if (String(this.scope || '').trim()) {
            url.searchParams.set('scope', String(this.scope || '').trim());
        }
        if (String(this.search || '').trim()) {
            url.searchParams.set('q', String(this.search || '').trim());
        }
        return url.toString();
    },
    async load(append = false) {
        if (this.loading) return;
        if (append && !this.hasMore) return;

        const nextPage = append ? this.page + 1 : 1;
        const requestSearch = String(this.search || '').trim();
        this.loading = true;
        this.error = '';

        if (!append) {
            this.page = 1;
            this.hasMore = false;
        }

        try {
            const response = await fetch(this.url(nextPage), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Image gallery could not be loaded.');

            const payload = await response.json();
            const freshItems = (Array.isArray(payload.data) ? payload.data : []).filter(item => String(item?.url || '').trim() && !String(item.url || '').includes('product-placeholder.svg'));

            if (requestSearch !== String(this.search || '').trim()) return;

            if (append) {
                const knownIds = new Set(this.items.map(item => Number(item.id)));
                this.items = [...this.items, ...freshItems.filter(item => !knownIds.has(Number(item.id)))];
            } else {
                this.items = freshItems;
            }

            this.page = Number(payload.meta?.current_page || nextPage);
            this.hasMore = Boolean(payload.meta?.has_more);
        } catch (error) {
            this.error = error instanceof Error ? error.message : 'Image gallery could not be loaded.';
        } finally {
            this.loading = false;
        }
    },
    handleScroll(event) {
        const scroller = event?.target;
        if (!scroller || this.loading || !this.hasMore || !this.items.length) return;

        const nearBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 260;
        if (nearBottom) this.load(true);
    },
    fillScrollableArea() {
        this.$nextTick(() => {
            const scroller = this.$refs.scroller;
            if (!scroller || this.loading || !this.hasMore || !this.items.length) return;

            const canScroll = scroller.scrollHeight > scroller.clientHeight + 24;
            if (!canScroll) this.load(true);
        });
    },
    async upload(fileList) {
        const files = Array.from(fileList || []);
        if (!files.length) return;

        const invalid = files.find(file => !this.isAllowedImageFile(file) || file.size > 5 * 1024 * 1024);
        if (invalid || files.length > 20) {
            this.uploadError = 'Choose up to 20 JPG, PNG, WebP, or AVIF images, each no larger than 5 MB.';
            return;
        }

        const formData = new FormData();
        files.forEach(file => formData.append('images[]', file));
        this.uploadBusy = true;
        this.uploadError = '';

        try {
            const response = await fetch(this.storeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                credentials: 'same-origin',
                body: formData,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message || Object.values(payload.errors || {})?.flat?.()?.[0] || 'Images could not be uploaded to the gallery.';
                throw new Error(message);
            }

            const freshItems = (Array.isArray(payload.data) ? payload.data : []).filter(item => String(item?.url || '').trim() && !String(item.url || '').includes('product-placeholder.svg'));
            const knownIds = new Set(this.items.map(item => Number(item.id)));
            this.items = [...freshItems.filter(item => !knownIds.has(Number(item.id))), ...this.items];
            this.totalImages += freshItems.length;
        } catch (error) {
            this.uploadError = error instanceof Error ? error.message : 'Images could not be uploaded to the gallery.';
        } finally {
            this.uploadBusy = false;
            if (this.$refs.uploadInput) this.$refs.uploadInput.value = '';
        }
    },
});

const preserveAdminSidebarPosition = () => {
    const sidebarNav = document.querySelector('[data-admin-sidebar-nav]');
    if (!sidebarNav) return;

    const storageKey = 'nextplay.admin.sidebar.scrollTop';
    const savedScroll = Number(sessionStorage.getItem(storageKey) || 0);

    if (savedScroll > 0) {
        sidebarNav.scrollTop = savedScroll;
    }

    requestAnimationFrame(() => {
        if (savedScroll > 0) {
            sidebarNav.scrollTop = savedScroll;
            return;
        }

        const activeLink = sidebarNav.querySelector('[data-sidebar-active="true"]');
        if (activeLink) {
            activeLink.scrollIntoView({ block: 'center', inline: 'nearest' });
        }
    });

    const persistScroll = () => {
        sessionStorage.setItem(storageKey, String(sidebarNav.scrollTop));
    };

    let saveTimer = null;
    const saveScroll = () => {
        if (saveTimer) window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(persistScroll, 80);
    };

    sidebarNav.addEventListener('scroll', saveScroll, { passive: true });
    sidebarNav.addEventListener('pointerdown', (event) => {
        if (event.target.closest('a')) {
            persistScroll();
        }
    }, true);
    sidebarNav.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            persistScroll();
        }
    }, true);
    window.addEventListener('pagehide', persistScroll);
};

preserveAdminSidebarPosition();


Alpine.start();

const initializeResizableAdminSidebar = () => {
    const sidebar = document.getElementById('admin-sidebar');
    const handle = document.querySelector('[data-admin-sidebar-resizer]');
    if (!sidebar || !handle) return;

    const storageKey = 'nextplay.admin.sidebar.width';
    const root = document.documentElement;
    const minWidth = Number(handle.getAttribute('aria-valuemin')) || 220;
    const maxWidth = Number(handle.getAttribute('aria-valuemax')) || 380;
    const defaultWidth = 256;

    const clampWidth = (value) => Math.min(maxWidth, Math.max(minWidth, Math.round(value)));
    const applyWidth = (value, persist = false) => {
        const width = clampWidth(value);
        root.style.setProperty('--admin-sidebar-width', `${width}px`);
        handle.setAttribute('aria-valuenow', String(width));
        if (persist) {
            localStorage.setItem(storageKey, String(width));
        }
        return width;
    };

    const savedWidth = Number(localStorage.getItem(storageKey));
    if (Number.isFinite(savedWidth) && savedWidth > 0) {
        applyWidth(savedWidth);
    }

    let isDragging = false;
    let sidebarLeft = 0;

    const finishDragging = (event) => {
        if (!isDragging) return;
        isDragging = false;
        document.body.classList.remove('admin-sidebar-resizing');
        try {
            handle.releasePointerCapture(event.pointerId);
        } catch (_) {}
        const currentWidth = Number.parseInt(getComputedStyle(root).getPropertyValue('--admin-sidebar-width'), 10) || defaultWidth;
        applyWidth(currentWidth, true);
    };

    const dragSidebar = (event) => {
        if (!isDragging) return;
        event.preventDefault();
        applyWidth(event.clientX - sidebarLeft);
    };

    handle.addEventListener('pointerdown', (event) => {
        if (window.matchMedia('(max-width: 1023px)').matches) return;
        event.preventDefault();
        isDragging = true;
        sidebarLeft = sidebar.getBoundingClientRect().left;
        document.body.classList.add('admin-sidebar-resizing');
        try {
            handle.setPointerCapture(event.pointerId);
        } catch (_) {}
    });

    handle.addEventListener('pointermove', dragSidebar);
    handle.addEventListener('pointerup', finishDragging);
    handle.addEventListener('pointercancel', finishDragging);
    handle.addEventListener('dblclick', () => applyWidth(defaultWidth, true));
    handle.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const currentWidth = Number.parseInt(getComputedStyle(root).getPropertyValue('--admin-sidebar-width'), 10) || defaultWidth;
        if (event.key === 'Home') {
            applyWidth(minWidth, true);
            return;
        }
        if (event.key === 'End') {
            applyWidth(maxWidth, true);
            return;
        }
        applyWidth(currentWidth + (event.key === 'ArrowRight' ? 16 : -16), true);
    });

    window.addEventListener('resize', () => {
        const currentWidth = Number.parseInt(getComputedStyle(root).getPropertyValue('--admin-sidebar-width'), 10) || defaultWidth;
        applyWidth(currentWidth, true);
    });
};

const initializeAdminSidebarTooltips = () => {
    const sidebar = document.getElementById('admin-sidebar');
    if (!sidebar) return;

    let tooltip = null;
    let activeTarget = null;
    let showTimer = null;

    const ensureTooltip = () => {
        if (tooltip) return tooltip;

        tooltip = document.createElement('div');
        tooltip.className = 'admin-sidebar-floating-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-hidden', 'true');
        document.body.appendChild(tooltip);

        return tooltip;
    };

    const tooltipTargetFromEvent = (event) => {
        const target = event.target instanceof Element
            ? event.target.closest('[data-sidebar-tooltip]')
            : null;

        return target && sidebar.contains(target) ? target : null;
    };

    const getLabel = (target) => target.querySelector('[data-sidebar-label], .truncate');

    const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();

    const isLabelTruncated = (target) => {
        const label = getLabel(target);
        const text = normalizeText(target.getAttribute('data-sidebar-tooltip'));

        if (!text) return false;
        if (!label) return text.length > 18;

        const measuredAsTruncated = label.scrollWidth > label.clientWidth + 2;
        const targetIsNarrow = target.getBoundingClientRect().width < 300;
        const longLabel = text.length >= 18;

        // Show the tooltip when the label is actually truncated. The fallback
        // keeps the full label readable when Tailwind/browser rounding reports
        // equal widths even though the visible text has ellipsis.
        return measuredAsTruncated || (targetIsNarrow && longLabel);
    };

    const positionTooltip = (target) => {
        if (!tooltip) return;

        const targetRect = target.getBoundingClientRect();
        const label = getLabel(target);
        const anchorRect = label ? label.getBoundingClientRect() : targetRect;
        const tooltipRect = tooltip.getBoundingClientRect();
        const gap = 14;
        let onLeft = false;
        let left = Math.round(targetRect.right + gap);

        if (left + tooltipRect.width > window.innerWidth - 12) {
            left = Math.max(12, Math.round(targetRect.left - tooltipRect.width - gap));
            onLeft = true;
        }

        let top = Math.round(anchorRect.top + anchorRect.height / 2);
        const minTop = tooltipRect.height / 2 + 12;
        const maxTop = window.innerHeight - tooltipRect.height / 2 - 12;
        top = Math.min(maxTop, Math.max(minTop, top));

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
        tooltip.classList.toggle('is-on-left', onLeft);
    };

    const showTooltip = (target) => {
        const text = normalizeText(target.getAttribute('data-sidebar-tooltip'));
        if (!text || !isLabelTruncated(target)) return;

        activeTarget = target;
        const floatingTooltip = ensureTooltip();
        floatingTooltip.textContent = text;
        floatingTooltip.setAttribute('aria-hidden', 'false');
        floatingTooltip.classList.add('is-visible');
        requestAnimationFrame(() => positionTooltip(target));
    };

    const hideTooltip = () => {
        activeTarget = null;

        if (showTimer) {
            window.clearTimeout(showTimer);
            showTimer = null;
        }

        if (tooltip) {
            tooltip.classList.remove('is-visible', 'is-on-left');
            tooltip.setAttribute('aria-hidden', 'true');
        }
    };

    const scheduleTooltip = (target) => {
        if (activeTarget === target) return;

        hideTooltip();
        showTimer = window.setTimeout(() => {
            showTimer = null;
            showTooltip(target);
        }, 80);
    };

    sidebar.addEventListener('pointerover', (event) => {
        const target = tooltipTargetFromEvent(event);
        if (!target) return;

        const related = event.relatedTarget;
        if (related instanceof Node && target.contains(related)) return;

        scheduleTooltip(target);
    });

    sidebar.addEventListener('pointerout', (event) => {
        const target = tooltipTargetFromEvent(event);
        if (!target) return;

        const related = event.relatedTarget;
        if (related instanceof Node && target.contains(related)) return;

        hideTooltip();
    });

    sidebar.addEventListener('focusin', (event) => {
        const target = tooltipTargetFromEvent(event);
        if (target) showTooltip(target);
    });

    sidebar.addEventListener('focusout', hideTooltip);
    sidebar.addEventListener('scroll', hideTooltip, true);

    window.addEventListener('resize', () => {
        if (activeTarget) positionTooltip(activeTarget);
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideTooltip();
    });
};

initializeResizableAdminSidebar();
initializeAdminSidebarTooltips();
