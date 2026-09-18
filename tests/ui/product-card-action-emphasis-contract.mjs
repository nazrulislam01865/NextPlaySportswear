import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync(new URL('../../resources/js/storefront/styles/tokens.css', import.meta.url), 'utf8');

assert.ok(
  tokens.includes('--np-home-product-action-size: 16.5px;'),
  'product-card action font size must be 16.5px'
);
assert.ok(
  tokens.includes('--np-home-product-action-weight: 700;'),
  'product-card action font weight must be Expose Bold 700'
);

console.log('Product card action emphasis contract passed.');
