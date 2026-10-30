/* Shared region selector: no request for countries without configured regions. */
(() => {
    const regionCache = new Map();
    const ready = () => document.querySelectorAll('[data-address-geography]').forEach((container) => {
        if (container.dataset.geoReady) return;
        container.dataset.geoReady = '1';
        const country = container.querySelector('[data-geo-country]');
        const region = document.querySelector(`[data-address-region-for="${CSS.escape(country.name)}"]`);
        if (!region) return;
        // Bulk quotes use a free-text County / State field by design.
        // No region fetch is needed and the field must never become a dropdown.
        if (region.hasAttribute('data-geo-region-freeform')) {
            const input = region.querySelector('[data-geo-region-input]');
            country.addEventListener('change', () => { if (input) input.value = ''; });
            return;
        }
        const label = region.querySelector('[data-geo-region-label]');
        const input = region.querySelector('[data-geo-region-input]');
        const select = region.querySelector('[data-geo-region-select]');
        const fieldName = input.name;
        let sequence = 0;
        const setInput = (show) => {
            label.htmlFor = show ? input.id : select.id;
            input.hidden = !show;
            input.disabled = !show;
            select.hidden = show;
            select.disabled = show;
            if (show) { input.name = fieldName; select.removeAttribute('name'); }
            else { select.name = fieldName; input.removeAttribute('name'); }
        };
        const update = async (initial = false) => {
            const currentSequence = ++sequence;
            const option = country.selectedOptions[0];
            const title = option?.dataset.regionLabel || 'State / Province';
            label.textContent = `${title} (optional)`;
            select.setAttribute('aria-label', title);
            input.placeholder = `${title} (optional)`;
            const previous = initial ? container.dataset.initialRegion || '' : '';
            if (!initial) input.value = '';
            setInput(true);
            if (option?.dataset.hasRegions !== '1' || !option.dataset.iso) return;
            select.replaceChildren(new Option('Loading...', ''));
            try {
                const iso = option.dataset.iso;
                if (!regionCache.has(iso)) {
                    const url = container.dataset.regionsUrl.replace('__ISO__', encodeURIComponent(iso));
                    regionCache.set(iso, fetch(url, { headers: { Accept: 'application/json' } }).then((response) => {
                        if (!response.ok) throw new Error('Region list unavailable');
                        return response.json();
                    }).catch((error) => { regionCache.delete(iso); throw error; }));
                }
                const regions = await regionCache.get(iso);
                if (currentSequence !== sequence || country.selectedOptions[0] !== option) return;
                if (!regions.length) return;
                select.replaceChildren(new Option(`Select ${title.toLowerCase()} (optional)`, ''));
                regions.forEach(({ name, code }) => {
                    const opt = new Option(name, name);
                    if (previous && (previous === name || previous === code)) opt.selected = true;
                    select.add(opt);
                });
                // Preserve legacy saved values if a state's name/code has since changed.
                if (previous && !select.value) select.add(new Option(previous, previous, true, true));
                setInput(false);
            } catch (_) {
                // Do not block an order when the lookup fails: allow manual entry.
                if (initial) input.value = previous;
                setInput(true);
            }
        };
        country.addEventListener('change', () => update());
        update(true);
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
})();
