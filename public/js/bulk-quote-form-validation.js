/* Inline validation from the NextPlay Form Design System handoff.
 * Only affects the existing bulk quote request form. Laravel remains authoritative.
 * Product selection, country/region, CAPTCHA, and submission APIs are untouched. */
(() => {
    'use strict';
    const form = document.getElementById('bulk-quote-request-form');
    if (!form) return;
    const fields = [
        'product_type', 'estimated_quantity', 'sizes_needed', 'artwork_details',
        'full_name', 'organization', 'email', 'phone_country_code_id', 'phone',
        'shipping_address', 'country', 'needed_by',
    ];
    const messages = {
        product_type: 'Enter the product type you need.',
        estimated_quantity: 'Select an estimated quantity.',
        sizes_needed: 'Enter the sizes needed.',
        artwork_details: 'Enter at least 10 characters describing your customisation.',
        full_name: 'Enter your full name.',
        organization: 'Enter your company or team name.',
        email: 'Enter your email address.',
        phone_country_code_id: 'Select a country calling code.',
        phone: 'Enter your phone or WhatsApp number.',
        shipping_address: 'Enter your shipping address.',
        country: 'Select a country.',
        needed_by: 'Choose the date you need the order.',
    };
    const minLength = { product_type: 2, artwork_details: 10, full_name: 2, organization: 2, shipping_address: 8 };
    const fieldByName = name => form.elements.namedItem(name);
    const groupFor = field => field?.closest('.bulk-quote-field, [data-address-geography], [data-address-region-for]');

    function problem(field) {
        if (!field || field.disabled) return '';
        const name = field.name;
        const value = (field.value || '').trim();
        if (fields.includes(name) && !value) return messages[name];
        if (name === 'email' && value && (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || field.validity.typeMismatch)) {
            return 'Enter a valid email address.';
        }
        if (name === 'phone' && value && !/^[0-9+()\-.\s]+$/.test(value)) {
            return 'Please enter a valid phone or WhatsApp number.';
        }
        if (minLength[name] && value && value.length < minLength[name]) {
            return name === 'artwork_details' ? messages[name] : `Enter at least ${minLength[name]} characters.`;
        }
        if (name === 'attachment' && field.files?.length && field.files[0].size > 10 * 1024 * 1024) {
            return 'Choose a file under 10 MB.';
        }
        return '';
    }

    function render(field, text) {
        const group = groupFor(field);
        if (!group) return;
        let error = [...group.querySelectorAll('[data-bulk-client-error]')].find(p => p.dataset.for === field.name);
        if (text && !error) {
            error = document.createElement('p');
            error.className = 'bulk-quote-field-error nps-error';
            error.dataset.bulkClientError = '';
            error.dataset.for = field.name;
            error.id = `bulk-quote-client-error-${field.id || field.name}`;
            error.setAttribute('role', 'alert');
            group.append(error);
        }
        if (error) { error.textContent = text; error.hidden = !text; }
        // The server message stays visible until the user edits this particular
        // field. After editing, show only the current client message, not both.
        group.querySelectorAll('.bulk-quote-field-error:not([data-bulk-client-error])').forEach(node => {
            node.hidden = true;
        });
        field.setAttribute('aria-invalid', String(Boolean(text)));
        if (error) {
            const original = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id !== error.id);
            field.setAttribute('aria-describedby', text ? [...original, error.id].join(' ') : original.join(' '));
        }
        // Styled dropdown is a button; preserve its accessibility state as well.
        if (field.matches('select')) {
            group.querySelector('.bulk-quote-select-trigger')?.setAttribute('aria-invalid', String(Boolean(text)));
        }
        if (field.name === 'phone' || field.name === 'phone_country_code_id') {
            const control = group.querySelector('.bulk-quote-phone-control');
            if (control) control.classList.toggle('is-invalid', Boolean(text));
        }
    }

    let submitted = false;
    const validate = field => {
        const message = problem(field);
        render(field, message);
        return message;
    };

    for (const name of [...fields, 'attachment']) {
        const field = fieldByName(name);
        if (!field) continue;
        const refresh = () => {
            if (!submitted && field.getAttribute('aria-invalid') !== 'true') return;
            validate(field);
        };
        field.addEventListener('input', refresh);
        field.addEventListener('change', refresh);
        field.addEventListener('blur', () => {
            if (field.value || submitted) validate(field);
        });
    }

    form.addEventListener('submit', event => {
        submitted = true;
        const errors = [...fields, 'attachment']
            .map(name => fieldByName(name))
            .filter(Boolean)
            .map(field => ({ field, message: validate(field) }))
            .filter(item => item.message);
        if (!errors.length) {
            const button = form.querySelector('.bulk-quote-submit');
            if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
            return;
        }
        event.preventDefault();
        // Keep the feedback at each field. Do not show a duplicate summary
        // at the top of the form; focus the first invalid control instead.
        const first = errors[0].field;
        (groupFor(first)?.querySelector('.bulk-quote-select-trigger') || first).focus();
    });
})();
