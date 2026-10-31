import test from 'node:test';
import assert from 'node:assert/strict';
import { authFieldError, setupCustomerAuthInlineValidation } from '../../resources/js/customer-auth-inline-validation.js';

const makeField = (name, type = 'email', value = '') => ({
    name, type, required: true, value, dataset: {},
    attributes: {}, handlers: {},
    setAttribute(key, next) { this.attributes[key] = next; },
    removeAttribute(key) { delete this.attributes[key]; },
    getAttribute(key) { return this.attributes[key]; },
    focus() { this.focused = true; },
    addEventListener(key, handler) { this.handlers[key] = handler; },
});

const makeForm = (values, initialServerError = '') => {
    const fields = Object.entries(values).map(([name, value]) => makeField(name, name === 'password' ? 'password' : 'email', value));
    const errors = Object.fromEntries(fields.map((field) => [field.name, {
        id: `test-${field.name}-error`, hidden: !initialServerError, textContent: initialServerError, dataset: {},
    }]));
    const handlers = {};
    const form = {
        dataset: {},
        querySelectorAll(selector) { assert.equal(selector, '[data-auth-field]'); return fields; },
        querySelector(selector) { return errors[selector.match(/data-auth-error-for="([^"]+)"/)?.[1]] || null; },
        addEventListener(key, handler) { handlers[key] = handler; },
    };
    return { form, fields: Object.fromEntries(fields.map((field) => [field.name, field])), errors, handlers };
};

const setup = (forms) => {
    const oldDocument = globalThis.document;
    globalThis.document = { querySelectorAll: () => forms.map(({ form }) => form) };
    setupCustomerAuthInlineValidation();
    return () => { globalThis.document = oldDocument; };
};

const submit = (handlers) => {
    const event = { prevented: false, stopped: false,
        preventDefault() { this.prevented = true; },
        stopImmediatePropagation() { this.stopped = true; },
    };
    handlers.submit(event);
    return event;
};

test('email validation uses clear inline messages', () => {
    assert.equal(authFieldError({ type: 'email', value: '', required: true }), 'Enter your email address.');
    assert.equal(authFieldError({ type: 'email', value: 'bad-email', required: true }), 'Enter a valid email address.');
    assert.equal(authFieldError({ type: 'email', value: 'customer@example.com', required: true }), '');
    assert.equal(authFieldError({ type: 'password', value: '', required: true }), 'Enter your password.');
});

test('login with prefilled invalid email never validates on autofocus/blur/click elsewhere', () => {
    const login = makeForm({ email: 'landingadmin', password: 'secretpass' });
    const restore = setup([login]);
    try {
        assert.equal(login.form.noValidate, true);
        assert.equal(login.fields.email.handlers.blur, undefined, 'no blur validation listener');
        assert.equal(login.errors.email.hidden, true);
        // Simulate typing an invalid email, then clicking the password field.
        login.fields.email.value = 'still-wrong';
        login.fields.email.handlers.input();
        assert.equal(login.errors.email.hidden, true);
        assert.equal(login.fields.email.getAttribute('aria-invalid'), undefined);
        // Only the actual login attempt validates the fields.
        const result = submit(login.handlers);
        assert.equal(result.prevented, true);
        assert.equal(result.stopped, true);
        assert.equal(login.errors.email.textContent, 'Enter a valid email address.');
        assert.equal(login.errors.email.hidden, false);
        assert.equal(login.fields.email.focused, true);
        assert.equal(login.fields.email.getAttribute('aria-invalid'), 'true');
        login.fields.email.value = 'hello@example.com';
        login.fields.email.handlers.input();
        assert.equal(login.errors.email.hidden, true);
        assert.equal(login.fields.email.getAttribute('aria-invalid'), undefined);
        assert.equal(submit(login.handlers).prevented, false);
    } finally { restore(); }
});

test('signup and password recovery only validate after submit, including restored/empty emails', () => {
    const signup = makeForm({ email: '' });
    const recovery = makeForm({ email: 'invalid-restored-value' });
    const restore = setup([signup, recovery]);
    try {
        for (const subject of [signup, recovery]) {
            assert.equal(subject.fields.email.handlers.blur, undefined);
            assert.equal(subject.errors.email.hidden, true);
            subject.fields.email.handlers.input();
            assert.equal(subject.errors.email.hidden, true);
            assert.equal(submit(subject.handlers).prevented, true);
            assert.equal(subject.errors.email.hidden, false);
            subject.fields.email.value = 'valid@example.com';
            subject.fields.email.handlers.input();
            assert.equal(subject.errors.email.hidden, true);
            assert.equal(submit(subject.handlers).prevented, false);
        }
        assert.equal(signup.form.noValidate, true);
        assert.equal(recovery.form.noValidate, true);
    } finally { restore(); }
});

test('invalid submit renders all errors and subsequent edits update only their own error', () => {
    const login = makeForm({ email: 'bad', password: '' });
    const restore = setup([login]);
    try {
        assert.equal(submit(login.handlers).prevented, true);
        assert.equal(login.errors.email.textContent, 'Enter a valid email address.');
        assert.equal(login.errors.password.textContent, 'Enter your password.');
        login.fields.password.value = 'password123';
        login.fields.password.handlers.input();
        assert.equal(login.errors.password.hidden, true);
        assert.equal(login.errors.email.hidden, false);
    } finally { restore(); }
});

test('server validation feedback is preserved until the user actually edits that field', () => {
    const signup = makeForm({ email: 'used@example.com' }, 'This email address is already registered.');
    const restore = setup([signup]);
    try {
        assert.equal(signup.errors.email.hidden, false);
        assert.equal(signup.errors.email.textContent, 'This email address is already registered.');
        signup.fields.email.value = 'new@example.com';
        signup.fields.email.handlers.input();
        assert.equal(signup.errors.email.hidden, true);
        assert.equal(signup.fields.email.getAttribute('aria-invalid'), undefined);
    } finally { restore(); }
});

test('reinitialization cannot attach multiple handlers to the same authentication form', () => {
    const login = makeForm({ email: 'bad' });
    const restore = setup([login]);
    try {
        const firstSubmit = login.handlers.submit;
        const firstInput = login.fields.email.handlers.input;
        setupCustomerAuthInlineValidation();
        assert.equal(login.handlers.submit, firstSubmit);
        assert.equal(login.fields.email.handlers.input, firstInput);
    } finally { restore(); }
});
