import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const cssBlock = (source, selector) => {
  const start = source.indexOf(selector);
  assert.notEqual(start, -1, `${selector} must exist`);
  const open = source.indexOf('{', start);
  const close = source.indexOf('}', open);
  return source.slice(open + 1, close);
};

test('Vue homepage loads Expose from Fontshare and removes the old Google font families', () => {
  const blade = read('resources/views/storefront-vue/home.blade.php');
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const base = read('resources/js/storefront/styles/base.css');
  const footer = read('resources/js/storefront/components/layout/StorefrontFooter.vue');

  assert.match(blade, /https:\/\/api\.fontshare\.com\/v2\/css\?f\[\]=expose@400,500,700,800,900&display=swap/);
  assert.doesNotMatch(blade, /fonts\.googleapis\.com|fonts\.gstatic\.com|family=Inter|family=Oswald/);
  assert.match(tokens, /--np-font-primary:\s*'Expose'/);
  assert.match(base, /#nextplay-storefront\s*\{[\s\S]*?font-family:\s*var\(--np-font-primary\)/);
  assert.doesNotMatch(footer, /font-family:\s*Arial/);
});

test('Shop by Sport uses the compact Expose button typography from the supplied template', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');

  assert.match(tokens, /--np-home-sport-link-size:\s*var\(--np-type-cta-small-size\)/);
  assert.match(tokens, /--np-home-sport-link-weight:\s*var\(--np-type-cta-regular-weight\)/);
  const link = cssBlock(sport, '.np-sport-hero nav a');
  assert.match(link, /font-family:\s*var\(--np-home-sport-link-font-family\)/);
  assert.match(link, /font-size:\s*var\(--np-home-sport-link-size\)/);
});

test('new-arrivals product title clamps exactly to two 22px Expose lines without a third-line ellipsis row', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(tokens, /--np-home-product-title-line-height:\s*22px/);
  assert.match(tokens, /--np-home-product-title-height:\s*44px/);

  const title = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__title');
  assert.match(title, /height:\s*var\(--np-home-product-title-height\)/);
  assert.match(title, /min-height:\s*var\(--np-home-product-title-height\)/);
  assert.match(title, /max-height:\s*var\(--np-home-product-title-height\)/);
  assert.match(title, /line-height:\s*var\(--np-home-product-title-line-height\)/);
  assert.match(title, /-webkit-line-clamp:\s*2/);
  assert.match(title, /overflow:\s*hidden/);

  assert.match(card, /@media\(max-width:640px\)[\s\S]*\.np-product-card--new-arrivals \.np-product-card__title\s*\{[^}]*height:\s*var\(--np-home-product-title-height\)/);
});
