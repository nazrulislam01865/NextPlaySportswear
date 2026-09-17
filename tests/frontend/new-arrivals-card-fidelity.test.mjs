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

test('new arrivals keeps the shared ProductCard reusable instead of duplicating card markup', () => {
  const newArrivals = read('resources/js/storefront/features/home/components/NewArrivals.vue');
  const carousel = read('resources/js/storefront/components/common/ProductCarousel.vue');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(newArrivals, /ProductCarousel/);
  assert.match(carousel, /ProductCard/);
  assert.match(card, /variant\?:\s*'default'\s*\|\s*'new-arrivals'/);
});

test('shared product card chooses ADD TO CART or CUSTOMIZE from product capability', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(card, /product\.is_customizable/);
  assert.match(card, /'CUSTOMIZE'/);
  assert.match(card, /'ADD TO CART'/);
  assert.match(card, /\{\{\s*actionLabel\s*\}\}/);
});

test('new arrivals card uses prototype body typography rather than condensed display type', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  const meta = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__meta');
  assert.match(meta, /font-family:\s*var\(--np-font-body\)/);
  assert.match(meta, /font-size:\s*var\(--np-home-product-meta-size\)/);
  assert.match(meta, /font-weight:\s*var\(--np-weight-regular\)/);

  const title = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__title');
  assert.match(title, /font-family:\s*var\(--np-font-body\)/);
  assert.match(title, /font-size:\s*var\(--np-home-product-title-size\)/);
  assert.match(title, /font-weight:\s*var\(--np-home-product-title-weight\)/);
  assert.match(title, /line-height:\s*1\.45/);
  assert.match(title, /-webkit-line-clamp:\s*2/);

  const pill = cssBlock(card, '.np-pill');
  assert.match(pill, /font-family:\s*var\(--np-font-body\)/);
  assert.match(pill, /font-size:\s*var\(--np-home-badge-size\)/);
});

test('new arrivals card keeps the prototype vertical proportions', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  const media = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__media');
  assert.match(media, /aspect-ratio:\s*0\.86/);

  const body = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__body');
  assert.match(body, /min-height:\s*var\(--np-home-product-body-min-height\)/);
  assert.match(body, /padding:\s*20px 20px 22px/);

  const price = cssBlock(card, '.np-product-card--new-arrivals :deep(.np-price)');
  assert.match(price, /margin-top:\s*auto/);
  assert.match(price, /padding-top:\s*18px/);
  assert.match(price, /border-top:\s*1px solid var\(--np-home-divider\)/);

  const priceStrong = cssBlock(card, '.np-product-card--new-arrivals :deep(.np-price strong)');
  assert.match(priceStrong, /font-size:\s*var\(--np-home-product-price-size\)/);

  const action = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__action');
  assert.match(action, /min-height:\s*var\(--np-home-product-action-height\)/);
  assert.match(action, /font-size:\s*var\(--np-home-product-action-size\)/);
});

test('new arrivals hover treatment stays the thin orange prototype state', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(card, /\.np-product-card--new-arrivals:hover[\s\S]*border-color:\s*var\(--np-color-orange\)/);
  assert.match(card, /\.np-product-card--new-arrivals:hover \.np-product-card__action[\s\S]*background:\s*var\(--np-color-orange\)/);
});

test('shared product card exposes category/subcategory metadata and a second gallery image on hover', () => {
  const types = read('resources/js/storefront/types/product.ts');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(types, /subcategory\?:\s*string/);
  assert.match(types, /gallery\?:\s*Array</);
  assert.match(card, /props\.product\.subcategory/);
  assert.match(card, /const\s+secondaryImage\s*=\s*computed/);
  assert.match(card, /class="np-product-card__image np-product-card__image--primary"/);
  assert.match(card, /class="np-product-card__image np-product-card__image--secondary"/);
  assert.match(card, /\.np-product-card--new-arrivals:hover \.np-product-card__image--primary[\s\S]*opacity:\s*0/);
  assert.match(card, /\.np-product-card--new-arrivals:hover \.np-product-card__image--secondary[\s\S]*opacity:\s*1/);
  assert.match(card, /\.np-product-card--new-arrivals:focus-within \.np-product-card__image--secondary[\s\S]*opacity:\s*1/);
});

test('new arrivals product-card typography and spacing use the approved centralized prototype tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');
  const section = read('resources/js/storefront/features/home/components/NewArrivals.vue');

  for (const declaration of [
    '--np-home-product-meta-size: 11px',
    '--np-home-product-title-size: 14px',
    '--np-home-product-title-weight: 500',
    '--np-home-product-price-size: 18px',
    '--np-home-product-compare-price-size: 12px',
    '--np-home-product-body-min-height: 182px',
    '--np-home-product-body-min-height-mobile: 176px',
    '--np-home-product-action-height: 48px',
    '--np-home-new-arrivals-padding-top: 48px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  const action = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__action');
  assert.match(action, /min-height:\s*var\(--np-home-product-action-height\)/);

  const compare = cssBlock(card, '.np-product-card--new-arrivals :deep(.np-price del)');
  assert.match(compare, /font-size:\s*var\(--np-home-product-compare-price-size\)/);

  const sectionBlock = cssBlock(section, '.np-products-section');
  assert.match(sectionBlock, /padding-top:\s*var\(--np-home-new-arrivals-padding-top\)/);
  assert.match(card, /@media\(max-width:640px\)[\s\S]*\.np-product-card--new-arrivals \.np-product-card__body \{[^}]*min-height:\s*var\(--np-home-product-body-min-height-mobile\)/);
});

test('shared product card only shows compare price for a genuine active discount', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');
  const price = read('resources/js/storefront/components/common/PriceDisplay.vue');

  assert.match(card, /const\s+hasActiveDiscount\s*=\s*computed/);
  assert.match(card, /product\.discount_price/);
  assert.match(card, /originalPrice\s*>\s*discountPrice/);
  assert.match(card, /:discount-active="hasActiveDiscount"/);
  assert.match(price, /discountActive\?:\s*boolean/);
  assert.match(price, /<del\s+v-if="discountActive\s*&&\s*original"/);
});


test('shared product card price prefers authoritative numeric amount and never renders legacy From prefix', () => {
  const price = read('resources/js/storefront/components/common/PriceDisplay.vue');

  assert.doesNotMatch(price, /props\.price\s*\|\|\s*formatCurrency/);
  assert.match(price, /formatCurrency\(props\.value,\s*props\.currency\s*\|\|\s*'USD'\)/);
  assert.match(price, /replace\(\/\^From\\s\+\/i,\s*''\)/);
});

test('only Make It Yours may force the CUSTOMIZE product-card action', () => {
  const makeItYours = read('resources/js/storefront/features/home/components/MakeItYours.vue');
  const newArrivals = read('resources/js/storefront/features/home/components/NewArrivals.vue');
  const bestChoices = read('resources/js/storefront/features/home/components/BestChoices.vue');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(makeItYours, /force-customize/);
  assert.doesNotMatch(newArrivals, /force-customize/);
  assert.doesNotMatch(bestChoices, /force-customize/);
  assert.match(card, /props\.forceCustomize\s*\|\|\s*props\.product\.is_customizable/);
});
