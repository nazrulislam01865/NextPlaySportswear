/* Unified NextPlay inline validation for straightforward customer forms only.
 * Auth, bulk quote, checkout and payment flows keep their specialized handlers.
 * Server validation remains authoritative. No network calls are introduced.
 */
const fieldLabel = (form, field) => {
    const id = field.getAttribute('id');
    const label = id ? [...form.querySelectorAll('label[for]')].find((item) => item.htmlFor === id) : field.closest('label');
    return (label?.textContent || field.name || 'this field').replace(/\*/g, '').replace(/\(optional\)/gi, '').trim();
};

const validationMessage = (form, field) => {
    if (field.disabled || field.type === 'hidden' || field.getAttribute('aria-hidden') === 'true') return '';
    if (field.getClientRects().length === 0) return '';
    const value = String(field.value || '').trim();
    const name = fieldLabel(form, field);
    if (field.required && !value) {
        if (field.type === 'email') return 'Enter your email address.';
        if (field.type === 'password') return `Enter your ${name.toLowerCase()}.`;
        if (field.tagName === 'SELECT') return `Select ${name.toLowerCase()}.`;
        return `Enter ${name.toLowerCase()}.`;
    }
    if (value && field.type === 'email' && !field.validity.valid) return 'Enter a valid email address.';
    if (value && field.validity.tooShort) return `Enter at least ${field.minLength} characters.`;
    if (value && field.validity.tooLong) return `Use no more than ${field.maxLength} characters.`;
    return '';
};

export const setupUnifiedFormValidation = () => {
    document.querySelectorAll('form[data-nps-inline]').forEach((form, formIndex) => {
        if (form.dataset.npsInlineReady === '1') return;
        form.dataset.npsInlineReady = '1';
        const fields = [...form.querySelectorAll('input, textarea, select')].filter((field) =>
            field.name && !['checkbox', 'radio', 'file', 'hidden', 'submit'].includes(field.type) && field.tabIndex !== -1
        );
        let submitted = false;

        const renderError = (field) => {
            const text = validationMessage(form, field);
            const errorId = `nps-inline-${formIndex}-${field.name.replace(/[^a-z0-9_-]/gi, '-')}`;
            let message = [...form.querySelectorAll('[data-nps-inline-error]')].find((node) => node.id === errorId);
            if (text && !message) {
                message = document.createElement('p');
                message.id = errorId;
                message.className = 'nps-error';
                message.dataset.npsInlineError = '';
                message.setAttribute('role', 'alert');
                (field.closest('.np-auth-proto__password') || field).insertAdjacentElement('afterend', message);
            }
            if (message) {
                message.textContent = text;
                message.hidden = !text;
            }
            if (text) {
                field.setAttribute('aria-invalid', 'true');
                const ids = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                ids.add(errorId);
                field.setAttribute('aria-describedby', [...ids].join(' '));
            } else {
                if (field.getAttribute('aria-invalid') === 'true') field.removeAttribute('aria-invalid');
                const ids = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id && id !== errorId);
                if (ids.length) field.setAttribute('aria-describedby', ids.join(' '));
                else field.removeAttribute('aria-describedby');
            }
            return text;
        };

        fields.forEach((field) => {
            field.addEventListener('blur', () => {
                if (submitted || field.value) renderError(field);
            });
            const update = () => {
                if (submitted || field.getAttribute('aria-invalid') === 'true') renderError(field);
            };
            field.addEventListener('input', update);
            field.addEventListener('change', update);
        });

        form.addEventListener('submit', (event) => {
            submitted = true;
            const firstInvalid = fields.find((field) => renderError(field));
            // Run once more for all fields, not just the first, so every error is inline.
            fields.forEach((field) => { if (field !== firstInvalid) renderError(field); });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });
};
