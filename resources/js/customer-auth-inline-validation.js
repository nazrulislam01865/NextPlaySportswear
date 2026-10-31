/**
 * Inline validation for customer sign-in, sign-up email verification and
 * password recovery. Laravel remains authoritative on every POST.
 *
 * Focus changes are not validation attempts: the email input may be focused
 * automatically when the page opens, and browsers may restore an old value.
 * Only an attempted submit reveals client errors. Once submitted, editing a
 * field updates that error immediately. Server errors remain visible until
 * the user edits the corresponding field.
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
            // Do not validate on blur, focus, or clicks elsewhere in the form.
            // In particular, the autofocus input must not display an error
            // merely because the user moves to the password field.
            field.addEventListener('input', () => {
                const error = errorFor(field);
                if (error?.dataset.serverError) delete error.dataset.serverError;
                render(field, form.dataset.authSubmitted === 'true');
            });
        });

        form.addEventListener('submit', (event) => {
            form.dataset.authSubmitted = 'true';
            // Do not short-circuit: every invalid field gets its own message.
            const invalid = fields.filter((field) => !render(field, true));
            if (!invalid.length) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            invalid[0].focus();
        });
    });
};
