import { customerRegistrationState } from './customer-registration.js';

export const resetPasswordState = (password = '', confirmation = '') => {
    const state = customerRegistrationState({ name: 'Reset User', email: 'reset@example.com', password, confirmation, terms: true });
    return { validPassword: state.validPassword, passwordsMatch: state.passwordsMatch,
        showMatchFeedback: state.showMatchFeedback, canSubmit: state.validPassword && state.passwordsMatch };
};

export const setupResetPassword = () => {
    document.querySelectorAll('form[data-password-reset]').forEach((form) => {
        if (form.dataset.passwordResetReady === 'true') return;
        const password = form.elements.namedItem('password');
        const confirmation = form.elements.namedItem('password_confirmation');
        const hint = form.querySelector('[data-reset-password-hint]');
        const match = form.querySelector('[data-reset-password-match]');
        const submit = form.querySelector('[data-reset-submit]');
        if (!password || !confirmation || !hint || !match || !submit) return;
        form.dataset.passwordResetReady = 'true';
        const update = () => {
            const state = resetPasswordState(password.value, confirmation.value);
            const serverError = password.value.length === 0 && hint.dataset.serverError;
            const invalid = Boolean(serverError) || (password.value.length > 0 && !state.validPassword);
            hint.classList.toggle('is-invalid', invalid);
            hint.textContent = serverError || (invalid
                ? 'Use at least 8 characters with a letter, a number, and a special character.'
                : hint.dataset.defaultHint);
            const serverMismatch = !password.value && !confirmation.value && match.dataset.serverError;
            const mismatch = state.showMatchFeedback && !state.passwordsMatch;
            match.hidden = !mismatch && !serverMismatch;
            match.textContent = serverMismatch || (mismatch ? 'Passwords don’t match. Please try again.' : '');
            confirmation.setAttribute('aria-invalid', String(mismatch));
            submit.disabled = !state.canSubmit || form.dataset.submitting === 'true';
            return state.canSubmit;
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('submit', (event) => {
            if (!update()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });
        window.addEventListener('pageshow', update);
        update();
    });
};
