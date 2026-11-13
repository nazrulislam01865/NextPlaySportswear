/* Shared NextPlay admin dropdown. Progressively enhances native selects, keeping
   their name/value/GET semantics. Menus live under body to avoid panel clipping.
   Filtering is fully client-side; no additional database or network requests. */
(() => {
    'use strict';

    const initialize = () => {
        let active = null;
        let sequence = 0;
        const normalize = (value) => String(value ?? '').normalize('NFKD').toLocaleLowerCase().trim();

        const close = (instance, restoreFocus = false) => {
            if (!instance || instance.menu.hidden) return;
            instance.menu.hidden = true;
            instance.wrapper.classList.remove('is-open');
            instance.trigger.setAttribute('aria-expanded', 'false');
            if (active === instance) active = null;
            if (restoreFocus) instance.trigger.focus({ preventScroll: true });
        };

        const position = (instance) => {
            const r = instance.trigger.getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight) {
                close(instance);
                return;
            }
            const margin = 12;
            const gap = 6;
            const desiredWidth = instance.searchable ? 358 : 260;
            const width = Math.min(window.innerWidth - margin * 2, Math.max(r.width, desiredWidth));
            const left = Math.max(margin, Math.min(r.left, window.innerWidth - width - margin));
            const below = window.innerHeight - r.bottom - gap - margin;
            const above = r.top - gap - margin;
            const flipUp = below < 240 && above > below;
            const available = Math.max(100, flipUp ? above : below);
            const maxHeight = Math.min(instance.searchable ? 420 : 340, available);

            instance.menu.style.width = `${width}px`;
            instance.menu.style.left = `${left}px`;
            instance.menu.style.maxHeight = `${maxHeight}px`;
            const visualHeight = Math.min(instance.menu.scrollHeight, maxHeight);
            instance.menu.style.top = `${flipUp ? Math.max(margin, r.top - gap - visualHeight) : r.bottom + gap}px`;
        };

        document.querySelectorAll('[data-np-admin-select]').forEach((wrapper) => {
            if (wrapper.dataset.npSelectReady === 'true') return;
            const select = wrapper.querySelector('select');
            if (!select || select.multiple || select.disabled) return;
            wrapper.dataset.npSelectReady = 'true';

            const searchable = wrapper.dataset.npSelectSearchable === 'true';
            const label = wrapper.dataset.npSelectLabel || select.getAttribute('aria-label') || 'Select an option';
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'np-as-trigger';
            trigger.setAttribute('aria-label', label);
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            const caption = document.createElement('span');
            caption.className = 'np-as-caption';
            const arrow = document.createElement('span');
            arrow.className = 'np-as-arrow';
            arrow.setAttribute('aria-hidden', 'true');
            arrow.innerHTML = '<svg viewBox="0 0 20 20" fill="none" width="17" height="17"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            trigger.append(caption, arrow);

            const menu = document.createElement('div');
            menu.className = 'np-as-menu';
            menu.id = `np-as-menu-${++sequence}`;
            menu.hidden = true;
            trigger.setAttribute('aria-controls', menu.id);
            const list = document.createElement('div');
            list.className = 'np-as-list';
            list.id = `${menu.id}-list`;
            list.setAttribute('role', 'listbox');
            list.setAttribute('aria-label', label);
            let search = null;
            if (searchable) {
                const searchShell = document.createElement('div');
                searchShell.className = 'np-as-search-shell';
                search = document.createElement('input');
                search.type = 'search';
                search.className = 'np-as-search';
                search.placeholder = wrapper.dataset.npSelectSearchPlaceholder || 'Search options...';
                search.autocomplete = 'off';
                search.setAttribute('aria-label', `Search ${label}`);
                search.setAttribute('aria-controls', list.id);
                searchShell.append(search);
                menu.append(searchShell);
            }
            menu.append(list);
            document.body.append(menu);
            wrapper.append(trigger);
            wrapper.classList.add('is-enhanced');
            select.tabIndex = -1;
            select.setAttribute('aria-hidden', 'true');
            if (select.required) trigger.setAttribute('aria-required', 'true');
            const instance = { wrapper, select, trigger, caption, menu, list, search, searchable };

            const options = () => {
                const stack = [];
                return Array.from(select.options).filter(option => !option.hidden).map(option => {
                    const labelText = option.dataset.label || option.textContent.trim();
                    const hasDepth = option.dataset.depth !== undefined;
                    const depth = hasDepth ? Math.max(0, Math.min(20, Number(option.dataset.depth) || 0)) : 0;
                    let path = labelText;
                    if (hasDepth) {
                        stack.length = depth;
                        path = [...stack.filter(Boolean), labelText].join(' / ');
                        stack[depth] = labelText;
                        stack.length = depth + 1;
                    }
                    return { value: option.value, label: labelText, depth, path, disabled: option.disabled };
                });
            };

            const sync = () => {
                caption.textContent = select.selectedOptions[0]?.dataset.label || select.selectedOptions[0]?.textContent.trim() || select.options[0]?.textContent.trim() || 'Select';
                caption.title = caption.textContent;
                trigger.classList.toggle('is-placeholder', !select.value);
                list.querySelectorAll('[role="option"]').forEach(item => {
                    item.setAttribute('aria-selected', String(item.dataset.value === select.value));
                });
            };

            const rebuild = (query = '') => {
                const term = normalize(query);
                const all = options();
                const matches = term ? all.filter(item => normalize(`${item.label} ${item.path}`).includes(term)) : all;
                const shown = matches.slice(0, 120);
                const fragment = document.createDocumentFragment();
                for (const item of shown) {
                    const optionButton = document.createElement('button');
                    optionButton.type = 'button';
                    optionButton.className = 'np-as-option';
                    optionButton.setAttribute('role', 'option');
                    optionButton.setAttribute('aria-selected', String(item.value === select.value));
                    optionButton.setAttribute('aria-label', item.path);
                    optionButton.dataset.value = item.value;
                    optionButton.disabled = item.disabled;
                    if (item.depth && !term) optionButton.style.setProperty('--np-as-indent', `${12 + Math.min(item.depth, 5) * 11}px`);
                    const name = document.createElement('span');
                    name.className = 'np-as-option-label';
                    name.textContent = item.label;
                    optionButton.append(name);
                    if (item.path !== item.label && (term || item.value === select.value)) {
                        const trail = document.createElement('small');
                        trail.className = 'np-as-option-trail';
                        trail.textContent = item.path;
                        optionButton.append(trail);
                    }
                    optionButton.addEventListener('click', () => {
                        if (select.value !== item.value) {
                            select.value = item.value;
                            select.dispatchEvent(new Event('input', { bubbles: true }));
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        sync();
                        close(instance, true);
                    });
                    fragment.append(optionButton);
                }
                if (!shown.length) {
                    const empty = document.createElement('p');
                    empty.className = 'np-as-message';
                    empty.textContent = 'No matching options';
                    fragment.append(empty);
                } else if (matches.length > shown.length) {
                    const info = document.createElement('p');
                    info.className = 'np-as-message';
                    info.textContent = `Showing ${shown.length} of ${matches.length} results. Search to narrow the list.`;
                    fragment.append(info);
                }
                list.replaceChildren(fragment);
                if (!menu.hidden) position(instance);
            };

            const open = () => {
                if (active && active !== instance) close(active);
                if (search) search.value = '';
                rebuild();
                menu.hidden = false;
                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                active = instance;
                position(instance);
                if (search) search.focus({ preventScroll: true });
                else (list.querySelector('[aria-selected="true"]') || list.querySelector('.np-as-option:not(:disabled)'))?.focus({ preventScroll: true });
            };

            const move = (event) => {
                const items = [...list.querySelectorAll('.np-as-option:not(:disabled)')];
                if (!items.length) return;
                const index = items.indexOf(document.activeElement);
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                    : event.key === 'ArrowDown' ? (index + 1) % items.length : (index < 0 ? items.length - 1 : (index - 1 + items.length) % items.length);
                items[next].focus({ preventScroll: true });
                items[next].scrollIntoView({ block: 'nearest' });
            };

            trigger.addEventListener('click', () => menu.hidden ? open() : close(instance));
            trigger.addEventListener('keydown', event => {
                if (menu.hidden && ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    open();
                }
            });
            menu.addEventListener('keydown', event => {
                if (event.key === 'Escape') { event.preventDefault(); close(instance, true); return; }
                if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                    event.preventDefault();
                    move(event);
                }
                if (event.key === 'Enter' && event.target === search) {
                    event.preventDefault();
                    list.querySelector('.np-as-option:not(:disabled)')?.click();
                }
            });
            search?.addEventListener('input', () => rebuild(search.value));
            select.addEventListener('change', sync);
            // Alpine x-model may change the selected value without firing change.
            // Sync the caption without triggering the application's filter callback.
            select.addEventListener('np-admin-select-sync', sync);
            select.addEventListener('invalid', (event) => {
                // A hidden required native select cannot display the browser's normal popup.
                // Keep constraint validation but direct the user to the accessible menu.
                event.preventDefault();
                open();
            });
            select.form?.addEventListener('reset', () => window.setTimeout(sync, 0));
            sync();
        });

        document.addEventListener('pointerdown', event => {
            if (active && !active.wrapper.contains(event.target) && !active.menu.contains(event.target)) close(active);
        });
        document.addEventListener('focusin', event => {
            if (active && !active.wrapper.contains(event.target) && !active.menu.contains(event.target)) close(active);
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && active) { event.preventDefault(); close(active, true); }
        });
        window.addEventListener('resize', () => { if (active) position(active); }, { passive: true });
        window.addEventListener('scroll', (event) => {
            if (active && !active.menu.contains(event.target)) position(active);
        }, { passive: true, capture: true });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
