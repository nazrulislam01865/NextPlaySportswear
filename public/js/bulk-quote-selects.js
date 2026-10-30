/* Bulk quote selects: accessible, searchable menus. Existing native selects remain the form values. */
(() => {
    'use strict';
    const form = document.getElementById('bulk-quote-request-form');
    if (!form) return;
    let active = null;

    const close = (instance, restoreFocus = false) => {
        if (!instance) return;
        instance.menu.hidden = true;
        instance.trigger.setAttribute('aria-expanded', 'false');
        instance.wrapper.classList.remove('is-open');
        if (active === instance) active = null;
        if (restoreFocus) instance.trigger.focus();
    };

    let menuIndex = 0;
    const enhance = select => {
        if (select.classList.contains('bulk-quote-select-native-hidden') || select.disabled || select.hidden) return;
        const index = menuIndex++;
        const wrapper = document.createElement('div');
        wrapper.className = 'bulk-quote-select-ui';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'bulk-quote-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const label = select.closest('.bulk-quote-field, [data-address-geography]')?.querySelector('label')?.textContent?.trim()
            || select.getAttribute('aria-label') || 'Choose an option';
        trigger.setAttribute('aria-label', label);
        const selectedText = document.createElement('span');
        selectedText.className = 'bulk-quote-select-value';
        trigger.append(selectedText);

        const menu = document.createElement('div');
        menu.className = 'bulk-quote-select-menu';
        menu.id = `bulk-quote-select-menu-${index}`;
        menu.hidden = true;
        trigger.setAttribute('aria-controls', menu.id);
        const list = document.createElement('div');
        list.className = 'bulk-quote-select-list';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', label);
        const choices = [...select.options].filter(option => !option.disabled && !option.hidden);
        const hasSearch = choices.length > 8;
        let filter = null;
        if (hasSearch) {
            filter = document.createElement('input');
            filter.type = 'search';
            filter.className = 'bulk-quote-select-filter';
            filter.placeholder = 'Search options…';
            filter.setAttribute('aria-label', `Search ${label}`);
            menu.append(filter);
        }
        menu.append(list);
        wrapper.append(trigger, menu);
        select.after(wrapper);
        select.classList.add('bulk-quote-select-native-hidden');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        const instance = { wrapper, trigger, menu, filter, list };

        function sync() {
            const option = select.selectedOptions[0];
            selectedText.textContent = option?.textContent?.trim() || 'Select an option';
            trigger.classList.toggle('is-placeholder', !select.value);
            trigger.setAttribute('aria-invalid', select.getAttribute('aria-invalid') || 'false');
            list.querySelectorAll('[role="option"]').forEach(button => {
                button.setAttribute('aria-selected', String(button.dataset.value === select.value));
            });
        }
        function build(query = '') {
            const term = query.trim().toLocaleLowerCase();
            list.replaceChildren();
            const matching = choices.filter(option => `${option.textContent} ${option.dataset.countryName || ''}`.toLocaleLowerCase().includes(term));
            if (!matching.length) {
                const empty = document.createElement('p');
                empty.className = 'bulk-quote-select-empty';
                empty.textContent = 'No matching options';
                list.append(empty);
                return;
            }
            matching.forEach(option => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'bulk-quote-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(option.value === select.value));
                item.dataset.value = option.value;
                item.textContent = option.dataset.countryName
                    ? `${option.textContent.trim()} · ${option.dataset.countryName}`
                    : option.textContent.trim();
                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    sync();
                    close(instance, true);
                });
                list.append(item);
            });
        }
        function open() {
            if (active && active !== instance) close(active);
            build();
            if (filter) filter.value = '';
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            wrapper.classList.add('is-open');
            active = instance;
            if (filter) filter.focus();
            else (list.querySelector('[aria-selected="true"]') || list.querySelector('button'))?.focus();
        }
        trigger.addEventListener('click', () => menu.hidden ? open() : close(instance));
        trigger.addEventListener('keydown', event => {
            if (['ArrowDown', 'ArrowUp', ' ', 'Enter'].includes(event.key) && menu.hidden) {
                event.preventDefault();
                open();
            }
            if (event.key === 'Escape') close(instance);
        });
        menu.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); close(instance, true); return; }
            if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
            const items = [...list.querySelectorAll('button')];
            if (!items.length) return;
            event.preventDefault();
            const current = items.indexOf(document.activeElement);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                : event.key === 'ArrowDown' ? (current + 1) % items.length : (current - 1 + items.length) % items.length;
            items[next].focus();
        });
        filter?.addEventListener('input', () => build(filter.value));
        filter?.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                list.querySelector('button')?.click();
            }
        });
        select.addEventListener('change', sync);
        const associatedLabel = select.id ? [...form.querySelectorAll('label')].find(label => label.htmlFor === select.id) : null;
        associatedLabel?.addEventListener('click', event => { event.preventDefault(); if (menu.hidden) open(); else close(instance); });
        sync();
    };

    form.querySelectorAll('select:not([hidden]):not([disabled])').forEach(enhance);
    // Product-specific options are inserted only after a product is selected.
    const dynamicOptions = document.getElementById('bulk-quote-product-options');
    if (dynamicOptions) {
        new MutationObserver(() => {
            dynamicOptions.querySelectorAll('select:not(.bulk-quote-select-native-hidden)').forEach(enhance);
        }).observe(dynamicOptions, { childList: true, subtree: true });
    }

    // Opening a date calendar should never depend on clicking a tiny browser icon.
    // Keep the original native date inputs (names, values and validation) intact.
    form.querySelectorAll('.bulk-quote-date-control input[type="date"]').forEach(input => {
        const control = input.closest('.bulk-quote-date-control');
        if (!control) return;
        let openedOnPointerDown = false;
        const openCalendar = () => {
            if (input.disabled || input.readOnly || typeof input.showPicker !== 'function') return false;
            try {
                input.showPicker();
                return true;
            } catch (_) {
                return false;
            }
        };
        if (typeof input.showPicker !== 'function') {
            control.classList.add('bulk-quote-date-fallback');
        }
        control.addEventListener('pointerdown', event => {
            // Mouse: prevent the browser selecting a date segment on first click.
            // Touch devices receive user activation on release, handled by click.
            if (event.pointerType !== 'mouse' || event.button !== 0) return;
            if (openCalendar()) {
                openedOnPointerDown = true;
                event.preventDefault();
            }
        });
        control.addEventListener('click', event => {
            if (openedOnPointerDown) {
                openedOnPointerDown = false;
                return;
            }
            if (!openCalendar() && event.target !== input) {
                input.focus({ preventScroll: true });
                input.click();
            }
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ' || (event.altKey && event.key === 'ArrowDown')) {
                if (openCalendar()) event.preventDefault();
            }
        });
    });

    document.addEventListener('focusin', event => {
        if (active && !active.wrapper.contains(event.target)) close(active);
    });
    document.addEventListener('pointerdown', event => {
        if (active && !active.wrapper.contains(event.target)) close(active);
    });
})();
