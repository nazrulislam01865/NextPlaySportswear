/* NextPlay quote builder: catalogue options are loaded only on demand. */
(() => {
    'use strict';
    const form = document.getElementById('bulk-quote-request-form');
    if (!form) return;
    const el = id => document.getElementById(id);
    const state = JSON.parse(el('npq-state')?.textContent || '{}');
    const products = Array.isArray(state.products) ? state.products.filter(item => item && typeof item === 'object').slice(0, 20) : [];
    const choices = [...form.querySelectorAll('[name="npq_choice[]"]')];
    const newNames = ['name', 'description', 'material', 'colors', 'sizes', 'quantity', 'budget', 'customization'];
    const text = value => String(value ?? '');
    const make = (tag, cls = '', value) => { const element = document.createElement(tag); element.className = cls; if (value !== undefined) element.textContent = text(value); return element; };
    const safeImage = url => typeof url === 'string' && (/^https?:\/\//i.test(url) || url.startsWith('/')) ? url : '';
    const placeholder = () => document.body.dataset.imagePlaceholderUrl || '/images/product-placeholder.svg';
    let draft = {mode: 'existing', catalog: null, artwork: null};
    // Keep artwork separate for the two editors; never reuse a file across tabs.
    let artworkByTab = {existing: null, new: null};
    let editing = -1;
    let searchController = null, searchTimer = null;
    const objectUrls = new Map();
    function previewUrl(file) {
        if (!file || !/^image\/(png|jpeg|webp|gif)$/i.test(file.type)) return '';
        if (!objectUrls.has(file)) objectUrls.set(file, URL.createObjectURL(file));
        return objectUrls.get(file);
    }
    function error(name, message = '') {
        const target = form.querySelector(`[data-error="${name}"]`);
        if (target) { target.textContent = message; target.hidden = !message; }
        const field = el(`npq-${name}`);
        if (field) { if (message) field.setAttribute('aria-invalid', 'true'); else field.removeAttribute('aria-invalid'); }
    }
    const field = name => el(`npq-${name}`);
    function counts() {
        for (const name of ['description', 'customization']) el(`npq-${name}-count`).textContent = field(name).value.length;
        el('npq-existing-customization-count').textContent = el('npq-existing-customization').value.length;
    }
    function mode(name) {
        draft.mode = name;
        const existing = name === 'existing';
        el('npq-existing-fields').hidden = !existing;
        el('npq-new-fields').hidden = existing;
        for (const name of ['existing', 'new']) {
            const active = name === (existing ? 'existing' : 'new');
            el(`npq-${name}-tab`).classList.toggle('is-active', active);
            el(`npq-${name}-tab`).setAttribute('aria-selected', String(active));
        }
        for (const name of ['material', 'colors', 'sizes', 'quantity']) field(name).required = !existing;
        // The inline explanation remains available to screen readers but is not an extra layout row.
        el('npq-mode-help').textContent = existing ? 'Select a catalogue product and configure its options.' : 'Describe the product you need.';
    }
    function status() {
        el('npq-draft-status').querySelector('.npq-status-pill').textContent = editing < 0 ? '●  Not added yet' : '●  Editing product';
        el('npq-add').firstChild.textContent = editing < 0 ? 'Add Product to Request ' : 'Save Product Changes ';
    }
    function showArtwork() {
        for (const prefix of ['', 'existing-']) {
            const visible = (!prefix && draft.mode === 'new') || (prefix && draft.mode === 'existing');
            const container = el(`npq-${prefix}file-preview`);
            const image = el(`npq-${prefix}image-preview`);
            const fileName = draft.artwork?.name || draft.artwork_name || '';
            container.hidden = !visible || !fileName;
            el(`npq-${prefix}file-name`).textContent = fileName;
            el(`npq-${prefix}file-size`).textContent = draft.artwork ? `${Math.ceil(draft.artwork.size / 1024)} KB` : 'Previously selected';
            const src = previewUrl(draft.artwork);
            if (src) image.src = src;
            image.hidden = !src;
        }
    }
    function lookupValues(group) {
        const control = [...el('npq-catalog-options').querySelectorAll('[data-group]')]
            .find(input => input.dataset.group === text(group.id));
        if (!control) return '';
        if (control.tagName === 'INPUT') return control.value.trim();
        const selected = control.multiple ? [...control.selectedOptions] : [control.selectedOptions[0]].filter(Boolean);
        return selected.filter(option => option.value).map(option => option.textContent.trim()).join(', ');
    }
    function labelFor(pattern) {
        return (draft.catalog?.groups || []).find(group => pattern.test(group.label || ''));
    }
    function populateCatalog(product) {
        const selection = el('npq-catalog-selection');
        const options = el('npq-catalog-options');
        const sizes = el('npq-catalog-sizes');
        selection.replaceChildren(); options.replaceChildren(); sizes.replaceChildren();
        el('npq-existing-config').hidden = !product;
        selection.hidden = !product;
        if (!product) return;
        const image = make('img'); image.src = safeImage(product.image) || placeholder(); image.alt = product.name;
        const caption = make('div', 'npq-selection-copy');
        caption.append(make('strong', '', product.name), make('small', '', product.sku ? `SKU: ${product.sku}` : 'Catalogue product'));
        if (product.summary) caption.append(make('p', '', product.summary));
        const change = make('button', 'npq-selection-change', '⇄  Change');
        change.type = 'button'; change.addEventListener('click', () => {
            draft.catalog = null; draft.options = {}; draft.sizeQuantities = {};
            populateCatalog(null); field('catalog-search').focus();
        });
        selection.append(image, caption, change);
        const groups = product.groups || [];
        groups.forEach(group => {
            const wrap = make('div', 'npq-field npq-catalog-option');
            const label = make('label', '', group.label);
            const freeText = !['image','swatch','buttons','select','checkbox'].includes(group.type) || !(group.values || []).length;
            const input = make(freeText ? 'input' : 'select'); input.dataset.group = text(group.id);
            input.id = `npq-group-${text(group.id).replace(/[^\w-]/g, '-')}`;
            label.htmlFor = input.id;
            const requiredType = /fabric|material|colou?r/i.test(group.label || '');
            if (requiredType) label.append(make('span', 'npq-required', ' *'));
            if (!freeText) {
                const empty = make('option', '', `Select ${group.label}`); empty.value = ''; input.append(empty);
                (group.values || []).forEach(option => {
                    const item = make('option', '', option.label); item.value = text(option.id);
                    if (safeImage(option.image)) item.dataset.iconUrl = safeImage(option.image);
                    if (/^#[a-f0-9]{6}$/i.test(option.color || '')) item.dataset.color = option.color;
                    input.append(item);
                });
                if (group.type === 'checkbox') input.multiple = true;
                if (group.type === 'checkbox') {
                    const previous = draft.options?.[group.id] || [];
                    [...input.options].forEach(option => option.selected = Array.isArray(previous) && previous.map(String).includes(option.value));
                } else input.value = text(draft.options?.[group.id] ?? group.default ?? '');
            } else {
                input.type = group.type === 'number' ? 'number' : group.type === 'date' ? 'date' : 'text';
                input.maxLength = group.type === 'text' ? 500 : 190;
                input.value = text(draft.options?.[group.id] ?? group.default ?? '');
                input.placeholder = `Enter ${group.label.toLowerCase()}`;
            }
            if (group.fixed && input.value) { input.setAttribute('aria-readonly', 'true'); input.dataset.fixed = 'true'; }
            if (group.type === 'swatch' && !freeText) {
                const color = make('span', 'npq-color-swatch');
                const refresh = () => {
                    const option = (group.values || []).find(value => text(value.id) === input.value);
                    color.style.backgroundColor = /^#[a-f0-9]{6}$/i.test(option?.color || '') ? option.color : 'transparent';
                    color.hidden = !option?.color;
                };
                input.addEventListener('change', refresh);
                wrap.classList.add('npq-color-option'); wrap.append(label, color, input); refresh();
            } else wrap.append(label, input);
            const groupError = make('span', 'npq-error');
            groupError.hidden = true;
            groupError.id = `${input.id}-error`;
            wrap.append(groupError);
            input.addEventListener('change', () => {
                groupError.textContent = '';
                groupError.hidden = true;
                input.removeAttribute('aria-invalid');
            });
            options.append(wrap);
        });
        // Only the selected product's configured options belong in this editor.
        // Budget and generic fabric/colour inputs belong to Product not listed,
        // not to a catalogue item which does not offer those choices.
        const sizeGroups = (product.size_groups || []).filter(group => (group.sizes || []).length);
        const sizeHeading = el('npq-existing-size-heading');
        if (sizeGroups.length) {
            sizeHeading.replaceChildren('Size / dimensions ', make('span', 'npq-required', '*'), ' & quantity ', make('span', 'npq-required', '*'));
            const table = make('table', 'npq-size-table');
            const thead = make('thead'), heading = make('tr');
            heading.append(make('th', '', 'Size'), make('th', '', 'Quantity')); thead.append(heading);
            const tbody = make('tbody');
            sizeGroups.forEach(group => (group.sizes || []).forEach(size => {
                const row = make('tr'); row.append(make('td', '', `${size.label}${sizeGroups.length > 1 ? ` (${group.label})` : ''}`));
                const qtyCell = make('td'); const qty = make('input');
                qty.type = 'number'; qty.min = 0; qty.max = 99999; qty.step = 1; qty.inputMode = 'numeric';
                qty.dataset.sizeGroup = text(group.id); qty.dataset.size = text(size.id);
                qty.setAttribute('aria-label', `Quantity for ${size.label}`);
                qty.value = text(draft.sizeQuantities?.[group.id]?.[size.id] || '0');
                qty.addEventListener('input', updateTotal); qtyCell.append(qty); row.append(qtyCell); tbody.append(row);
            }));
            const tfoot = make('tfoot'), totalRow = make('tr');
            totalRow.append(make('th', '', 'Total'), make('th', 'npq-size-total', '0 pieces'));
            tfoot.append(totalRow); table.append(thead, tbody, tfoot); sizes.append(table);
        } else {
            // Quantity is needed for every quote. Do not invent a size/dimensions
            // option when the selected product has no configured size group.
            sizeHeading.replaceChildren('Quantity ', make('span', 'npq-required', '*'));
            const qty = make('input'); qty.id = 'npq-existing-quantity';
            qty.type = 'number'; qty.min = 1; qty.max = 99999; qty.step = 1;
            qty.inputMode = 'numeric'; qty.placeholder = 'Enter quantity';
            qty.value = text(draft.quantity); qty.setAttribute('aria-label', 'Quantity');
            qty.addEventListener('input', updateTotal); sizes.append(qty);
        }
        updateTotal();
    }
    function updateTotal() {
        const inputs = [...el('npq-catalog-sizes').querySelectorAll('input[data-size-group]')];
        const total = inputs.length ? inputs.reduce((sum, input) => sum + (Number(input.value) || 0), 0)
            : Number(el('npq-existing-quantity')?.value || 0);
        const output = el('npq-catalog-sizes').querySelector('.npq-size-total');
        if (output) output.textContent = `${total.toLocaleString('en-GB')} pieces`;
        return total;
    }
    function clearEditor() {
        editing = -1; draft = {mode: 'existing', catalog: null, artwork: null};
        artworkByTab = {existing: null, new: null};
        newNames.forEach(name => { field(name).value = ''; error(name); });
        choices.forEach(choice => choice.checked = false);
        field('catalog-search').value = ''; field('catalog-search').placeholder = 'Search for a product by name or SKU';
        el('npq-search-results').replaceChildren(); el('npq-search-results').hidden = true;
        field('artwork').value = ''; el('npq-existing-artwork').value = '';
        el('npq-existing-customization').value = '';
        ['catalog','existing-sizes','artwork'].forEach(name => error(name));
        populateCatalog(null); mode('existing'); showArtwork(); counts(); status();
    }
    function readEditor() {
        const product = {mode: draft.mode};
        if (draft.mode === 'new') {
            newNames.forEach(name => product[name] = field(name).value.trim());
            product.customizations = choices.filter(input => input.checked).map(input => input.value);
        } else {
            product.catalog = draft.catalog;
            product.catalog_product_id = Number(draft.catalog?.id || 0);
            product.name = text(draft.catalog?.name);
            product.description = '';
            product.customization = el('npq-existing-customization').value.trim();
            product.customizations = [];
            product.budget = ''; // No generic budget control for a catalogue item.
            product.options = {};
            el('npq-catalog-options').querySelectorAll('[data-group]').forEach(select => {
                const value = select.tagName === 'SELECT' && select.multiple ? [...select.selectedOptions].filter(option => option.value).map(option => option.value) : select.value;
                if (Array.isArray(value) ? value.length : value) product.options[select.dataset.group] = value;
            });
            const materialGroup = labelFor(/fabric|material/i), colorGroup = labelFor(/colou?r/i);
            product.material = materialGroup ? lookupValues(materialGroup) : '';
            product.colors = colorGroup ? lookupValues(colorGroup) : '';
            product.sizeQuantities = {};
            const quantities = [...el('npq-catalog-sizes').querySelectorAll('[data-size-group]')];
            if (quantities.length) {
                quantities.forEach(input => (product.sizeQuantities[input.dataset.sizeGroup] ||= {})[input.dataset.size] = text(input.value || 0));
                product.quantity = text(updateTotal());
                product.sizes = (draft.catalog?.size_groups || []).flatMap(group => (group.sizes || [])
                    .filter(size => Number(product.sizeQuantities[group.id]?.[size.id]) > 0)
                    .map(size => `${size.label}: ${product.sizeQuantities[group.id][size.id]}`)).join('  |  ');
            } else {
                product.quantity = text(el('npq-existing-quantity')?.value || '');
                product.sizes = ''; // No invented dimensions without an assigned size chart.
            }
        }
        product.artwork = draft.artwork || null;
        product.artwork_name = draft.artwork?.name || draft.artwork_name || '';
        return product;
    }
    function validateProduct(product) {
        let first = null;
        function invalid(name, message, input) { error(name, message); if (!first) first = input || field(name); }
        if (product.mode === 'new') {
            if (product.name.length < 2) invalid('name', 'Enter the product name or type.');
            if (!product.description) invalid('description', 'Enter a product description.');
            for (const [name, label] of [['material','material or fabric'],['colors','colours'],['sizes','size or dimensions']]) {
                if (!product[name]) invalid(name, `Enter ${label}.`);
            }
        } else {
            if (!product.catalog_product_id) invalid('catalog', 'Select a product from the catalogue.', field('catalog-search'));
            // A catalogue product may not have fabric, colour or size options.
            // Validate only the product-specific groups actually configured for it.
            const selectedGroups = draft.catalog?.groups || [];
            for (const [pattern, value, label] of [[/fabric|material/i, product.material, 'material / fabric'], [/colou?r/i, product.colors, 'colours']]) {
                const group = selectedGroups.find(group => pattern.test(group.label || ''));
                if (group && !value) {
                    const input = [...el('npq-catalog-options').querySelectorAll('[data-group]')]
                        .find(control => control.dataset.group === text(group.id));
                    if (input) {
                        const groupError = el(`${input.id}-error`);
                        groupError.textContent = `Select ${label} for this product.`;
                        groupError.hidden = false;
                        input.setAttribute('aria-invalid', 'true');
                        input.setAttribute('aria-describedby', groupError.id);
                    }
                    if (!first) first = input || field('catalog-search');
                }
            }
            if ((draft.catalog?.size_groups || []).some(group => (group.sizes || []).length) && !product.sizes)
                invalid('existing-sizes', 'Enter a quantity for at least one configured size.', el('npq-catalog-sizes').querySelector('input'));
        }
        if (!/^\d+$/.test(product.quantity) || Number(product.quantity) < 1 || Number(product.quantity) > 99999) {
            invalid(product.mode === 'existing' ? 'existing-sizes' : 'quantity', 'Enter a valid quantity between 1 and 99,999.', product.mode === 'existing' ? el('npq-catalog-sizes').querySelector('input') : field('quantity'));
        }
        if (product.artwork && (product.artwork.size > 10 * 1024 * 1024 || !['image/png','image/jpeg','application/pdf'].includes(product.artwork.type))) {
            invalid('artwork', 'Choose a PDF, JPG or PNG file under 10 MB.', field(product.mode === 'existing' ? 'existing-artwork' : 'artwork'));
        }
        if (first) { first.focus(); first.scrollIntoView({behavior:'smooth', block:'center'}); }
        return !first;
    }
    function dataProduct(product) {
        const copy = {...product}; delete copy.artwork; delete copy.catalog;
        if (product.catalog) copy.catalog_product_id = Number(product.catalog.id);
        return copy;
    }
    function syncProducts() {
        el('npq-products-data').value = JSON.stringify(products.map(dataProduct));
        if (products.length) el('npq-products-error').hidden = true;
    }
    function renderCards() {
        const list = el('npq-added-list'); list.replaceChildren();
        const count = products.length;
        el('npq-added-count').textContent = count;
        el('npq-summary-count').textContent = count;
        el('npq-summary-quantity').textContent = products.reduce((sum, product) => sum + (Number(product.quantity) || 0), 0).toLocaleString('en-GB');
        if (!count) list.append(make('p', 'npq-empty', 'No products added yet. Complete the form on the left to add your first product.'));
        products.forEach((product, index) => {
            const card = make('article', 'npq-product-card');
            const head = make('div', 'npq-product-head');
            head.append(make('span', 'npq-number', index + 1), make('strong', 'npq-product-name', product.name));
            head.append(make('span', `npq-product-badge ${product.mode === 'existing' ? 'npq-catalog-badge' : 'npq-new-badge'}`, product.mode === 'existing' ? 'Catalogue product' : 'New product'));
            const toggle = make('button', 'npq-expand', '⌃'); toggle.type = 'button';
            toggle.setAttribute('aria-label', `Toggle details for ${product.name}`); toggle.setAttribute('aria-expanded','true'); head.append(toggle);
            const body = make('div', 'npq-product-body');
            const picture = make('img','npq-product-picture'); picture.alt = product.name; picture.loading = 'lazy';
            picture.src = previewUrl(product.artwork) || safeImage(product.catalog?.image) || safeImage(product.catalog_image) || placeholder();
            body.append(picture);
            const details = make('dl');
            const listed = [
                ['Quantity', `${product.quantity} pieces`],
                ['Sizes', product.sizes],
                ['Fabric', product.material],
                ['Customisation', product.customization || (product.customizations || []).map(v => ({'names-numbers':'Names & numbers','full-custom-design':'Full custom design'}[v] || v)).join(', ')],
                ['Artwork', product.artwork?.name || product.artwork_name],
            ];
            listed.filter(([, value]) => Boolean(value)).forEach(([label, value]) => {
                const dt = make('dt', '', label), dd = make('dd', '', value);
                if (label === 'Artwork') dd.classList.add('npq-artwork-label');
                details.append(dt, dd);
            });
            body.append(details);
            const actions = make('div','npq-card-actions');
            const edit = make('button','npq-edit','✎  Edit'); edit.type = 'button'; edit.addEventListener('click', () => loadProduct(product, index));
            const remove = make('button','npq-remove','▤  Remove'); remove.type = 'button'; remove.addEventListener('click', () => {
                products.splice(index, 1);
                if (editing === index) clearEditor(); else if (editing > index) editing--;
                renderCards();
            });
            actions.append(edit, remove); card.append(head, body, actions); list.append(card);
            toggle.addEventListener('click', () => {
                const collapsed = !body.hidden; body.hidden = collapsed; actions.hidden = collapsed;
                toggle.textContent = collapsed ? '⌄' : '⌃'; toggle.setAttribute('aria-expanded', String(!collapsed));
            });
        });
        syncProducts();
    }
    function loadProduct(product, index) {
        editing = index;
        draft = {...product, artwork: product.artwork || null};
        artworkByTab = {existing: null, new: null};
        mode(product.mode === 'existing' ? 'existing' : 'new');
        newNames.forEach(name => { field(name).value = text(product[name]); error(name); });
        choices.forEach(choice => choice.checked = (product.customizations || []).includes(choice.value));
        el('npq-existing-customization').value = text(product.customization);
        populateCatalog(draft.catalog);
        if (product.mode === 'existing' && !draft.catalog && product.catalog_product_id) {
            fetch(form.dataset.detailsUrl.replace('__ID__', encodeURIComponent(product.catalog_product_id)), {headers: {'Accept': 'application/json'}})
                .then(response => { if (!response.ok) throw Error('Unavailable'); return response.json(); })
                .then(data => { if (editing === index) { draft.catalog = data.product; populateCatalog(draft.catalog); } })
                .catch(() => { if (editing === index) error('catalog', 'This catalogue item is no longer available. Please select another.'); });
        }
        field('catalog-search').value = '';
        field('artwork').value = ''; el('npq-existing-artwork').value = '';
        counts(); showArtwork(); status();
        el('npq-product-stage').scrollIntoView({behavior:'smooth', block:'start'});
    }
    function stage(contact) {
        (contact ? el('npq-contact-stage') : el('npq-product-stage')).scrollIntoView({behavior:'smooth', block:'start'});
    }
    for (const name of ['new','existing']) el(`npq-${name}-tab`).addEventListener('click', () => {
        if (draft.mode !== name) {
            artworkByTab[draft.mode] = {artwork: draft.artwork || null, artwork_name: draft.artwork_name || ''};
            mode(name);
            const saved = artworkByTab[name] || {artwork: null, artwork_name: ''};
            draft.artwork = saved.artwork;
            draft.artwork_name = saved.artwork_name;
        }
        showArtwork();
    });
    for (const name of newNames) field(name).addEventListener('input', () => { error(name); counts(); });
    el('npq-existing-customization').addEventListener('input', counts);
    for (const [inputId, dropId, removeId] of [['npq-artwork','npq-dropzone','npq-file-remove'], ['npq-existing-artwork','npq-existing-dropzone','npq-existing-file-remove']]) {
        const input = el(inputId), drop = el(dropId);
        input.addEventListener('change', () => { draft.artwork = input.files?.[0] || null; draft.artwork_name = draft.artwork?.name || ''; error('artwork'); showArtwork(); });
        drop.addEventListener('dragover', event => { event.preventDefault(); drop.classList.add('is-dragging'); });
        drop.addEventListener('dragleave', () => drop.classList.remove('is-dragging'));
        drop.addEventListener('drop', event => {
            event.preventDefault(); drop.classList.remove('is-dragging');
            const file = event.dataTransfer.files?.[0];
            if (file) { draft.artwork = file; draft.artwork_name = file.name; error('artwork'); showArtwork(); }
        });
        el(removeId).addEventListener('click', () => { draft.artwork = null; draft.artwork_name = ''; input.value = ''; showArtwork(); });
    }
    el('npq-add').addEventListener('click', () => {
        const product = readEditor(); if (!validateProduct(product)) return;
        if (editing < 0 && products.length >= 20) {
            el('npq-products-error').textContent = 'You can add up to 20 products per request.';
            el('npq-products-error').hidden = false; return;
        }
        if (product.catalog) product.catalog_image = safeImage(product.catalog.image);
        if (editing < 0) products.push(product); else products[editing] = product;
        clearEditor(); renderCards();
    });
    el('npq-cancel').addEventListener('click', clearEditor);
    el('npq-another').addEventListener('click', () => { clearEditor(); el('npq-editor-heading').scrollIntoView({behavior:'smooth'}); });
    // Debounced, bounded search of published products. No product-list preload.
    field('catalog-search').addEventListener('input', () => {
        const search = field('catalog-search'); const term = search.value.trim();
        error('catalog'); clearTimeout(searchTimer);
        const results = el('npq-search-results'); results.replaceChildren(); results.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        if (searchController) searchController.abort();
        if (term.length < 2 || term.length > 90) return;
        searchTimer = setTimeout(async () => {
            searchController = new AbortController();
            try {
                const response = await fetch(`${form.dataset.searchUrl}?q=${encodeURIComponent(term)}`, {signal:searchController.signal,headers:{Accept:'application/json'}});
                if (!response.ok) throw Error('Search failed');
                const data = await response.json();
                if (field('catalog-search').value.trim() !== term) return;
                (data.products || []).forEach(product => {
                    const button = make('button','npq-search-result'); button.type = 'button'; button.setAttribute('role','option');
                    const picture = make('img'); picture.src = safeImage(product.image) || placeholder(); picture.alt = '';
                    button.append(picture, make('span','',`${product.name}${product.sku ? ` · ${product.sku}` : ''}`));
                    button.addEventListener('click', async () => {
                        results.hidden = true; search.setAttribute('aria-expanded', 'false');
                        try {
                            const detail = await fetch(form.dataset.detailsUrl.replace('__ID__',encodeURIComponent(product.id)), {headers:{Accept:'application/json'}});
                            if (!detail.ok) throw Error('Unavailable');
                            draft.catalog = (await detail.json()).product;
                            draft.options = {}; draft.sizeQuantities = {};
                            populateCatalog(draft.catalog); search.value = ''; error('catalog');
                        } catch { error('catalog', 'This product is unavailable. Search again.'); }
                    });
                    results.append(button);
                });
                if (!results.children.length) results.append(make('p','npq-no-results','No matching products. Try another search or Product not listed.'));
                results.hidden = false; search.setAttribute('aria-expanded','true');
            } catch (error) { if (error.name !== 'AbortError') {
                const target = form.querySelector('[data-error="catalog"]'); target.textContent = 'Product search is temporarily unavailable. Try again.'; target.hidden = false;
            } }
        }, 250);
    });
    form.addEventListener('submit', event => {
        if (!products.length) {
            event.preventDefault();
            event.stopImmediatePropagation();
            el('npq-products-error').textContent = 'Add at least one product before submitting your request.';
            el('npq-products-error').hidden = false;
            stage(false);
            el('npq-add').focus();
            return;
        }
        syncProducts();
        const inputs = el('npq-upload-inputs'); inputs.replaceChildren();
        products.forEach((product, index) => {
            if (!product.artwork) return;
            const input = make('input'); input.type = 'file'; input.name = `quote_artworks[${index}]`;
            const transfer = new DataTransfer(); transfer.items.add(product.artwork); input.files = transfer.files;
            inputs.append(input);
        });
    }, {capture: true});
    mode('existing'); renderCards(); counts(); status();
    if (state.selected && !products.length) { draft.catalog = state.selected; populateCatalog(draft.catalog); }
    if (form.dataset.serverErrors === '1' && products.length && form.dataset.productErrors !== '1') stage(true);
    window.addEventListener('pagehide', () => { objectUrls.forEach(url => URL.revokeObjectURL(url)); });
})();
