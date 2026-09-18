import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const tokens = read('resources/js/storefront/styles/tokens.css');
const card = read('resources/js/storefront/components/common/ProductCard.vue');
const home = read('resources/views/storefront-vue/home.blade.php');

for (const token of [
  "--np-home-product-title-font-family: 'Expose', sans-serif;",
  '--np-home-product-title-size: 16px;',
  '--np-home-product-title-weight: 400;',
  '--np-home-product-title-line-height: 22px;',
  '--np-home-product-title-height: 44px;',
  "--np-home-product-action-font-family: 'Expose', sans-serif;",
  '--np-home-product-action-size: 16.5px;',
  '--np-home-product-action-weight: 700;',
]) {
  assert.ok(tokens.includes(token), `Missing exact product typography token: ${token}`);
}

assert.ok(
  card.includes('font-family:var(--np-home-product-title-font-family);'),
  'Product title must use the dedicated Expose title family token',
);
assert.ok(
  card.includes('font-family:var(--np-home-product-action-font-family);'),
  'Product action must use the dedicated Expose action family token',
);
assert.ok(
  card.includes('font-synthesis:none;'),
  'Product typography must disable synthetic browser font faces',
);
assert.ok(
  home.includes('https://api.fontshare.com/v2/css?f[]=expose@400,500,700,800,900&display=swap'),
  'Vue homepage must load the requested Expose weights from Fontshare',
);
assert.match(
  home,
  /<link rel="preconnect" href="https:\/\/cdn\.fontshare\.com" crossorigin>/,
  'Vue homepage must preconnect to Fontshare font assets',
);

console.log('Product card font fidelity contract passed.');
