import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const tokens = read('resources/js/storefront/styles/tokens.css');

const requiredTypography = [
  '--np-home-product-action-size: 16.5px;',
  '--np-home-product-action-weight: 700;',
  '--np-home-product-price-size: 20px;',
  '--np-home-product-price-weight: 800;',
];

for (const token of requiredTypography) {
  assert.ok(tokens.includes(token), `Missing requested typography token: ${token}`);
}

const requiredGeometry = [
  '--np-product-card-body-height: 162.5px;',
  '--np-product-card-action-height: 49px;',
  '--np-product-card-wishlist-size: 38px;',
  '--np-product-card-wishlist-offset: 13px;',
];

for (const token of requiredGeometry) {
  assert.ok(tokens.includes(token), `Card geometry must remain unchanged: ${token}`);
}


const home = read('resources/views/storefront-vue/home.blade.php');
assert.ok(
  home.includes('expose@400,500,700,800,900'),
  'Expose 800 must be loaded so requested 800 weight is rendered instead of mapping to another face'
);

console.log('Product card CTA/price typography contract passed.');
