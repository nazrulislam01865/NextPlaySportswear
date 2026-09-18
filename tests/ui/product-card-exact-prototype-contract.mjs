import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const tokens = read('resources/js/storefront/styles/tokens.css');
const card = read('resources/js/storefront/components/common/ProductCard.vue');

const expectedTokens = [
  '--np-product-card-body-height: 162.5px;',
  '--np-product-card-action-height: 49px;',
  '--np-product-card-wishlist-size: 38px;',
  '--np-product-card-wishlist-offset: 13px;',
  '--np-product-card-badge-height: 24px;',
  '--np-product-card-badge-padding-inline: 7px;',
  '--np-product-card-badge-gap: 5px;',
  '--np-product-card-body-padding-inline: 18px;',
  '--np-product-card-action-bg: #f5f6f8;',
  '--np-product-card-meta: #79869a;',
  '--np-product-card-title: #21385a;',
  '--np-home-product-action-size: 16.5px;',
];

for (const token of expectedTokens) {
  assert.ok(tokens.toLowerCase().includes(token), `Missing exact prototype token: ${token}`);
}

const exactRules = [
  'height:auto;',
  'flex:0 0 var(--np-product-card-body-height);',
  'height:var(--np-product-card-body-height);',
  'max-height:var(--np-product-card-body-height);',
  'padding:20px var(--np-product-card-body-padding-inline) 20px;',
  'height:var(--np-product-card-action-height);',
  'flex:0 0 var(--np-product-card-action-height);',
  'min-height:0;',
  'padding:0 var(--np-product-card-badge-padding-inline);',
  'margin-left:var(--np-product-card-badge-gap);',
];

for (const rule of exactRules) {
  assert.ok(card.includes(rule), `ProductCard missing exact prototype rule: ${rule}`);
}

assert.ok(card.includes('font-size:var(--np-home-product-title-size);'));
assert.ok(card.includes('font-weight:var(--np-home-product-title-weight);'));
assert.ok(card.includes('line-height:var(--np-home-product-title-line-height);'));
assert.ok(card.includes('font-size:var(--np-home-product-action-size);'));
assert.ok(card.includes('font-weight:var(--np-home-product-action-weight);'));
assert.ok(card.includes('object-fit:cover;'));
assert.ok(card.includes('border-color:var(--np-color-orange);'));
assert.ok(card.includes('background:var(--np-color-orange);'));

console.log('Exact product card prototype contract passed.');
