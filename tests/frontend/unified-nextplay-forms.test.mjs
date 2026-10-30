import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const base = 'resources/views/storefront/';
const css = read('resources/css/nextplay-form-system.css');
const storefrontCSS = read('resources/css/storefront.css');
const bulkCSS = read('public/css/bulk-quote-form-system.css');

const groups = {
    auth: ['auth/login.blade.php', 'auth/register.blade.php', 'auth/forgot-password.blade.php', 'auth/reset-password.blade.php', 'auth/verify-email.blade.php'],
    account: ['account/profile.blade.php', 'account/addresses.blade.php', 'account/returns/create.blade.php', 'account/returns/exchange.blade.php', 'account/orders/cancel.blade.php', 'account/orders/change.blade.php', 'account/orders/reorder.blade.php', 'account/orders/partials/payment-form.blade.php'],
    checkout: ['checkout/information.blade.php', 'checkout/shipping-address.blade.php', 'checkout/shipping-method.blade.php', 'checkout/billing-address.blade.php', 'checkout/payment-method.blade.php', 'checkout/review.blade.php'],
    quotes: ['bulk-quote/create.blade.php', 'bulk-quote/guest/track.blade.php', 'bulk-quote/guest/review.blade.php', 'account/bulk-quotes/changes.blade.php', 'account/bulk-quotes/show.blade.php'],
    other: ['content/contact.blade.php', 'referral/register.blade.php', 'account/artwork/changes.blade.php'],
};

test('one storefront form stylesheet and no separate guide palette or Arial stack', () => {
    assert.match(storefrontCSS, /@import "\.\/nextplay-form-system\.css"/);
    for (const [name, value] of Object.entries({
        '--nps-navy': '--np-color-primary',
        '--nps-orange': '--np-color-secondary',
        '--nps-text': '--np-color-body',
        '--nps-muted': '--np-color-muted',
        '--nps-border': '--np-color-border',
        '--nps-error': '--np-color-error',
        '--nps-font': '--np-font-body',
    })) {
        assert.ok(css.includes(`${name}: var(${value});`), `${name} must be centralized`);
    }
    assert.doesNotMatch(css, /Arial|Helvetica|#0b2345|#c94f2c/i);
    assert.match(bulkCSS, /--nps-navy: var\(--np-color-primary\)/);
    assert.match(bulkCSS, /--nps-orange: var\(--np-color-secondary\)/);
});

test('type, control and action metrics follow the provided design handoff', () => {
    for (const rule of ['--nps-title-size: 28px','--nps-section-size: 20px',
       '--nps-body-size: 16px','--nps-label-size: 14px','--nps-help-size: 13px',
       '--nps-control: 48px','--nps-radius: 8px','--nps-field-gap: 24px',
       'min-height: 120px','outline: 3px solid var(--nps-focus)']) {
        assert.ok(css.includes(rule), rule);
    }
    assert.match(css, /@media \(max-width: 640px\)/);
    assert.match(css, /\.np-auth-proto__title/);
    assert.match(css, /input\[type="file"\]::file-selector-button/);
});

test('all primary customer-facing form families use the shared class', () => {
    for (const [section, pages] of Object.entries(groups)) {
        for (const page of pages) {
            assert.match(read(base + page), /<form[^>]*\bclass="[^"]*nps-form/, `${section}: ${page}`);
        }
    }
    assert.doesNotMatch(read('resources/css/admin.css'), /nextplay-form-system\.css/);
});

test('standard field components use parenthetical optional labels and inline errors', () => {
    for (const name of ['account', 'checkout']) {
        const view=read(`resources/views/components/storefront/${name}/form-field.blade.php`);
        assert.match(view, /nps-optional/);
        assert.match(view, /nps-required/);
        assert.match(view, /nps-help/);
        assert.match(view, /nps-error/);
    }
});

test('simple forms use field errors without changing complex checkout, auth or quote handling', () => {
    for (const page of ['content/contact.blade.php','account/profile.blade.php','account/addresses.blade.php']) {
        assert.match(read(base + page), /data-nps-inline/);
    }
    assert.doesNotMatch(read(base + 'checkout/payment-method.blade.php'), /data-nps-inline/);
    assert.doesNotMatch(read(base + 'bulk-quote/create.blade.php'), /data-nps-inline/);
    const js = read('resources/js/unified-form-validation.js');
    assert.match(js,/field\.setAttribute\('aria-invalid', 'true'\)/);
    assert.match(js,/event\.preventDefault\(\)/);
    assert.match(js,/firstInvalid\.focus\(\)/);
    assert.match(read('resources/js/storefront.js'), /setupUnifiedFormValidation\(\)/);
});
