/**
 * Lightweight browser-only registration validation. Laravel remains the
 * authority on registration rules and accepts/rejects all submitted data.
 */
export const customerRegistrationState = ({ name = '', email = '', password = '', confirmation = '', terms = false }) => {
    const normalizedEmail = email.trim();
    const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail);
    const validPassword = password.length >= 8 && /[A-Za-z]/.test(password) && /\d/.test(password) && /[^A-Za-z0-9]/.test(password);
    const showMatchFeedback = password.length > 0 && confirmation.length > 0;
    const passwordsMatch = showMatchFeedback && password === confirmation;

    return {
        showMatchFeedback,
        passwordsMatch,
        validPassword,
        canSubmit: name.trim().length >= 2 && validEmail && validPassword && passwordsMatch && terms === true,
    };
};

export const setupCustomerRegistration = () => {
    document.querySelectorAll('form[data-customer-registration]').forEach((form) => {
        if (form.dataset.customerRegistrationReady === 'true') return;

        const name = form.elements.namedItem('name');
        const email = form.elements.namedItem('email');
        const password = form.elements.namedItem('password');
        const confirmation = form.elements.namedItem('password_confirmation');
        const terms = form.elements.namedItem('terms');
        const submit = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('[data-password-match-feedback]');
        const ruleFeedback = form.querySelector('[data-password-rule-feedback]');
        if (!name || !email || !password || !confirmation || !terms || !submit || !feedback) return;

        form.dataset.customerRegistrationReady = 'true';

        const update = () => {
            const result = customerRegistrationState({
                name: name.value,
                email: email.value,
                password: password.value,
                confirmation: confirmation.value,
                terms: terms.checked,
            });
            const canSubmit = result.canSubmit && email.validity.valid && form.dataset.submitting !== 'true';
            submit.disabled = !canSubmit;
            submit.setAttribute('aria-disabled', String(!canSubmit));

            feedback.hidden = !result.showMatchFeedback;
            feedback.textContent = !result.showMatchFeedback
                ? ''
                : result.passwordsMatch ? 'Passwords match.' : 'Passwords do not match.';
            feedback.dataset.state = result.passwordsMatch ? 'match' : 'mismatch';
            if (ruleFeedback) {
                const serverError = password.value.length === 0 && ruleFeedback.dataset?.serverError;
                const invalid = (password.value.length > 0 && !result.validPassword) || Boolean(serverError);
                ruleFeedback.hidden = false;
                ruleFeedback.classList?.toggle('is-invalid', invalid);
                ruleFeedback.textContent = serverError || (password.value.length > 0 && !result.validPassword)
                    ? 'Use at least 8 characters with a letter, a number, and a special character.'
                    : (ruleFeedback.dataset?.defaultHint || 'Use at least 8 characters with a letter, a number, and a special character.');
            }
        };

        form.addEventListener('input', update);
        form.addEventListener('change', update);
        // A script or Enter key must not submit an invalid form, even when
        // HTML native validation is bypassed with the form's novalidate attr.
        form.addEventListener('submit', (event) => {
            update();
            if (submit.disabled || !form.checkValidity()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });
        window.addEventListener('pageshow', update);
        update();
    });
};
