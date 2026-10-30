import test from 'node:test';
import assert from 'node:assert/strict';
import { customerRegistrationState } from '../../resources/js/customer-registration.js';

const complete = {
    name: 'Alex Taylor',
    email: 'alex@example.com',
    password: 'mypassword123!',
    confirmation: 'mypassword123!',
    terms: true,
};

test('valid form including consent enables sign up', () => {
    assert.equal(customerRegistrationState(complete).canSubmit, true);
});

test('consent is mandatory', () => {
    assert.equal(customerRegistrationState({ ...complete, terms: false }).canSubmit, false);
});

test('password mismatch is shown immediately and submit remains disabled', () => {
    const state = customerRegistrationState({ ...complete, confirmation: 'different123' });
    assert.equal(state.showMatchFeedback, true);
    assert.equal(state.passwordsMatch, false);
    assert.equal(state.canSubmit, false);
});

test('matching confirmation is shown before submit', () => {
    const state = customerRegistrationState(complete);
    assert.equal(state.showMatchFeedback, true);
    assert.equal(state.passwordsMatch, true);
});

test('nothing is shown until both password fields have values', () => {
    const state = customerRegistrationState({ ...complete, confirmation: '' });
    assert.equal(state.showMatchFeedback, false);
    assert.equal(state.canSubmit, false);
});

test('password must satisfy minimum, letter, number and special character', () => {
    for (const pw of ['short7', 'onlyletters', '1234567890', 'letters123', 'abcdefg1'.slice(0,7), 'PasswordOnly!', '12345678!']) {
        assert.equal(customerRegistrationState({ ...complete, password: pw, confirmation: pw }).canSubmit, false);
    }
});

test('name and email must be present and valid', () => {
    for (const invalid of [{ name: ' ' }, { name: 'A' }, { email: 'no-email' }, { email: '' }]) {
        assert.equal(customerRegistrationState({ ...complete, ...invalid }).canSubmit, false);
    }
});

test('registration DOM enables/disables button and updates live feedback without submitting', async () => {
    const { setupCustomerRegistration } = await import('../../resources/js/customer-registration.js');
    const fields = Object.fromEntries(['name', 'email', 'password', 'password_confirmation', 'terms'].map((key) => [key, {
        value: key === 'name' ? 'Alex Taylor' : key === 'email' ? 'alex@example.com' : '',
        validity: { valid: true },
        checked: false,
    }]));
    const submit = { disabled: false, attrs: {}, setAttribute(key, value) { this.attrs[key] = value; } };
    const feedback = { hidden: true, textContent: '', dataset: {} };
    const passwordRuleFeedback = { hidden: true, textContent: '', dataset: { defaultHint: 'Use at least 8 characters with a letter, a number, and a special character.' }, classList: { toggle() {} } };
    const handlers = {};
    const form = {
        dataset: {},
        elements: { namedItem: (key) => fields[key] },
        querySelector: (query) => query.includes('submit') ? submit : query.includes('rule-feedback') ? passwordRuleFeedback : feedback,
        addEventListener: (type, callback) => { handlers[type] = callback; },
        checkValidity: () => true,
    };
    const originalDocument = globalThis.document;
    const originalWindow = globalThis.window;
    globalThis.document = { querySelectorAll: () => [form] };
    globalThis.window = { addEventListener: () => {} };
    try {
        setupCustomerRegistration();
        assert.equal(submit.disabled, true);
        fields.password.value = 'password123';
        handlers.input();
        assert.equal(submit.disabled, true);
        assert.equal(passwordRuleFeedback.hidden, false);
        assert.match(passwordRuleFeedback.textContent, /special character/);
        fields.password.value = 'password123!';
        fields.password_confirmation.value = 'mismatch123';
        fields.terms.checked = true;
        handlers.input();
        assert.equal(feedback.textContent, 'Passwords do not match.');
        assert.equal(submit.disabled, true);
        fields.password_confirmation.value = 'password123!';
        handlers.input();
        assert.equal(feedback.textContent, 'Passwords match.');
        assert.equal(passwordRuleFeedback.hidden, false);
        assert.match(passwordRuleFeedback.textContent, /at least 8 characters/);
        assert.equal(submit.disabled, false);
        fields.terms.checked = false;
        handlers.change();
        assert.equal(submit.disabled, true);
        let prevented = false;
        handlers.submit({ preventDefault: () => { prevented = true; }, stopImmediatePropagation: () => {} });
        assert.equal(prevented, true);
    } finally {
        globalThis.document = originalDocument;
        globalThis.window = originalWindow;
    }
});
