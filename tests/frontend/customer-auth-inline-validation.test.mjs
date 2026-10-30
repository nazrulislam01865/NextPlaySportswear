import test from 'node:test';
import assert from 'node:assert/strict';
import { authFieldError, setupCustomerAuthInlineValidation } from '../../resources/js/customer-auth-inline-validation.js';

test('email validation gives a specific inline message, never browser wording', () => {
    assert.equal(authFieldError({ type: 'email', value: '', required: true }), 'Enter your email address.');
    assert.equal(authFieldError({ type: 'email', value: 'bad-email', required: true }), 'Enter a valid email address.');
    assert.equal(authFieldError({ type: 'email', value: 'customer@example.com', required: true }), '');
    assert.equal(authFieldError({ type: 'password', value: '', required: true }), 'Enter your password.');
});

test('signup and forgot password use the same inline validator and reject invalid submissions', () => {
    const field = {
        name: 'email', type: 'email', required: true, value: '', dataset: {},
        attributes: {}, setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        getAttribute(name) { return this.attributes[name]; },
        focus() { this.focused = true; },
        handlers: {}, addEventListener(name, handler) { this.handlers[name] = handler; },
    };
    const error = { id: 'register-email-error', hidden: true, textContent: '', dataset: {} };
    const handlers = {};
    const form = {
        dataset: {},
        querySelectorAll: () => [field],
        querySelector: (selector) => selector === '[data-auth-error-for="email"]' ? error : null,
        addEventListener(name, handler) { handlers[name] = handler; },
    };
    const oldDocument = globalThis.document;
    globalThis.document = { querySelectorAll: () => [form] };
    try {
        setupCustomerAuthInlineValidation();
        assert.equal(form.noValidate, true);
        let prevented = 0;
        handlers.submit({ preventDefault() { prevented++; }, stopImmediatePropagation() {} });
        assert.equal(prevented, 1);
        assert.equal(error.hidden, false);
        assert.equal(error.textContent, 'Enter your email address.');
        assert.equal(field.getAttribute('aria-invalid'), 'true');
        assert.equal(field.focused, true);

        field.value = 'invalid-email';
        field.handlers.input();
        assert.equal(error.textContent, 'Enter a valid email address.');

        field.value = 'customer@example.com';
        field.handlers.input();
        assert.equal(error.hidden, true);
        assert.equal(field.getAttribute('aria-invalid'), undefined);
        handlers.submit({ preventDefault() { prevented++; }, stopImmediatePropagation() {} });
        assert.equal(prevented, 1);
        assert.equal(form.dataset.authSubmitted, 'true');
    } finally { globalThis.document = oldDocument; }
});

test('inline errors appear on blur but not while typing into untouched fields', () => {
    const field = {
        name: 'email', type: 'email', required: true, value: '', dataset: {},
        attributes: {}, setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        getAttribute(name) { return this.attributes[name]; },
        handlers: {}, addEventListener(name, handler) { this.handlers[name] = handler; },
    };
    const error = { id: 'recovery-email-error', hidden: true, textContent: '', dataset: {} };
    const form = { dataset: {}, querySelectorAll: () => [field], querySelector: () => error, addEventListener() {} };
    const oldDocument = globalThis.document;
    globalThis.document = { querySelectorAll: () => [form] };
    try {
        setupCustomerAuthInlineValidation();
        field.value = 'bad';
        field.handlers.input();
        assert.equal(error.hidden, true);
        field.handlers.blur();
        assert.equal(error.hidden, false);
        assert.equal(error.textContent, 'Enter a valid email address.');
    } finally { globalThis.document = oldDocument; }
});
