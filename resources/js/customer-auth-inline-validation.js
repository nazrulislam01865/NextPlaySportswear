/**
 * Customer auth field validation is inline rather than the browser's native
 * constraint-validation popover. Laravel still validates every POST.
 */
export const authFieldError = ({ type = 'text', value = '', required = true }) => {
    const text = String(value).trim();
    if (!text && required) {
        return type === 'email' ? 'Enter your email address.'
            : type === 'password' ? 'Enter your password.'
                : 'This field is required.';
    }
    if (text && type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) {
        return 'Enter a valid email address.';
    }
    return '';
};

export const setupCustomerAuthInlineValidation = () => {
    document.querySelectorAll('form[data-auth-inline-validation]').forEach((form) => {
        if (form.dataset.authInlineValidationReady === 'true') return;
        form.dataset.authInlineValidationReady = 'true';
        // The Blade forms also declare novalidate so browser bubbles cannot
        // reappear even before JavaScript initializes.
        form.noValidate = true;

        const fields = Array.from(form.querySelectorAll('[data-auth-field]'));
        const errorFor = (field) => form.querySelector(`[data-auth-error-for="${field.name}"]`);
        const render = (field, showError) => {
            const message = authFieldError(field);
            const error = errorFor(field);
            if (!error) return !message;
            error.hidden = !showError || !message;
            error.textContent = showError && message ? message : '';
            if (showError && message) {
                field.setAttribute('aria-invalid', 'true');
                field.setAttribute('aria-describedby', error.id);
            } else {
                field.removeAttribute('aria-invalid');
                if (field.getAttribute('aria-describedby') === error.id) field.removeAttribute('aria-describedby');
            }
            return !message;
        };

        fields.forEach((field) => {
            const error = errorFor(field);
            field.addEventListener('blur', () => {
                field.dataset.authTouched = 'true';
                render(field, true);
            });
            field.addEventListener('input', () => {
                if (error?.dataset.serverError) delete error.dataset.serverError;
                render(field, field.dataset.authTouched === 'true' || form.dataset.authSubmitted === 'true');
            });
        });
        form.addEventListener('submit', (event) => {
            form.dataset.authSubmitted = 'true';
            const invalid = fields.filter((field) => !render(field, true));
            if (!invalid.length) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            invalid[0].focus();
        });
    });
};
