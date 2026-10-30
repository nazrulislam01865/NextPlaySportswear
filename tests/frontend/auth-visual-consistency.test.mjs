import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setupPasswordVisibility, setupRegistrationWizard } from '../../resources/js/registration-wizard.js';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('one delegated password handler toggles sign-in, signup and reset fields reliably', () => {
    const handlers = [];
    const controls = Object.fromEntries(['login-password', 'signup-password', 'signup-confirm-password', 'reset-password', 'reset-confirm-password'].map((id) => [id, { type: 'password' }]));
    const root = {
        addEventListener(type, handler) { if (type === 'click') handlers.push(handler); },
        getElementById(id) { return controls[id]; },
    };
    setupPasswordVisibility(root);
    setupPasswordVisibility(root);
    assert.equal(handlers.length, 1);
    for (const id of Object.keys(controls)) {
        const attrs = {};
        const button = { dataset: { passwordVisibility: id }, setAttribute(key, value) { attrs[key] = value; } };
        const event = { target: { closest() { return button; } }, preventDefault() { this.prevented = true; } };
        handlers[0](event);
        assert.equal(event.prevented, true);
        assert.equal(controls[id].type, 'text');
        assert.equal(attrs['aria-label'], 'Hide password');
        assert.equal(button.dataset.visible, 'true');
        handlers[0](event);
        assert.equal(controls[id].type, 'password');
        assert.equal(attrs['aria-label'], 'Show password');
        assert.equal(attrs['aria-pressed'], 'false');
    }
});

test('preview mode is internal; all registration steps use the real customer-facing copy', () => {
    const view = read('resources/views/storefront/auth/register.blade.php');
    const controller = read('app/Http/Controllers/Storefront/Auth/RegistrationEmailCodeController.php');
    for (const message of ['We\'ll send you a 6-digit verification code.', 'Send verification code', 'Enter the 6-digit code we sent to', 'Your code expires in 10 minutes.', "Didn't receive a code? Check your spam folder."]) {
        assert.ok(view.includes(message), `Expected customer copy: ${message}`);
    }
    for (const notCustomerCopy of ['Local preview', 'Local test code', '123456', 'No email was sent', 'Continue to verification']) {
        assert.ok(!view.includes(notCustomerCopy), `Preview label should be hidden: ${notCustomerCopy}`);
    }
    assert.ok(controller.includes("$preview ? '123456'"));
    assert.ok(controller.includes("return $preview\n            ? redirect()->route('register')"));
    assert.ok(controller.includes("app()->environment('local', 'testing')"));
});

test('success reset notice is green and email glyph no longer overlaps its input', () => {
    const css = read('resources/css/nextplay-form-system.css');
    const forgot = read('resources/views/storefront/auth/forgot-password.blade.php');
    assert.ok(forgot.includes('np-auth-proto__notice--success'));
    assert.ok(css.includes('.np-auth-proto__notice--success'));
    assert.ok(css.includes('background: #e9f8ee'));
    assert.ok(css.includes('padding-left: 50px !important'));
    assert.ok(css.includes('.np-auth-proto__input-icon svg'));
    assert.ok(css.includes('transform: translateY(-50%)'));
});

test('theme font and guide scale stay centralized with a single 1px focus border', () => {
    const css = read('resources/css/nextplay-form-system.css');
    const theme = read('resources/css/storefront-theme.css');
    assert.match(css, /--nps-font:\s*var\(--np-font-body\)/);
    assert.match(css, /--nps-navy:\s*var\(--np-color-primary\)/);
    assert.match(css, /--nps-orange:\s*var\(--np-color-secondary\)/);
    for (const size of ['--nps-title-size: 28px', '--nps-section-size: 20px', '--nps-body-size: 16px', '--nps-label-size: 14px', '--nps-help-size: 13px', '--nps-control: 48px']) {
        assert.ok(css.includes(size), `Guide scale missing ${size}`);
    }
    assert.ok(theme.includes('font-family: "Expose"'));
    assert.ok(css.includes('outline: none !important;'));
    assert.ok(css.includes('border-width: 1px !important;'));
    assert.ok(css.includes('border-color: var(--nps-focus) !important;'));
    assert.ok(css.includes(':is(:focus, :focus-visible)'));
    assert.ok(css.includes('button[aria-pressed="true"]::after'));
    assert.ok(css.includes('.np-auth-proto__step {'));
    assert.ok(css.includes('font: 400 14px/1.5 var(--nps-font);'));
});
