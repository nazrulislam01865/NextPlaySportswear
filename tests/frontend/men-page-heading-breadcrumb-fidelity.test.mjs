import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('men heading and product count match the approved prototype scale', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  assert.match(tokens, /--np-men-page-title-size:\s*40px;/);
  assert.match(tokens, /--np-men-product-count-size:\s*16px;/);
});

test('breadcrumb links reuse the mega-menu underline interaction without coloring the current Men label', () => {
  const breadcrumbs = read('resources/js/storefront/components/common/Breadcrumbs.vue');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');

  assert.match(breadcrumbs, /\.np-breadcrumbs__link::after\s*\{/);
  assert.match(breadcrumbs, /height:\s*var\(--np-mega-menu-underline-height\)/);
  assert.match(breadcrumbs, /bottom:\s*calc\(-1\s*\*\s*var\(--np-mega-menu-underline-offset\)\)/);
  assert.match(breadcrumbs, /transform:\s*scaleX\(0\)/);
  assert.match(breadcrumbs, /transform-origin:\s*left/);
  assert.match(breadcrumbs, /transition:\s*transform\s+var\(--np-transition-base\)/);
  assert.match(breadcrumbs, /\.np-breadcrumbs__link:hover::after[\s\S]*\.np-breadcrumbs__link:focus-visible::after[\s\S]*transform:\s*scaleX\(1\)/);
  assert.doesNotMatch(breadcrumbs, /\.np-breadcrumbs__link:hover\s*\{[^}]*color:\s*var\(--np-color-orange\)/s);
  assert.match(men, /\{ label: 'Home', href: '\/' \}/);
  assert.match(men, /\{ label: 'Shop', href: '\/products' \}/);
  assert.match(men, /\{ label: 'Men' \}/);
  assert.doesNotMatch(men, /\{ label: 'Men', href:/);
});
