/** Pre-account email code UX; all verification rules are enforced on the server. */
// Delegated handling survives browser back/forward restoration and inserted auth forms.
// One handler prevents accidental double-toggles after repeat storefront initialization.
const visibilityRoots = new WeakSet();
export const setupPasswordVisibility = (root = document) => {
    if (visibilityRoots.has(root)) return;
    visibilityRoots.add(root);
    root.addEventListener('click', (event) => {
        const button = event.target?.closest?.('[data-password-visibility]');
        if (!button) return;
        const input = root.getElementById?.(button.dataset.passwordVisibility);
        if (!input || (input.type !== 'password' && input.type !== 'text')) return;
        event.preventDefault();
        const isVisible = input.type === 'password';
        input.type = isVisible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(isVisible));
        button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        button.dataset.visible = String(isVisible);
    });
};

export const setupRegistrationWizard = () => {
    setupPasswordVisibility();
    document.querySelectorAll('[data-code-verification]').forEach((form) => {
        const cells = Array.from(form.querySelectorAll('[data-code-digit]'));
        const hidden = form.querySelector('[data-code-value]');
        const submit = form.querySelector('[data-code-submit]');
        if (cells.length !== 6 || !hidden || !submit) return;
        const sync = () => {
            const code = cells.map((cell) => cell.value).join('');
            hidden.value = code;
            submit.disabled = !/^\d{6}$/.test(code);
        };
        cells.forEach((cell, index) => {
            cell.addEventListener('input', () => {
                const digits = cell.value.replace(/\D/g, '');
                cell.value = digits.slice(-1);
                if (digits.length > 1) {
                    digits.split('').slice(0, 6 - index).forEach((digit, i) => { cells[index + i].value = digit; });
                    cells[Math.min(index + digits.length, 5)]?.focus();
                } else if (cell.value && index < 5) cells[index + 1].focus();
                sync();
            });
            cell.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !cell.value && index > 0) cells[index - 1].focus();
                if (event.key === 'ArrowLeft' && index > 0) cells[index - 1].focus();
                if (event.key === 'ArrowRight' && index < 5) cells[index + 1].focus();
            });
            cell.addEventListener('paste', (event) => {
                const pasted = event.clipboardData?.getData('text')?.replace(/\D/g, '').slice(0, 6);
                if (!pasted) return;
                event.preventDefault();
                pasted.split('').forEach((digit, i) => { cells[i].value = digit; });
                cells[Math.min(pasted.length, 5)].focus();
                sync();
            });
        });
        form.addEventListener('submit', (event) => {
            sync();
            if (submit.disabled) event.preventDefault();
        });
        sync();
    });
    document.querySelectorAll('[data-registration-resend]').forEach((form) => {
        const button = form.querySelector('[data-resend-button]');
        if (!button) return;
        const availableAt = Number(form.dataset.availableAt || 0) * 1000;
        const update = () => {
            const seconds = Math.max(0, Math.ceil((availableAt - Date.now()) / 1000));
            button.disabled = seconds > 0;
            button.textContent = seconds > 0
                ? `Resend code in 00:${String(seconds).padStart(2, '0')}`
                : 'Resend verification code';
        };
        update();
        if (availableAt > Date.now()) {
            const timer = setInterval(() => { update(); if (Date.now() >= availableAt) clearInterval(timer); }, 1000);
        }
    });

};
