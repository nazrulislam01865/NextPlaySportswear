import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const view = read('resources/views/storefront/bulk-quote/create.blade.php');
const product = read('resources/views/components/storefront/bulk-quote/product-information.blade.php');
const validator = read('public/js/bulk-quote-form-validation.js');
const css = read('public/css/bulk-quote-form-system.css');
const error = read('resources/views/components/storefront/bulk-quote/field-error.blade.php');

test('server and client error lists are not duplicated above bulk quote form', () => {
    assert.doesNotMatch(view, /bulk-quote-error-summary|bulk-quote-client-summary|Please correct the following:/);
    assert.doesNotMatch(validator, /bulk-quote-client-summary|Please correct the following:/);
    assert.match(validator, /const first = errors\[0\]\.field/);
    assert.match(validator, /event\.preventDefault\(\)/);
    assert.match(error, /@error\(\$name\)/);
});

test('all bulk quote optional labels display lowercase parenthetical text', () => {
    for (const [name, content] of [['main view', view], ['product picker', product]]) {
        assert.doesNotMatch(content, /<span class="bulk-quote-optional">Optional<\/span>|\(Optional\)/, name);
    }
    for (const field of ['bulk-budget', 'bulk-postal-code', 'bulk-shipping-method', 'bulk-event-date', 'bulk-attachment', 'bulk-notes']) {
        assert.match(view, new RegExp(`<label[^>]*for="${field}"[^>]*>[^\n]*<span class="bulk-quote-optional">\\(optional\\)<\/span>`), field);
    }
    assert.match(product, /Search for a product <span class="bulk-quote-optional">\(optional\)<\/span>/);
    assert.match(product, /Additional Product Notes <span class="bulk-quote-optional">\(optional\)<\/span>/);
});

test('optional labels are plain muted normal weight 14px alongside labels', () => {
    assert.match(css, /\.bulk-quote-page \.nps-form \.bulk-quote-optional \{/);
    assert.match(css, /font: 400 14px\/1\.5 var\(--nps-font\)/);
    assert.match(css, /display: inline; margin: 0 0 0 4px; padding: 0;/);
    assert.match(css, /background: none; border: 0; border-radius: 0;/);
});

test('existing form data, sections and submission action are preserved', () => {
    for (const field of ['product_type', 'estimated_quantity', 'sizes_needed', 'artwork_details', 'full_name', 'organization', 'email', 'phone', 'shipping_address', 'needed_by', 'attachment', 'additional_notes']) {
        assert.ok(view.includes(`name="${field}"`), field);
    }
    assert.match(view, /route\('quote\.request\.store'\)/);
    assert.match(view, /x-storefront\.bulk-quote\.field-error/);
    assert.match(view, /x-storefront\.bulk-quote\.product-information/);
});
