import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const slots = read('app/Support/BulkQuoteIcons.php');
const admin = read('resources/views/admin/bulk-quote-icons/edit.blade.php');
const field = read('resources/views/components/admin/bulk-quote-icon-field.blade.php');
const form = read('resources/views/storefront/bulk-quote/create.blade.php');
const section = read('resources/views/components/storefront/bulk-quote/section.blade.php');
const script = read('public/js/nextplay-quote-builder.js');
const css = read('public/css/nextplay-quote-builder.css');

// Ensures the admin upload system actually controls the redesigned form, not
// merely an obsolete set of heading elements.
test('The same persisted upload slots control contact and delivery heading icons', () => {
  assert.match(slots, /'contact' => 'Contact Information'/);
  assert.match(slots, /'delivery' => 'Delivery Requirements'/);
  assert.match(section, /'user' => 'contact'/);
  assert.match(section, /'truck', 'shipping' => 'delivery'/);
  assert.match(section, /BulkQuoteIcons::url\(\$iconSlot\)/);
  assert.match(read('resources/views/storefront/bulk-quote/partials/contact-delivery.blade.php'), /icon="user"/);
  assert.match(read('resources/views/storefront/bulk-quote/partials/contact-delivery.blade.php'), /icon="truck"/);
  assert.match(css, /\.npq-page \.npq-shared-details \.bulk-quote-section-icon\s*\{[^}]*width:\s*52px;\s*height:\s*52px/s);
  assert.match(css, /\.npq-page \.npq-shared-details \.bulk-quote-section-layout\s*\{\s*grid-template-columns:\s*52px/);
});

test('Edit and Remove icons are editable using existing file-based admin icon settings', () => {
  for (const key of ['edit', 'remove']) {
    assert.match(slots, new RegExp(`'${key}' =>`));
    assert.match(form, new RegExp(`BulkQuoteIcons::url\\('${key}'\\)`));
    assert.match(form, new RegExp(`id="npq-icon-${key}"`));
    assert.match(script, new RegExp(`el\\('npq-icon-${key}'\\)\\.content\\.cloneNode\\(true\\)`));
  }
  assert.match(admin, /@foreach\(\['edit', 'remove'\] as \$key\)/);
  assert.match(field, /name="icons\[\{\{ \$iconKey \}\}\]"/);
  assert.match(field, /name="remove\[\{\{ \$iconKey \}\}\]"/);
  assert.match(field, /Action icon · 18 × 18px/);
  assert.match(css, /\.npq-page \.npq-card-actions \.npq-action-icon\s*\{[^}]*width:\s*18px; height:\s*18px;/s);
});

test('Changing the icons does not change the product buttons handlers, form fields or database', () => {
  assert.match(script, /edit\.addEventListener\('click', \(\) => loadProduct\(product, index\)\)/);
  assert.match(script, /remove\.addEventListener\('click', \(\) => \{/);
  assert.match(script, /products\.splice\(index, 1\)/);
  assert.match(slots, /CONFIG_PATH = 'nextplay\/bulk-quote-icons\.json'/);
});
