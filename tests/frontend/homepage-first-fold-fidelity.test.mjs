import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('first fold uses a reusable exact NextPlay wordmark component', () => {
  const component = 'resources/js/storefront/components/common/BrandLogo.vue';
  assert.equal(fs.existsSync(path.join(root, component)), true, 'BrandLogo.vue must exist');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  assert.match(header, /BrandLogo/);
});

test('utility bar matches the approved three-link layout', () => {
  const utility = read('resources/js/storefront/components/layout/UtilityBar.vue');
  assert.match(utility, /Track Your Order/);
  assert.match(utility, /Delivery &amp; Returns/);
  assert.match(utility, /Contact Support/);
  assert.doesNotMatch(utility, /CircleHelp|\/help-center|>\s*Help\s*</);
});

test('first-fold buttons are rendered through the centralized AppButton component', () => {
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
  assert.match(header, /AppButton/);
  assert.match(header, /size="header"/);
  assert.match(hero, /size="hero"/);
});

test('first-fold dimensions and colors are centralized in design tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  for (const token of [
    '--np-first-fold-max',
    '--np-first-fold-gutter',
    '--np-utility-height',
    '--np-header-height',
    '--np-hero-height',
    '--np-color-utility-bg',
    '--np-color-icon-muted',
  ]) {
    assert.match(tokens, new RegExp(token));
  }
});

test('storefront bootstrap exposes authoritative cart total for the header', () => {
  const service = read('app/Services/Storefront/StorefrontBootstrapService.php');
  const store = read('resources/js/storefront/stores/storefront.store.ts');
  assert.match(service, /'total'\s*=>\s*\(float\)/);
  assert.match(store, /cartTotal/);
});

test('approved hero content is owned by Laravel and upgrades legacy seeded homepage data', () => {
  const seeder = read('database/seeders/HomepageSlideSeeder.php');
  const migration = 'database/migrations/2026_09_16_170000_align_default_homepage_hero.php';
  assert.match(seeder, /YOUR TEAM YOUR KITS/);
  assert.match(seeder, /Performance sportswear for players, teams and clubs/);
  assert.equal(fs.existsSync(path.join(root, migration)), true, 'legacy homepage data migration must exist');
  const migrationSource = read(migration);
  assert.match(migrationSource, /Build Your Team Jersey/);
  assert.match(migrationSource, /YOUR TEAM YOUR KITS/);
});

test('hero carousel controls use the reusable centralized hero variant', () => {
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
  assert.match(controls, /variant/);
  assert.match(controls, /np-carousel-controls--hero/);
  assert.match(hero, /variant="hero"/);
  assert.match(hero, /:allow-touch-move="false"/);
  assert.match(hero, /:simulate-touch="false"/);
  assert.match(hero, /:autoplay="\{ delay: 7000, disableOnInteraction: false \}"/);
  assert.match(hero, /:modules="\[Autoplay\]"/);
  assert.match(hero, /import \{ Autoplay \} from 'swiper\/modules'/);
});

test('hero overlay colors are centralized rather than hardcoded in the component', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
  assert.match(tokens, /--np-color-hero-overlay-strong/);
  assert.match(tokens, /--np-color-hero-overlay-mid/);
  assert.match(tokens, /--np-color-hero-overlay-soft/);
  assert.doesNotMatch(hero, /rgba\(3,24,48/);
});

test('homepage cart mutations keep the API-owned header total in sync', () => {
  const store = read('resources/js/storefront/stores/storefront.store.ts');
  const actions = read('resources/js/storefront/features/home/composables/useHomeActions.ts');
  assert.match(store, /setCartSummary/);
  assert.match(actions, /setCartSummary/);
});

test('brand logo public asset is referenced as a runtime URL so Vite does not turn it into a module import', () => {
  const logo = read('resources/js/storefront/components/common/BrandLogo.vue');
  assert.match(logo, /const\s+logoSrc\s*=\s*['"]\/images\/storefront-vue\/brand\/nextplay-wordmark\.png['"]/);
  assert.match(logo, /:src="logoSrc"/);
  assert.doesNotMatch(logo, /<img\s+src="\/images\/storefront-vue\/brand\/nextplay-wordmark\.png"/);
});

test('header CTA and typography stay compact at the prototype scale', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const button = read('resources/js/storefront/components/ui/AppButton.vue');
  const nav = read('resources/js/storefront/components/layout/DesktopNavigation.vue');
  const utility = read('resources/js/storefront/components/layout/UtilityBar.vue');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  const blade = read('resources/views/storefront-vue/home.blade.php');

  for (const declaration of [
    '--np-header-cta-width: 112px',
    '--np-header-cta-height: 40px',
    '--np-header-cta-font-size: 13px',
    '--np-header-cta-font-weight: 700',
    '--np-header-cta-bg: #051f44',
    '--np-header-nav-font-size: 14px',
    '--np-header-action-font-size: 14px',
    '--np-utility-font-size: 12px',
    '--np-header-actions-gap: 11px',
    '--np-header-action-gap: 8px',
    '--np-header-cta-padding-inline: 12px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(button, /\.np-button--header[\s\S]*width:\s*var\(--np-header-cta-width\)/);
  assert.match(button, /\.np-button--header[\s\S]*height:\s*var\(--np-header-cta-height\)/);
  assert.match(button, /\.np-button--header[\s\S]*font-family:\s*var\(--np-font-display\)/);
  assert.match(button, /\.np-button--header\.np-button--navy[\s\S]*background:\s*var\(--np-header-cta-bg\)/);
  assert.match(nav, /font-family:\s*var\(--np-font-display\)/);
  assert.match(nav, /font-size:\s*var\(--np-header-nav-font-size\)/);
  assert.match(utility, /font-family:\s*var\(--np-font-display\)/);
  assert.match(header, /font-size:\s*var\(--np-header-action-font-size\)/);
  assert.match(header, /gap:\s*var\(--np-header-actions-gap\)/);
  assert.match(header, /gap:\s*var\(--np-header-action-gap\)/);
  assert.match(button, /padding:\s*0\s+var\(--np-header-cta-padding-inline\)/);
  assert.doesNotMatch(blade, /Roboto\+Condensed/);
});

test('audience tiles match the full-width prototype proportions', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const audience = read('resources/js/storefront/features/home/components/AudienceTiles.vue');
  const tile = read('resources/js/storefront/components/common/ImageCategoryTile.vue');
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');

  for (const declaration of [
    '--np-audience-max: 2048px',
    '--np-audience-gutter: 8px',
    '--np-audience-gap: 8px',
    '--np-audience-tile-ratio: 672 / 427',
    '--np-audience-title-size: var(--np-home-tile-title-size)',
    '--np-audience-title-weight: var(--np-type-title-weight)',
    '--np-audience-title-bottom: 36px',
    '--np-audience-title-hover-lift: 26px',
    '--np-audience-action-bottom: 22px',
    '--np-audience-action-size: 12px',
    '--np-audience-action-weight: var(--np-weight-medium)',
    '--np-audience-hover-scale: 1.025',
    '--np-audience-hover-duration: 280ms',
    '--np-audience-padding-top: 102px',
    '--np-audience-padding-bottom: 40px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(audience, /variant="audience"/);
  assert.match(audience, /max-width:\s*var\(--np-audience-max\)/);
  assert.match(audience, /padding-inline:\s*var\(--np-audience-gutter\)/);
  assert.match(audience, /gap:\s*var\(--np-audience-gap\)/);
  assert.match(audience, /padding:\s*var\(--np-audience-padding-top\)\s+0\s+var\(--np-audience-padding-bottom\)/);
  assert.match(tile, /aspect-ratio:\s*var\(--np-audience-tile-ratio\)/);
  assert.doesNotMatch(tile, /height:\s*var\(--np-audience-tile-height\)/);
  assert.match(tile, /font-size:\s*var\(--np-audience-title-size\)/);
  assert.match(tile, /font-weight:\s*var\(--np-audience-title-weight\)/);
  assert.match(tile, /bottom:\s*var\(--np-audience-title-bottom\)/);
  assert.match(tile, /class="np-image-tile__action"/);
  assert.match(tile, /Shop Now/);
  assert.match(tile, /transform:\s*scale\(var\(--np-audience-hover-scale\)\)/);
  assert.match(tile, /translateY\(calc\(-1\s*\*\s*var\(--np-audience-title-hover-lift\)\)\)/);
  assert.match(tile, /transition:[^;]*var\(--np-audience-hover-duration\)/);
  assert.match(tile, /np-image-tile--audience:hover[\s\S]*np-image-tile__action[\s\S]*opacity:\s*1/);
  assert.match(tile, /np-image-tile--audience:not\(:hover\)[\s\S]*np-image-tile__action[\s\S]*translateY/);

  // Keep the next section independent; changing audience width must not stretch its header.
  assert.doesNotMatch(sport, /np-sport-section__head-container\{max-width:var\(--np-audience-max\)\}/);
});

test('hero banner copy and controls stay centralized while matching the approved compact reference', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
  const button = read('resources/js/storefront/components/ui/AppButton.vue');
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');
  const seeder = read('database/seeders/HomepageSlideSeeder.php');
  const migration = 'database/migrations/2026_09_17_113500_align_uniform_hero_actions.php';

  for (const declaration of [
    '--np-home-hero-eyebrow-font-family: var(--np-font-display)',
    '--np-home-hero-eyebrow-size: var(--np-type-tag-size)',
    '--np-home-hero-eyebrow-weight: var(--np-type-tag-weight)',
    '--np-home-hero-title-font-family: var(--np-font-display)',
    '--np-home-hero-title-size-mobile: var(--np-type-title-2-size)',
    '--np-home-hero-title-weight: var(--np-type-title-weight)',
    '--np-home-hero-copy-font-family: var(--np-font-body)',
    '--np-home-hero-copy-size: 16px',
    '--np-home-hero-copy-size-mobile: var(--np-type-body-2-size)',
    '--np-home-hero-copy-weight: var(--np-type-body-1-weight)',
    '--np-home-hero-action-font-family: var(--np-font-display)',
    '--np-home-hero-action-size: 13px',
    '--np-home-hero-action-size-mobile: var(--np-type-cta-small-size)',
    '--np-home-hero-action-weight: var(--np-type-cta-regular-weight)',
    '--np-home-hero-action-min-width: 150px',
    '--np-home-hero-action-height: 48px',
    '--np-home-hero-action-padding-inline: 20px',
    '--np-home-hero-action-min-width-mobile: 142px',
    '--np-home-hero-action-height-mobile: 50px',
    '--np-home-hero-action-padding-inline-mobile: 18px',
    '--np-home-hero-control-size: 48px',
    '--np-home-hero-control-track-width: 70px',
    '--np-home-hero-control-icon-size: 18px',
    '--np-home-hero-control-size-mobile: 42px',
    '--np-home-hero-control-track-width-mobile: 46px',
    '--np-home-hero-indicator-dash-width: 24px',
    '--np-home-hero-indicator-dash-height: 2px',
    '--np-home-hero-indicator-dot-size: 4px',
    '--np-home-hero-indicator-gap: 7px',
  ]) {
    assert.match(tokens, new RegExp(declaration.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(hero, /font-family:\s*var\(--np-home-hero-eyebrow-font-family\)/);
  assert.match(hero, /font-size:\s*var\(--np-home-hero-eyebrow-size\)/);
  assert.match(hero, /font-weight:\s*var\(--np-home-hero-eyebrow-weight\)/);
  assert.match(hero, /font-family:\s*var\(--np-home-hero-title-font-family\)/);
  assert.match(hero, /font-weight:\s*var\(--np-home-hero-title-weight\)/);
  assert.match(hero, /font-family:\s*var\(--np-home-hero-copy-font-family\)/);
  assert.match(hero, /font-weight:\s*var\(--np-home-hero-copy-weight\)/);
  assert.match(hero, /font-size:\s*var\(--np-home-hero-title-size-mobile\)/);
  assert.match(hero, /font-size:\s*var\(--np-home-hero-copy-size-mobile\)/);
  assert.doesNotMatch(hero, /np-hero__eyebrow[\s\S]*font-size:\s*13px/);

  assert.match(button, /np-button--hero[\s\S]*min-width:\s*var\(--np-home-hero-action-min-width\)/);
  assert.match(button, /np-button--hero[\s\S]*height:\s*var\(--np-home-hero-action-height\)/);
  assert.match(button, /np-button--hero[\s\S]*font-family:\s*var\(--np-home-hero-action-font-family\)/);
  assert.match(button, /np-button--hero[\s\S]*font-weight:\s*var\(--np-home-hero-action-weight\)/);
  assert.match(controls, /grid-template-columns:\s*var\(--np-home-hero-control-size\)\s+var\(--np-home-hero-control-track-width\)\s+var\(--np-home-hero-control-size\)/);
  assert.match(controls, /width:\s*var\(--np-home-hero-control-size\)/);
  assert.match(controls, /height:\s*var\(--np-home-hero-control-size\)/);
  assert.match(controls, /width:\s*var\(--np-home-hero-control-icon-size\)/);
  assert.match(controls, /height:\s*var\(--np-home-hero-control-icon-size\)/);
  assert.match(controls, /np-carousel-controls__hero-markers/);
  assert.match(controls, /np-carousel-controls__marker--hero/);
  assert.match(controls, /np-carousel-controls__marker--active/);
  assert.match(controls, /width:\s*var\(--np-home-hero-indicator-dash-width\)/);
  assert.match(controls, /height:\s*var\(--np-home-hero-indicator-dash-height\)/);
  assert.match(controls, /width:\s*var\(--np-home-hero-indicator-dot-size\)/);
  assert.match(controls, /gap:\s*var\(--np-home-hero-indicator-gap\)/);
  assert.doesNotMatch(controls, /linear-gradient\(90deg,#fff 0 36%/);

  assert.ok((seeder.match(/'title'\s*=>\s*'YOUR TEAM YOUR KITS'/g) || []).length >= 2);
  assert.ok((seeder.match(/Performance sportswear for players, teams and clubs/g) || []).length >= 2);
  assert.ok((seeder.match(/'show_eyebrow'\s*=>\s*false/g) || []).length >= 2);
  assert.ok((seeder.match(/'primary_label'\s*=>\s*'SHOP PRODUCTS'/g) || []).length >= 2);
  assert.ok((seeder.match(/'secondary_label'\s*=>\s*'CUSTOMIZE YOUR GEAR'/g) || []).length >= 2);
  assert.equal(fs.existsSync(path.join(root, migration)), true, 'uniform hero action migration must exist');
  const migrationSource = read(migration);
  assert.match(migrationSource, /Uniform Sets for Schools, Leagues, and Clubs/);
  assert.match(migrationSource, /Shop Uniforms/);
  assert.match(migrationSource, /How It Works/);
  assert.match(migrationSource, /'title'\s*=>\s*'YOUR TEAM YOUR KITS'/);
  assert.match(migrationSource, /Performance sportswear for players, teams and clubs/);
  assert.match(migrationSource, /'show_eyebrow'\s*=>\s*false/);
  assert.match(migrationSource, /SHOP PRODUCTS/);
  assert.match(migrationSource, /CUSTOMIZE YOUR GEAR/);
});

test('reusable chevron hover effect uses three solid one-pass chevrons on the approved first-fold buttons', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const button = read('resources/js/storefront/components/ui/AppButton.vue');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');

  for (const declaration of [
    '--np-button-chevron-on-orange: var(--np-color-navy-950)',
    '--np-button-chevron-on-navy: var(--np-color-orange)',
    '--np-button-chevron-on-transparent: var(--np-color-white)',
    '--np-button-chevron-width: 26px',
    '--np-button-chevron-overlap: 4px',
    '--np-button-chevron-duration: 330ms',
    '--np-button-chevron-start: calc(-100% - 8px)',
    '--np-button-chevron-end-offset: 8px',
  ]) {
    assert.ok(tokens.includes(declaration), `missing token: ${declaration}`);
  }

  assert.match(button, /hoverEffect\?:\s*'none'\s*\|\s*'chevrons'/);
  assert.match(button, /hoverEffect:\s*'none'/);
  assert.match(button, /np-button--hover-chevrons/);
  assert.match(button, /class="np-button__chevrons"/);
  assert.equal((button.match(/class="np-button__chevron"/g) || []).length, 6);
  assert.doesNotMatch(button, /&gt;&gt;&gt;/);
  assert.match(button, /background:\s*var\(--np-button-chevron-color\)/);
  assert.match(button, /clip-path:\s*polygon\(/);
  assert.doesNotMatch(button, /border-top:\s*var\(--np-button-chevron-stroke\)/);
  assert.doesNotMatch(button, /@keyframes\s+np-button-chevron-patrol/);
  assert.doesNotMatch(button, /animation:[^;]*infinite/);
  assert.match(button, /transform:\s*translateX\(var\(--np-button-chevron-start\)\)/);
  assert.match(button, /transition:\s*left var\(--np-button-chevron-duration\) var\(--np-button-chevron-easing\),\s*transform var\(--np-button-chevron-duration\) var\(--np-button-chevron-easing\)/);
  assert.match(button, /\.np-button--hover-chevrons:hover\s+\.np-button__chevrons/);
  assert.match(button, /left:\s*100%/);
  assert.match(button, /transform:\s*translateX\(var\(--np-button-chevron-end-offset\)\)/);
  assert.match(button, /\.np-button--orange\s*\{[^}]*--np-button-chevron-color:\s*var\(--np-button-chevron-on-orange\)/s);
  assert.match(button, /\.np-button--navy\s*\{[^}]*--np-button-chevron-color:\s*var\(--np-button-chevron-on-navy\)/s);
  assert.match(button, /\.np-button--outline-light\s*\{[^}]*--np-button-chevron-color:\s*var\(--np-button-chevron-on-transparent\)/s);
  assert.match(button, /\.np-button--hover-chevrons:focus-visible\s+\.np-button__chevrons/);
  assert.match(button, /overflow:\s*hidden/);

  assert.match(header, /size="header"\s+hover-effect="chevrons"/);
  assert.equal((hero.match(/hover-effect="chevrons"/g) || []).length, 2);
});
