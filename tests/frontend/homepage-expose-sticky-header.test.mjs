import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('homepage typography uses the centralized Expose type system from the supplied spec', () => {
  const index = read('resources/js/storefront/styles/index.css');
  const blade = read('resources/views/storefront-vue/home.blade.php');
  const fonts = read('resources/js/storefront/styles/fonts.css');
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const typography = read('resources/js/storefront/styles/typography.css');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');

  assert.match(index, /@import '\.\/fonts\.css';/);
  assert.match(blade, /api\.fontshare\.com\/v2\/css\?f\[\]=expose@400,500,700,800,900&display=swap/);
  assert.match(fonts, /font-family:\s*'Expose Local'/);
  assert.match(fonts, /Expose-Regular\.woff2/);
  assert.match(fonts, /Expose-Medium\.woff2/);
  assert.match(fonts, /Expose-Bold\.woff2/);
  assert.match(fonts, /Expose-Black\.woff2/);

  assert.match(tokens, /--np-font-primary:\s*'Expose'/);
  assert.match(tokens, /--np-font-display:\s*var\(--np-font-primary\)/);
  assert.match(tokens, /--np-font-body:\s*var\(--np-font-primary\)/);

  assert.match(tokens, /--np-type-tag-size:\s*16px/);
  assert.match(tokens, /--np-type-tag-weight:\s*500/);
  assert.match(tokens, /--np-type-tag-line-height:\s*1\.4/);
  assert.match(tokens, /--np-type-title-1-size:\s*48px/);
  assert.match(tokens, /--np-type-title-1-weight:\s*900/);
  assert.match(tokens, /--np-type-title-1-line-height:\s*1\.15/);
  assert.match(tokens, /--np-type-title-2-size:\s*32px/);
  assert.match(tokens, /--np-type-title-3-size:\s*24px/);
  assert.match(tokens, /--np-type-title-4-size:\s*16px/);
  assert.match(tokens, /--np-type-body-1-size:\s*18px/);
  assert.match(tokens, /--np-type-body-2-size:\s*14px/);
  assert.match(tokens, /--np-type-cta-large-size:\s*18px/);
  assert.match(tokens, /--np-type-cta-regular-size:\s*16px/);
  assert.match(tokens, /--np-type-cta-small-size:\s*14px/);

  assert.match(tokens, /--np-home-hero-title-size:\s*40px/);
  assert.match(tokens, /--np-home-hero-title-weight:\s*var\(--np-type-title-weight\)/);
  assert.match(tokens, /--np-home-hero-copy-size:\s*16px/);
  assert.match(tokens, /--np-home-section-title-size:\s*21px/);
  assert.match(tokens, /--np-home-section-title-weight:\s*var\(--np-type-title-weight\)/);

  assert.match(typography, /letter-spacing:\s*0;/);
  assert.doesNotMatch(hero, /letter-spacing:\s*[-.]\d/);
});

test('main navigation header stays fixed at the viewport top after utility bar scrolls away', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');

  assert.match(tokens, /--np-header-sticky-z-index:\s*70/);
  assert.match(header, /\.np-header\s*\{[\s\S]*?position:\s*sticky;/);
  assert.match(header, /top:\s*0;/);
  assert.match(header, /z-index:\s*var\(--np-header-sticky-z-index\)/);
});
