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

test('shop by category uses the reusable category carousel with the prototype showcase variant', () => {
  const section = read('resources/js/storefront/features/home/components/ShopByCategory.vue');
  const carousel = read('resources/js/storefront/components/common/CategoryCarousel.vue');
  const tile = read('resources/js/storefront/components/common/ImageCategoryTile.vue');

  assert.match(section, /variant="showcase"/);
  assert.match(section, /controls-variant="showcase"/);
  assert.match(carousel, /variant\?:\s*'default'\s*\|\s*'showcase'/);
  assert.match(tile, /variant\?:[\s\S]*'category-showcase'/);

  const showcase = cssBlock(tile, '.np-image-tile--category-showcase');
  assert.match(showcase, /aspect-ratio:\s*1\.04/);
  assert.match(tile, /v-if="\(variant === 'audience' \|\| variant === 'category-showcase'\) && actionLabel"/);
  assert.match(tile, /\.np-image-tile--category-showcase:hover img[\s\S]*scale\(var\(--np-audience-hover-scale\)\)/);
  assert.match(tile, /\.np-image-tile--category-showcase:hover strong[\s\S]*np-audience-title-hover-lift/);
  assert.match(tile, /\.np-image-tile--category-showcase:hover \.np-image-tile__action[\s\S]*opacity:\s*1/);
});

test('best choices reuses the approved shared new-arrivals product card and compact centralized tabs', () => {
  const section = read('resources/js/storefront/features/home/components/BestChoices.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');
  assert.match(section, /card-variant="new-arrivals"/);
  assert.match(section, /FEATURED/);
  assert.match(section, /POPULAR/);
  assert.match(section, /TRENDING/);
  assert.match(section, /controls-variant="showcase"/);
  for (const declaration of [
    '--np-best-tabs-min-width: 98px',
    '--np-best-tabs-height: 40px',
    '--np-best-tabs-padding-x: 16px',
    '--np-best-tabs-font-size: 11px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }
  assert.match(section, /min-width:\s*var\(--np-best-tabs-min-width\)/);
  assert.match(section, /min-height:\s*var\(--np-best-tabs-height\)/);
  assert.match(section, /padding:\s*0 var\(--np-best-tabs-padding-x\)/);
  assert.match(section, /font-size:\s*var\(--np-best-tabs-font-size\)/);
  assert.doesNotMatch(section, /min-width:\s*120px/);
  assert.doesNotMatch(section, /min-height:\s*50px/);
});

test('make it yours reuses the shared product card while hiding prototype-omitted price and badges', () => {
  const section = read('resources/js/storefront/features/home/components/MakeItYours.vue');
  const carousel = read('resources/js/storefront/components/common/ProductCarousel.vue');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(section, /card-variant="new-arrivals"/);
  assert.match(section, /:show-price="false"/);
  assert.match(section, /:show-badges="false"/);
  assert.match(section, /:link-label="linkLabel"/);
  assert.match(section, /'Explore All'/);
  assert.match(carousel, /showPrice\?:\s*boolean/);
  assert.match(carousel, /showBadges\?:\s*boolean/);
  assert.match(card, /showPrice\?:\s*boolean/);
  assert.match(card, /showBadges\?:\s*boolean/);

  for (const declaration of [
    '--np-make-it-yours-padding-top: 72px',
    '--np-make-it-yours-padding-top-mobile: 56px',
    '--np-make-it-yours-padding-bottom: 94px',
    '--np-make-it-yours-padding-bottom-mobile: 56px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  const make = cssBlock(section, '.np-make');
  assert.match(make, /padding:\s*var\(--np-make-it-yours-padding-top\)\s+0\s+var\(--np-make-it-yours-padding-bottom\)/);
  assert.match(section, /@media\(max-width:700px\)[\s\S]*padding:\s*var\(--np-make-it-yours-padding-top-mobile\)\s+0\s+var\(--np-make-it-yours-padding-bottom-mobile\)/);
  assert.doesNotMatch(section, /padding:\s*28px 0 94px/);
});

test('season sale uses centralized sizing, typography, compact CTA, and the shared chevron hover', () => {
  const section = read('resources/js/storefront/features/home/components/SeasonSaleBanner.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(section, /<AppButton[^>]*hover-effect="chevrons"/);
  assert.match(section, />\{\{ label \}\}<\/AppButton>/);
  assert.match(section, /'SHOP SALE'/);
  assert.match(section, /UP TO 20% OFF/);

  for (const declaration of [
    '--np-home-sale-height: 500px',
    '--np-home-sale-height-mobile: 390px',
    '--np-home-sale-title-font-family: var(--np-font-display)',
    '--np-home-sale-cta-min-width: 118px',
    '--np-home-sale-cta-height: 46px',
    '--np-home-sale-cta-font-size: var(--np-type-cta-regular-size)',
    '--np-home-sale-cta-height-mobile: 44px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  const sale = cssBlock(section, '.np-sale');
  assert.match(sale, /height:\s*var\(--np-home-sale-height\)/);
  assert.match(section, /font-family:\s*var\(--np-home-sale-title-font-family\)/);
  assert.match(section, /min-width:\s*var\(--np-home-sale-cta-min-width\)/);
  assert.match(section, /min-height:\s*var\(--np-home-sale-cta-height\)/);
  assert.match(section, /font-size:\s*var\(--np-home-sale-cta-font-size\)/);
  assert.match(section, /@media\(max-width:640px\)[\s\S]*height:\s*var\(--np-home-sale-height-mobile\)/);
  assert.doesNotMatch(section, /height:\s*460px/);
  assert.doesNotMatch(section, /min-height:\s*56px/);
  assert.doesNotMatch(section, /font-size:\s*12px/);
});

test('secondary homepage sections use centralized storefront showcase tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  for (const token of [
    '--np-showcase-max',
    '--np-showcase-gutter',
    '--np-showcase-title-size',
    '--np-home-section-title-size',
    '--np-product-card-border',
    '--np-product-card-action-bg',
  ]) {
    assert.match(tokens, new RegExp(token));
  }
});

test('shop by sport quick links use centralized transparent mask styling with a hover arrow reveal', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');

  for (const declaration of [
    '--np-home-sport-title-font-family: var(--np-font-display)',
    '--np-home-sport-link-font-family: var(--np-font-display)',
    '--np-home-sport-title-size: var(--np-type-title-2-size)',
    '--np-home-sport-link-size: var(--np-type-cta-small-size)',
    '--np-home-sport-link-size-mobile: var(--np-type-cta-small-size)',
    '--np-home-sport-link-mask-bg: rgba(255, 255, 255, .10)',
    '--np-home-sport-link-mask-bg-hover: rgba(255, 255, 255, .16)',
    '--np-home-sport-link-arrow-size: 16px',
    '--np-home-sport-link-arrow-offset: 16px',
    '--np-home-sport-link-min-width: 132px',
    '--np-home-sport-link-label-hover-shift: 7px',
    '--np-home-sport-link-transition: 280ms',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(sport, /import\s*\{\s*ArrowRight\s*\}\s*from\s*'lucide-vue-next'/);
  assert.match(sport, /class="np-sport-link__label"/);
  assert.match(sport, /class="np-sport-link__arrow"/);
  assert.match(sport, /font-family:\s*var\(--np-home-sport-link-font-family\)/);
  assert.match(sport, /font-size:\s*var\(--np-home-sport-link-size\)/);
  assert.match(sport, /background:\s*var\(--np-home-sport-link-mask-bg\)/);
  assert.match(sport, /backdrop-filter:\s*blur\(var\(--np-home-sport-link-mask-blur\)\)/);
  assert.match(sport, /padding:\s*15px 22px/);
  assert.doesNotMatch(sport, /padding:\s*15px 48px 15px 22px/);
  assert.match(sport, /\.np-sport-link__arrow\{[\s\S]*position:\s*absolute/);
  assert.match(sport, /\.np-sport-link__arrow\{[\s\S]*right:\s*var\(--np-home-sport-link-arrow-offset\)/);
  assert.match(sport, /\.np-sport-link__arrow\{[\s\S]*width:\s*var\(--np-home-sport-link-arrow-size\)/);
  assert.match(sport, /\.np-sport-hero nav a:hover \.np-sport-link__label[\s\S]*translateX\(calc\(-1 \* var\(--np-home-sport-link-label-hover-shift\)\)\)/);
  assert.match(sport, /\.np-sport-hero nav a:hover[\s\S]*background:\s*var\(--np-home-sport-link-mask-bg-hover\)/);
  assert.match(sport, /\.np-sport-hero nav a:hover[\s\S]*\.np-sport-link__arrow[\s\S]*opacity:\s*1/);
  assert.match(sport, /@media\(max-width:900px\)[\s\S]*font-size:\s*var\(--np-home-sport-link-size-mobile\)/);
});

test('homepage showcase carousel controls use compact centralized sizing and exactly one dash plus one dot', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');

  for (const declaration of [
    '--np-home-showcase-control-width: 52px',
    '--np-home-showcase-control-height: 44px',
    '--np-home-showcase-control-icon-size: 18px',
    '--np-home-showcase-control-track-width: 42px',
    '--np-home-showcase-indicator-dash-width: 20px',
    '--np-home-showcase-indicator-dash-height: 2px',
    '--np-home-showcase-indicator-dot-size: 4px',
    '--np-home-showcase-indicator-gap: 6px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(controls, /variant === 'hero' \|\| variant === 'showcase'/);
  assert.match(controls, /np-carousel-controls__marker--showcase/);
  assert.match(controls, /np-carousel-controls__marker--active/);
  assert.match(controls, /grid-template-columns:\s*var\(--np-home-showcase-control-width\)\s+var\(--np-home-showcase-control-track-width\)\s+var\(--np-home-showcase-control-width\)/);
  assert.match(controls, /\.np-carousel-controls--showcase button[\s\S]*width:\s*var\(--np-home-showcase-control-width\)/);
  assert.match(controls, /\.np-carousel-controls--showcase button[\s\S]*height:\s*var\(--np-home-showcase-control-height\)/);
  assert.doesNotMatch(controls, /np-carousel-controls--showcase[\s\S]{0,500}linear-gradient\(/);

  // The banner keeps three positions while showcase controls keep two; the active position becomes the dash.
  assert.match(controls, /props\.variant === 'hero' \? 3 : 2/);
  assert.match(controls, /markerIndex - 1 === activeMarkerIndex/);
});

test('design process matches the five-column prototype with reusable step cards and centralized typography', () => {
  const section = read('resources/js/storefront/features/home/components/DesignProcess.vue');
  const step = read('resources/js/storefront/features/home/components/ProcessStepCard.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(section, /import ProcessStepCard from '\.\/ProcessStepCard\.vue'/);
  assert.match(section, /HOW TO DESIGN A T-SHIRT USING NEXTPLAY/);
  assert.match(section, /<ProcessStepCard/);
  assert.match(section, /:index="index"/);
  assert.match(section, /:visual="index\s*===?\s*0\s*\?\s*'product'\s*:\s*'placeholder'"/);
  assert.doesNotMatch(section, /CUSTOM COLOR|TEAM LOGO|PLAYER NAME|PLAYER NUMBER/);
  assert.doesNotMatch(section, /np-process__art/);

  for (const declaration of [
    '--np-home-process-max-width: 1912px',
    '--np-home-process-padding-top: 72px',
    '--np-home-process-padding-bottom: 64px',
    '--np-home-process-heading-font-family: var(--np-font-display)',
    '--np-home-process-heading-size: var(--np-home-section-title-size)',
    '--np-home-process-heading-gap: 52px',
    '--np-home-process-column-gap: 24px',
    '--np-home-process-step-number-font-family: var(--np-font-display)',
    '--np-home-process-step-number-size: var(--np-type-tag-size)',
    '--np-home-process-copy-min-height: 70px',
    '--np-home-process-step-title-font-family: var(--np-font-display)',
    '--np-home-process-step-title-size: 16px',
    '--np-home-process-step-title-weight: 700',
    '--np-home-process-step-description-font-family: var(--np-font-body)',
    '--np-home-process-step-description-size: 14px',
    '--np-home-process-step-description-line-height: var(--np-type-body-2-line-height)',
    '--np-home-process-image-gap: 14px',
    '--np-home-process-image-height: 195px',
    '--np-home-process-placeholder-size: 13px',
    '--np-home-process-preview-width: 102px',
    '--np-home-process-preview-media-height: 112px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(section, /grid-template-columns:\s*repeat\(5, minmax\(0, 1fr\)\)/);
  assert.match(section, /max-width:\s*var\(--np-home-process-max-width\)/);
  assert.match(section, /padding:\s*var\(--np-home-process-padding-top\)\s+var\(--np-home-process-gutter\)\s+var\(--np-home-process-padding-bottom\)/);
  assert.match(section, /font-family:\s*var\(--np-home-process-heading-font-family\)/);

  assert.match(step, /String\(props\.index \+ 1\)\.padStart\(2, '0'\)/);
  assert.match(step, /font-family:\s*var\(--np-home-process-step-title-font-family\)/);
  assert.match(step, /font-family:\s*var\(--np-home-process-step-description-font-family\)/);
  assert.match(step, /height:\s*var\(--np-home-process-image-height\)/);
  assert.match(step, /v-(?:if|else-if)="visual === 'product'"/);
  assert.match(step, />Image</);
});
