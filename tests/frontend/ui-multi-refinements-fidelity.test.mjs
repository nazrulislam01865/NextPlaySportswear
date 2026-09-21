import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('delivery and returns uses the supplied reusable cyclist icon in utility header and mega footer', () => {
  const iconPath = 'resources/js/storefront/components/common/DeliveryReturnsIcon.vue';
  assert.equal(fs.existsSync(path.join(root, iconPath)), true, 'DeliveryReturnsIcon.vue must exist');

  const icon = read(iconPath);
  const utility = read('resources/js/storefront/components/layout/UtilityBar.vue');
  const megaFooter = read('resources/js/storefront/components/layout/navigation/MegaMenuFooter.vue');

  assert.match(icon, /viewBox="0 0 24 24"/);
  assert.match(icon, /M16 6C17\.1046 6 18 5\.10457 18 4/);
  assert.match(icon, /fill="currentColor"/);
  assert.match(utility, /DeliveryReturnsIcon/);
  assert.match(megaFooter, /DeliveryReturnsIcon/);
  assert.doesNotMatch(utility, /\bRoute\b/);
  assert.doesNotMatch(megaFooter, /\bRoute\b/);
});

test('header and mega-menu utility actions reuse one left-to-right underline link component', () => {
  const linkPath = 'resources/js/storefront/components/layout/navigation/UtilityActionLink.vue';
  assert.equal(fs.existsSync(path.join(root, linkPath)), true, 'UtilityActionLink.vue must exist');

  const link = read(linkPath);
  const utility = read('resources/js/storefront/components/layout/UtilityBar.vue');
  const megaFooter = read('resources/js/storefront/components/layout/navigation/MegaMenuFooter.vue');

  assert.match(utility, /UtilityActionLink/);
  assert.match(megaFooter, /UtilityActionLink/);
  const settings = read('app/Services/Storefront/StorefrontSettingsService.php');
  for (const label of ['Track Your Order', 'Delivery & Returns', 'Contact Support']) {
    assert.match(settings, new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }
  assert.match(utility, /track_order/);
  assert.match(utility, /delivery_returns/);
  assert.match(utility, /contact_support/);
  for (const label of ['Track Your Order', 'Delivery &amp; Returns', 'Contact Support']) {
    assert.match(megaFooter, new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }

  assert.match(link, /\.np-utility-action__label::after\s*\{/);
  assert.match(link, /height:\s*var\(--np-mega-menu-underline-height\)/);
  assert.match(link, /bottom:\s*calc\(-1\s*\*\s*var\(--np-mega-menu-underline-offset\)\)/);
  assert.match(link, /transform:\s*scaleX\(0\)/);
  assert.match(link, /transform-origin:\s*left center/);
  assert.match(link, /transition:\s*transform\s+var\(--np-transition-base\)/);
  assert.match(link, /\.np-utility-action:hover[\s\S]*\.np-utility-action:focus-visible[\s\S]*scaleX\(1\)/);
});

test('hero carousel arrow icon is reduced to the same compact scale as standard section controls', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');

  assert.match(tokens, /--np-home-hero-control-icon-size:\s*18px;/);
  assert.match(controls, /\.np-carousel-controls--hero button svg\s*\{[^}]*width:\s*var\(--np-home-hero-control-icon-size\)/s);
  assert.match(controls, /\.np-carousel-controls--hero button svg\s*\{[^}]*height:\s*var\(--np-home-hero-control-icon-size\)/s);
});

test('audience and category Shop Now actions use the smaller centralized font size on desktop and mobile', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const tile = read('resources/js/storefront/components/common/ImageCategoryTile.vue');

  assert.match(tokens, /--np-audience-action-size:\s*12px;/);
  assert.match(tile, /font-size:\s*var\(--np-audience-action-size\)/);
  assert.match(tile, /\.np-image-tile--audience \.np-image-tile__action\s*\{[^}]*font-size:\s*var\(--np-audience-action-size\)/s);
  assert.match(tile, /\.np-image-tile--category-showcase \.np-image-tile__action\s*\{[^}]*font-size:\s*var\(--np-audience-action-size\)/s);
  assert.doesNotMatch(tile, /\.np-image-tile--audience \.np-image-tile__action\s*\{[^}]*font-size:\s*14px/s);
  assert.doesNotMatch(tile, /\.np-image-tile--category-showcase \.np-image-tile__action\s*\{[^}]*font-size:\s*14px/s);
});

test('Shop by Sport keeps fixed button dimensions while label shifts left and arrow reveals on hover', () => {
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(tokens, /--np-home-sport-link-min-width:\s*132px;/);
  assert.match(tokens, /--np-home-sport-link-label-hover-shift:\s*7px;/);
  assert.match(tokens, /--np-home-sport-link-arrow-offset:\s*16px;/);
  assert.match(sport, /min-width:\s*var\(--np-home-sport-link-min-width\)/);
  assert.match(sport, /\.np-sport-link__arrow\s*\{[^}]*position:\s*absolute/s);
  assert.match(sport, /\.np-sport-link__arrow\s*\{[^}]*right:\s*var\(--np-home-sport-link-arrow-offset\)/s);
  assert.match(sport, /\.np-sport-link__arrow\s*\{[^}]*width:\s*var\(--np-home-sport-link-arrow-size\)/s);
  assert.match(sport, /\.np-sport-hero nav a:hover \.np-sport-link__label[\s\S]{0,220}translateX\(calc\(-1 \* var\(--np-home-sport-link-label-hover-shift\)\)\)/);
  assert.match(sport, /\.np-sport-hero nav a:hover \.np-sport-link__arrow[\s\S]{0,220}opacity:\s*1/);
  const hoverArrowBlock = sport.match(/\.np-sport-hero nav a:hover \.np-sport-link__arrow,[\s\S]*?\{([^}]*)\}/)?.[1] ?? '';
  assert.doesNotMatch(hoverArrowBlock, /width:/);
  assert.match(sport, /@media\(max-width:900px\)[\s\S]*min-width:\s*0/);
});
