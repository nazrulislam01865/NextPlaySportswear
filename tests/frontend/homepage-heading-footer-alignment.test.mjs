import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('all homepage section headings use the New Arrivals heading size', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(tokens, /--np-home-section-title-size:\s*21px\s*;/);
  assert.match(tokens, /--np-home-new-arrivals-title-size:\s*var\(--np-home-section-title-size\)\s*;/);
  assert.match(tokens, /--np-showcase-title-size:\s*var\(--np-home-section-title-size\)\s*;/);
  assert.match(tokens, /--np-home-process-heading-size:\s*var\(--np-home-section-title-size\)\s*;/);
});

test('footer uses the same wide homepage alignment and prototype surfaces', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const footer = read('resources/js/storefront/components/layout/StorefrontFooter.vue');

  assert.match(tokens, /--np-footer-bg:\s*#ffffff\s*;/);
  assert.match(tokens, /--np-footer-band-bg:\s*#f5f6f8\s*;/);
  assert.match(footer, /\.np-footer__inner\s*\{[\s\S]*max-width:\s*var\(--np-showcase-max\)/);
  assert.match(footer, /\.np-footer__inner\s*\{[\s\S]*padding-inline:\s*var\(--np-showcase-gutter\)/);
});
