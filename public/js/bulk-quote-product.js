/* Bulk quote product picker. No framework or catalog-wide query on initial load. */
(() => {
    'use strict';
    const root = document.getElementById('bulk-quote-product-picker');
    if (!root) return;

    const byId = id => document.getElementById(id);
    const search = byId('bulk-quote-product-search');
    const results = byId('bulk-quote-product-results');
    const feedback = byId('bulk-quote-product-feedback');
    const hiddenId = byId('bulk-quote-selected-product-id');
    const selectedCard = byId('bulk-quote-selected-product');
    const fields = byId('bulk-quote-product-fields');
    const optionsHolder = byId('bulk-quote-product-options');
    const sizesHolder = byId('bulk-quote-product-sizes');
    const bottom = byId('bulk-quote-product-bottom');
    const notes = byId('bulk-quote-product-notes');
    const productType = byId('bulk-product-type');
    const totalQuantity = byId('bulk-quantity');
    const sizesNeeded = byId('bulk-sizes');
    let searchTimeout;
    let searchController;
    let detailsController;
    let requestVersion = 0;
    let lastAutoProductType = '';
    let lastAutoSizes = '';
    let lastAutoQuantity = '';
    let currentProduct = null;

    function element(tag, className = '', text = null) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== null) node.textContent = String(text);
        return node;
    }
    function input(type, name, value = '') {
        const node = document.createElement('input');
        node.type = type;
        node.name = name;
        node.value = String(value);
        return node;
    }
    function message(value, error = false) {
        feedback.textContent = value;
        feedback.hidden = !value;
        feedback.classList.toggle('is-error', error);
    }
    function hideResults() {
        results.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        results.replaceChildren();
    }
    function searchReset() {
        clearTimeout(searchTimeout);
        searchController?.abort();
        detailsController?.abort();
        requestVersion += 1;
        hideResults();
    }
    function makeResult(product) {
        const button = element('button', 'bulk-quote-product-result');
        button.type = 'button';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', 'false');
        const image = element('img');
        image.src = product.image;
        image.alt = '';
        image.loading = 'lazy';
        image.addEventListener('error', () => { image.src = '/images/product-placeholder.svg'; }, { once: true });
        const text = element('span');
        text.append(element('strong', '', product.name), element('small', '', product.sku ? `SKU: ${product.sku}` : ''));
        button.append(image, text);
        button.addEventListener('click', () => selectProduct(product.id));
        return button;
    }
    async function searchProducts(query) {
        searchController?.abort();
        const controller = new AbortController();
        searchController = controller;
        try {
            const url = new URL(root.dataset.searchUrl, location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Search unavailable');
            const data = await response.json();
            if (controller.signal.aborted || search.value.trim() !== query) return;
            results.replaceChildren();
            if (!Array.isArray(data.products) || !data.products.length) {
                results.append(element('p', 'bulk-quote-product-empty', 'No matching products found. You can still request a quote below.'));
            } else {
                data.products.forEach(product => results.append(makeResult(product)));
            }
            results.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            message('');
        } catch (error) {
            if (error.name !== 'AbortError') {
                hideResults();
                message('Product search is temporarily unavailable. Please try again, or complete the quote without a product.', true);
            }
        }
    }
    search.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchController?.abort();
        hideResults();
        message('');
        const value = search.value.trim();
        if (value.length >= 2) searchTimeout = setTimeout(() => searchProducts(value), 280);
    });
    search.addEventListener('keydown', event => {
        if (event.key === 'Escape') hideResults();
        if (event.key === 'ArrowDown' && !results.hidden) {
            const first = results.querySelector('button');
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    results.addEventListener('keydown', event => {
        const buttons = [...results.querySelectorAll('button')];
        const index = buttons.indexOf(document.activeElement);
        if (event.key === 'Escape') { hideResults(); search.focus(); }
        if (event.key === 'ArrowDown' && buttons[index + 1]) { event.preventDefault(); buttons[index + 1].focus(); }
        if (event.key === 'ArrowUp') { event.preventDefault(); (buttons[index - 1] || search).focus(); }
    });
    document.addEventListener('click', event => {
        if (!root.contains(event.target)) hideResults();
    });

    async function selectProduct(id) {
        searchReset();
        const version = requestVersion;
        detailsController = new AbortController();
        message('Loading product options…');
        try {
            const url = root.dataset.detailUrl.replace('__ID__', encodeURIComponent(String(id)));
            const response = await fetch(url, { signal: detailsController.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Cannot load product');
            const data = await response.json();
            if (version !== requestVersion) return;
            if (!data.product) throw new Error('Missing product');
            renderProduct(data.product);
            message('');
        } catch (error) {
            if (error.name !== 'AbortError') {
                message('This product could not be loaded. Please try another product.', true);
            }
        }
    }
    function resetProduct(restoreAutofilled = true) {
        searchReset();
        currentProduct = null;
        hiddenId.disabled = true;
        hiddenId.value = '';
        selectedCard.hidden = true;
        fields.hidden = true;
        optionsHolder.replaceChildren();
        sizesHolder.replaceChildren();
        notes.value = '';
        notes.disabled = true;
        byId('bulk-quote-product-notes-count').textContent = '0';
        if (restoreAutofilled && productType && productType.value === lastAutoProductType) productType.value = '';
        lastAutoProductType = '';
        if (restoreAutofilled && sizesNeeded && sizesNeeded.value === lastAutoSizes) sizesNeeded.value = '';
        if (restoreAutofilled && totalQuantity && totalQuantity.value === lastAutoQuantity) totalQuantity.value = '';
        lastAutoSizes = '';
        lastAutoQuantity = '';
        message('');
    }
    byId('bulk-quote-product-clear').addEventListener('click', () => {
        resetProduct();
        search.value = '';
        search.focus();
    });
    byId('bulk-quote-product-change').addEventListener('click', () => {
        search.value = '';
        search.focus();
        message('Search for a different product, or use Clear to remove this one.');
    });
    notes.addEventListener('input', () => { byId('bulk-quote-product-notes-count').textContent = notes.value.length; });

    function optionField(group, oldOptions) {
        const field = element('div', 'bulk-quote-field bulk-quote-product-option-field');
        const label = element('label', '', group.label);
        const name = `product_information[options][${group.id}]`;
        const prior = oldOptions && Object.prototype.hasOwnProperty.call(oldOptions, group.id)
            ? oldOptions[group.id] : undefined;
        const values = Array.isArray(group.values) ? group.values : [];
        const type = group.type || 'select';
        if (group.fixed && type !== 'checkbox') {
            label.htmlFor = `bq-option-${group.id}`;
            const text = values.find(value => String(value.id) === String(group.default))?.label || group.default || 'Not specified';
            const display = element('div', 'bulk-quote-product-fixed-option', text);
            const fixedInput = input('hidden', name, group.default || '');
            field.append(label, display, fixedInput);
            return field;
        }
        const colorMode = (type === 'swatch' || /colou?r/i.test(group.label)) && values.some(v => v.color);

        if (colorMode) {
            const legend = element('span', 'bulk-quote-product-field-label', group.label);
            const swatches = element('div', 'bulk-quote-color-options');
            swatches.setAttribute('role', 'radiogroup');
            swatches.setAttribute('aria-label', group.label);
            const current = prior === undefined ? group.default : prior;
            values.forEach(value => {
                const item = element('label', 'bulk-quote-color-choice');
                const radio = input('radio', name, value.id);
                radio.checked = String(current) === String(value.id);
                radio.setAttribute('aria-label', value.label);
                const circle = element('span', 'bulk-quote-color-chip');
                if (value.color) circle.style.backgroundColor = value.color;
                else circle.textContent = value.label.slice(0, 2);
                item.title = value.label;
                item.append(radio, circle);
                swatches.append(item);
            });
            field.append(legend, swatches);
        } else if (type === 'checkbox') {
            field.append(label);
            const choices = element('div', 'bulk-quote-product-checkboxes');
            values.forEach(value => {
                const item = element('label', 'bulk-quote-product-check');
                const checkbox = input('checkbox', `${name}[]`, value.id);
                checkbox.checked = Array.isArray(prior) ? prior.map(String).includes(String(value.id)) : !!value.default;
                item.append(checkbox, element('span', '', value.label));
                choices.append(item);
            });
            field.append(choices);
        } else if (['image', 'swatch', 'buttons', 'select'].includes(type)) {
            label.htmlFor = `bq-option-${group.id}`;
            const select = element('select');
            select.id = label.htmlFor;
            select.name = name;
            const placeholder = element('option', '', `Select ${group.label}`);
            placeholder.value = '';
            select.append(placeholder);
            const current = prior === undefined ? group.default : prior;
            values.forEach(value => {
                const item = element('option', '', value.label);
                item.value = String(value.id);
                item.selected = String(current) === String(value.id);
                select.append(item);
            });
            field.append(label, select);
        } else {
            label.htmlFor = `bq-option-${group.id}`;
            const control = type === 'textarea' ? element('textarea') : input(type === 'number' ? 'number' : type === 'date' ? 'date' : 'text', name);
            control.name = name;
            control.id = label.htmlFor;
            control.maxLength = 500;
            if (type !== 'textarea') control.setAttribute('maxlength', '500');
            control.placeholder = group.placeholder || `Enter ${group.label.toLowerCase()} (optional)`;
            control.value = String(prior === undefined ? (group.default || '') : prior);
            field.append(label, control);
        }
        return field;
    }
    function sizesField(group, oldSizes) {
        const block = element('div', 'bulk-quote-product-size-group');
        const caption = element('div', 'bulk-quote-product-field-label', 'Size & Quantity');
        if (currentProduct.size_groups.length > 1) caption.append(element('small', '', ` — ${group.label}`));
        block.append(caption);
        const grid = element('div', 'bulk-quote-size-quantities');
        group.sizes.forEach(size => {
            const field = element('label', 'bulk-quote-size-quantity');
            field.append(element('span', '', size.label));
            const qty = input('number', `product_information[sizes][${group.id}][${size.id}]`,
                oldSizes?.[group.id]?.[size.id] || '0');
            qty.min = '0';
            qty.max = '99999';
            qty.step = '1';
            qty.inputMode = 'numeric';
            qty.setAttribute('aria-label', `${group.label} ${size.label} quantity`);
            qty.addEventListener('input', updateOrderFields);
            field.append(qty);
            grid.append(field);
        });
        block.append(grid);
        return block;
    }
    function updateOrderFields() {
        if (!currentProduct) return;
        let count = 0;
        const usedSizes = [];
        sizesHolder.querySelectorAll('input[type="number"]').forEach(node => {
            const amount = Number(node.value);
            if (!Number.isInteger(amount) || amount <= 0) return;
            count += amount;
            const size = node.closest('label')?.querySelector('span')?.textContent;
            if (size && !usedSizes.includes(size)) usedSizes.push(size);
        });
        if (sizesNeeded && (!sizesNeeded.value || sizesNeeded.value === lastAutoSizes)) {
            lastAutoSizes = usedSizes.join(', ');
            sizesNeeded.value = lastAutoSizes;
        }
        if (totalQuantity && count >= 10 && (!totalQuantity.value || totalQuantity.value === lastAutoQuantity)) {
            const value = count < 50 ? '10-49' : count < 100 ? '50-99'
                : count < 500 ? '100-499' : count < 1000 ? '500-999' : '1000-plus';
            lastAutoQuantity = value;
            totalQuantity.value = value;
        } else if (totalQuantity && count < 10 && totalQuantity.value === lastAutoQuantity) {
            lastAutoQuantity = '';
            totalQuantity.value = '';
        }
    }
    function renderProduct(product, previous = {}) {
        currentProduct = product;
        hiddenId.disabled = false;
        hiddenId.value = product.id;
        byId('bulk-quote-selected-image').src = product.image;
        byId('bulk-quote-selected-image').alt = product.name;
        byId('bulk-quote-selected-name').textContent = product.name;
        byId('bulk-quote-selected-sku').textContent = product.sku ? `SKU: ${product.sku}` : '';
        byId('bulk-quote-selected-summary').textContent = product.summary || '';
        selectedCard.hidden = false;
        search.value = '';
        hideResults();
        optionsHolder.replaceChildren();
        sizesHolder.replaceChildren();
        (product.groups || []).forEach(group => optionsHolder.append(optionField(group, previous.options || {})));
        (product.size_groups || []).forEach(group => sizesHolder.append(sizesField(group, previous.sizes || {})));
        bottom.classList.toggle('is-notes-only', !(product.size_groups || []).length);
        fields.hidden = false;
        notes.disabled = false;
        notes.value = String(previous.notes || '').slice(0, 500);
        byId('bulk-quote-product-notes-count').textContent = notes.value.length;
        if (productType && (!productType.value || productType.value === lastAutoProductType)) {
            lastAutoProductType = product.name.slice(0, 190);
            productType.value = lastAutoProductType;
        }
        if ((product.size_groups || []).length) updateOrderFields();
    }

    try {
        const initial = JSON.parse(byId('bulk-quote-product-initial').textContent);
        if (initial.product) renderProduct(initial.product, initial.selection || {});
    } catch (error) {
        // A missing or unavailable product must not block the rest of the quote form.
        message('Product options could not be restored. Please search for the product again.', true);
    }
})();
