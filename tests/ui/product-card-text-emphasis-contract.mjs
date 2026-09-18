import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync(new URL('../../resources/js/storefront/styles/tokens.css', import.meta.url), 'utf8');

for (const token of [
  '--np-home-product-action-size: 16.5px;',
  '--np-home-product-action-weight: 700;',
  '--np-home-badge-size: 12px;',
  '--np-home-product-meta-size: 13px;',
]) {
  assert.ok(tokens.includes(token), `Missing requested typography token: ${token}`);
}

for (const unchanged of [
  '--np-home-product-body-min-height: 162.5px;',
  '--np-product-card-action-height: 49px;',
  '--np-product-card-badge-height: 24px;',
]) {
  assert.ok(tokens.includes(unchanged), `Card geometry changed unexpectedly: ${unchanged}`);
}

console.log('Product card text emphasis contract passed.');
