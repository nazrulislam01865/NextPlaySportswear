import test from 'node:test';
import assert from 'node:assert/strict';
import { resetPasswordState, setupResetPassword } from '../../resources/js/customer-reset-password.js';

test('requires letter number symbol and matching confirmation', () => {
    for (const password of ['abcdefgh', '123456789!', 'Password123', 'abc123!', '']) {
        assert.equal(resetPasswordState(password, password).canSubmit, false);
    }
    assert.equal(resetPasswordState('NewPassword123!', 'NewPassword123!').canSubmit, true);
    assert.equal(resetPasswordState('NewPassword123!', 'different123!').canSubmit, false);
});

test('reset form uses one hint or error line, live mismatch and disables invalid submission', () => {
    const fields = { password: { value: '' }, password_confirmation: { value: '', setAttribute() {} } }; 
    const hint = { textContent: '', dataset: { defaultHint: 'Use at least 8 characters with a letter, a number, and a special character.' }, classList: { invalid: false, toggle(name, value) { this.invalid = value; } } };
    const match = { hidden: true, textContent: '', dataset: {} };
    const submit = { disabled: false };
    const handlers = {};
    const form = {
        dataset: {},
        elements: { namedItem(name) { return fields[name]; } },
        querySelector(selector) {
            if (selector.includes('hint')) return hint;
            if (selector.includes('match')) return match;
            return submit;
        },
        addEventListener(event, callback) { handlers[event] = callback; },
    };
    const originalDocument = globalThis.document;
    const originalWindow = globalThis.window;
    globalThis.document = { querySelectorAll: () => [form] };
    globalThis.window = { addEventListener: () => {} };
    try {
        setupResetPassword();
        assert.equal(submit.disabled, true);
        fields.password.value = 'abcdefghi';
        handlers.input();
        assert.equal(hint.classList.invalid, true);
        assert.match(hint.textContent, /special character/);
        fields.password.value = 'SafePassword123!';
        fields.password_confirmation.value = 'SafePassword123@';
        handlers.input();
        assert.equal(hint.classList.invalid, false);
        assert.equal(match.hidden, false);
        assert.match(match.textContent, /don.t match/);
        assert.equal(submit.disabled, true);
        fields.password_confirmation.value = 'SafePassword123!';
        handlers.input();
        assert.equal(match.hidden, true);
        assert.equal(submit.disabled, false);
        fields.password_confirmation.value = 'changed';
        let blocked = false;
        handlers.submit({ preventDefault() { blocked = true; }, stopImmediatePropagation() {} });
        assert.equal(blocked, true);
    } finally {
        globalThis.document = originalDocument;
        globalThis.window = originalWindow;
    }
});
