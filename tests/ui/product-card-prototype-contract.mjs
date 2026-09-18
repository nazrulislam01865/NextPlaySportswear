import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const tokens = read('resources/js/storefront/styles/tokens.css');
const card = read('resources/js/storefront/components/common/ProductCard.vue');

const requiredTokens = [
  '--np-product-card-border: #f5f6f8;',
  '--np-product-card-body-bg: var(--np-color-white);',
  '--np-product-card-action-bg: #f5f6f8;',
  '--np-product-card-body-height: 162.5px;',
  '--np-product-card-action-height: 49px;',
  '--np-product-card-wishlist-size: 38px;',
  '--np-product-card-wishlist-offset: 13px;',
  '--np-home-badge-size: 12px;',
];
for (const token of requiredTokens) {
  assert.ok(tokens.includes(token), `Missing prototype token: ${token}`);
}

const requiredCardRules = [
  'background:var(--np-product-card-body-bg);',
  'height:var(--np-product-card-body-height);',
  'height:var(--np-product-card-action-height);',
  'width:var(--np-product-card-wishlist-size);',
  'top:var(--np-product-card-wishlist-offset);',
  'object-fit:cover;',
  'border-color:var(--np-color-orange);',
  'background:var(--np-color-navy-950);',
  'background:var(--np-color-orange);',
];
for (const rule of requiredCardRules) {
  assert.ok(card.includes(rule), `ProductCard is not using prototype rule: ${rule}`);
}

for (const path of [
  'resources/js/storefront/features/home/components/NewArrivals.vue',
  'resources/js/storefront/features/home/components/BestChoices.vue',
  'resources/js/storefront/features/home/components/MakeItYours.vue',
]) {
  assert.ok(read(path).includes('card-variant="new-arrivals"'), `${path} must reuse the prototype card variant`);
}

console.log('Product card prototype contract passed.');
