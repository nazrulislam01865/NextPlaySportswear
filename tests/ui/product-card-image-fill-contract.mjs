import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const card = readFileSync(new URL('../../resources/js/storefront/components/common/ProductCard.vue', import.meta.url), 'utf8');
const selector = '.np-product-card--new-arrivals .np-product-card__image';
const start = card.indexOf(selector);
assert.notEqual(start, -1, 'prototype product image rule must exist');
const open = card.indexOf('{', start);
const close = card.indexOf('}', open);
const rule = card.slice(open + 1, close);

assert.match(rule, /object-fit:\s*cover\s*;/, 'prototype image must fill the media area edge-to-edge');
assert.match(rule, /padding:\s*0\s*;/, 'prototype image must not have inner padding');

console.log('Product card image fill contract passed.');
