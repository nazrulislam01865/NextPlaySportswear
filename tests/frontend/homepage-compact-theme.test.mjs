import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const requiredTokens = [
  '--np-home-hero-title-size',
  '--np-home-hero-copy-size',
  '--np-home-section-title-size',
  '--np-home-section-title-weight',
  '--np-home-tile-title-size',
  '--np-home-tile-title-weight',
  '--np-home-product-meta-size',
  '--np-home-product-title-size',
  '--np-home-product-price-size',
  '--np-home-product-action-size',
  '--np-home-badge-size',
  '--np-home-tabs-size',
  '--np-best-tabs-font-size',
  '--np-home-link-size',
  '--np-home-sale-title-size',
  '--np-home-sale-copy-size',
  '--np-home-sport-title-size',
  '--np-home-sport-link-size',
  '--np-home-process-title-size',
];

test('homepage typography is centralized through compact semantic design tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  for (const token of requiredTokens) {
    assert.match(tokens, new RegExp(token), `${token} must be centralized in tokens.css`);
  }

  assert.match(tokens, /--np-home-section-title-size:\s*21px/);
  assert.match(tokens, /--np-home-section-title-weight:\s*var\(--np-type-title-weight\)/);
  assert.match(tokens, /--np-home-product-title-size:\s*16px/);
  assert.match(tokens, /--np-home-product-price-size:\s*20px/);
  assert.match(tokens, /--np-home-product-action-size:\s*16\.5px/);
});

test('homepage sections consume compact typography tokens instead of oversized local declarations', () => {
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');
  const sale = read('resources/js/storefront/features/home/components/SeasonSaleBanner.vue');
  const best = read('resources/js/storefront/features/home/components/BestChoices.vue');
  const make = read('resources/js/storefront/features/home/components/MakeItYours.vue');
  const process = read('resources/js/storefront/features/home/components/DesignProcess.vue');
  const typography = read('resources/js/storefront/styles/typography.css');

  assert.match(hero, /font-size:\s*var\(--np-home-hero-title-size\)/);
  assert.match(hero, /font-size:\s*var\(--np-home-hero-copy-size\)/);
  assert.match(sport, /font-size:\s*var\(--np-home-sport-title-size\)/);
  assert.match(sport, /font-size:\s*var\(--np-home-sport-link-size\)/);
  assert.match(sale, /font-size:\s*var\(--np-home-sale-title-size\)/);
  assert.match(sale, /font-size:\s*var\(--np-home-sale-copy-size\)/);
  assert.match(best, /font-size:\s*var\(--np-best-tabs-font-size\)/);
  assert.match(make, /font-size:\s*var\(--np-home-link-size\)/);
  assert.match(process, /font-size:\s*var\(--np-home-process-title-size\)/);
  assert.match(typography, /font-size:\s*var\(--np-home-section-title-size\)/);
  assert.match(typography, /font-weight:\s*var\(--np-home-section-title-weight\)/);
});

test('shared product card uses compact centralized homepage typography tokens', () => {
  const card = read('resources/js/storefront/components/common/ProductCard.vue');

  assert.match(card, /font-size:\s*var\(--np-home-badge-size\)/);
  assert.match(card, /font-size:\s*var\(--np-home-product-meta-size\)/);
  assert.match(card, /font-size:\s*var\(--np-home-product-title-size\)/);
  assert.match(card, /font-size:\s*var\(--np-home-product-price-size\)/);
  assert.match(card, /font-size:\s*var\(--np-home-product-action-size\)/);
});

test('homepage display and body font families remain centralized', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');

  assert.match(tokens, /--np-font-primary:\s*'Expose'/);
  assert.match(tokens, /--np-font-display:\s*var\(--np-font-primary\)/);
  assert.match(tokens, /--np-font-body:\s*var\(--np-font-primary\)/);
  assert.match(card, /font-family:\s*var\(--np-font-body\)/);
  assert.match(card, /font-family:\s*var\(--np-font-display\)/);
  assert.match(tokens, /--np-home-hero-title-font-family:\s*var\(--np-font-display\)/);
  assert.match(tokens, /--np-home-hero-copy-font-family:\s*var\(--np-font-body\)/);
  assert.match(hero, /font-family:\s*var\(--np-home-hero-title-font-family\)/);
  assert.match(hero, /font-family:\s*var\(--np-home-hero-copy-font-family\)/);
});
