import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const form = read('resources/views/storefront/bulk-quote/create.blade.php');
const system = read('public/css/bulk-quote-form-system.css');
const validator = read('public/js/bulk-quote-form-validation.js');
const layout = read('resources/views/components/layouts/storefront.blade.php');
const originalRules = read('app/Http/Requests/Storefront/StoreBulkQuoteRequest.php');

test('bulk quote exclusively uses the scoped production form system', () => {
    assert.match(form, /bulk-quote-card nps-form/);
    assert.match(layout, /routeIs\('quote\.request'\)[\s\S]+bulk-quote-form-system\.css/);
    assert.match(form, /bulk-quote-form-validation\.js/);
    assert.match(system, /--nps-font:\s*var\(--np-font-body\)/);
    assert.doesNotMatch(system, /\bfont-family:\s*Arial/);
});

test('design-system sizes and visual states are included', () => {
    for (const token of [
        '--nps-control: 48px', 'font-size: 28px', 'font-size: 24px',
        'font: 700 20px/1.4', 'font: 600 14px/1.5',
        'font: 400 16px/1.5', 'font: 400 13px/1.5',
        '--nps-focus: var(--np-color-focus)', '--nps-error: var(--np-color-error)',
        'min-height: 120px', 'grid-template-columns: repeat(2',
    ]) assert.ok(system.includes(token), `Missing CSS guideline: ${token}`);
    assert.match(system, /@media \(max-width: 640px\)/);
});

test('all existing bulk quote payload fields and sections are kept', () => {
    for (const section of [
        'Product & Customisation', 'Contact Information',
        'Delivery Requirements', 'Attachments & Notes',
    ]) assert.ok(form.includes(section), `Missing section ${section}`);
    for (const field of [
        'product_type', 'estimated_quantity', 'sizes_needed', 'budget_range',
        'artwork_details', 'full_name', 'organization',
        'email', 'phone_country_code_id', 'phone', 'shipping_address',
        'postal_code', 'preferred_shipping_method', 'needed_by', 'event_date',
        'attachment', 'additional_notes',
    ]) assert.ok(form.includes(`name="${field}"`), `Missing payload field ${field}`);
    assert.match(read('resources/views/components/storefront/bulk-quote/customization-option.blade.php'), /name="customization_types\[\]"/);
    assert.match(form, /x-storefront\.address-geography/);
    assert.match(form, /x-storefront\.bulk-quote\.product-information/);
    assert.match(form, /g-recaptcha/);
    assert.match(form, /route\('quote\.request\.store'\)/);
});

test('form validation uses inline errors without native browser bubbles', () => {
    assert.match(form, /novalidate/);
    assert.doesNotMatch(form, /bulk-quote-client-summary|bulk-quote-error-summary/);
    assert.match(validator, /event\.preventDefault\(\)/);
    assert.match(validator, /aria-invalid/);
    assert.match(validator, /aria-describedby/);
    assert.match(validator, /minimum|Enter at least/);
    assert.match(validator, /file\.size|files\[0\]\.size/);
    assert.match(validator, /const first = errors\[0\]\.field/);
});

test('original server submission rules retain their required and optional constraints', () => {
    assert.match(originalRules, /'attachment' => \['nullable', 'file', 'max:10240'\]/);
    assert.match(originalRules, /'postal_code' => \['nullable'/);
    assert.match(originalRules, /'g-recaptcha-response'/);
    assert.match(form, /Postcode <span class="bulk-quote-optional">\(optional\)/);
    assert.match(form, /class="bulk-quote-file-control"/);
});

test('assistance sidebar uses centralized Expose typography', () => {
    const sidebar = form.match(/<aside class="bulk-quote-sidebar"[\s\S]*?<\/aside>/)?.[0];
    assert.ok(sidebar);
    for (const copy of ['Your Request', 'What happens next?', 'Need a hand?', 'No payment required to request a quote.']) {
        assert.ok(sidebar.includes(copy), `Missing sidebar content: ${copy}`);
    }
    assert.match(system, /\.bulk-quote-page \.bulk-quote-sidebar\s*\{\s*font-family:\s*var\(--nps-font\)/);
    for (const rule of [
        '.bulk-quote-sidebar .bulk-quote-side-heading h2',
        '.bulk-quote-sidebar .bulk-quote-side-lead',
        '.bulk-quote-sidebar .bulk-quote-summary-row dt',
        '.bulk-quote-sidebar .bulk-quote-summary-row dd',
        '.bulk-quote-sidebar .bulk-quote-next-steps strong',
        '.bulk-quote-sidebar .bulk-quote-next-steps p',
        '.bulk-quote-sidebar .bulk-quote-whatsapp-link',
        '.bulk-quote-sidebar .bulk-quote-security-note p',
        '.bulk-quote-sidebar .bulk-quote-security-note strong',
    ]) assert.ok(system.includes(rule), `Missing sidebar typography: ${rule}`);
    assert.match(layout, /bulk-quote-form-system\.css'\) \}\}\?v=20261030c/);
});
