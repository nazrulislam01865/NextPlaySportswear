import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const megaFiles = [
  'resources/js/storefront/components/layout/navigation/MegaMenu.vue',
  'resources/js/storefront/components/layout/navigation/MegaMenuColumn.vue',
  'resources/js/storefront/components/layout/navigation/MegaMenuTopChoices.vue',
  'resources/js/storefront/components/layout/navigation/MegaMenuPromo.vue',
  'resources/js/storefront/components/layout/navigation/MegaMenuFooter.vue',
  'resources/js/storefront/components/layout/navigation/mega-menu.config.ts',
];

test('desktop navigation is split into reusable mega-menu modules', () => {
  for (const file of megaFiles) {
    assert.equal(fs.existsSync(path.join(root, file)), true, `${file} must exist`);
  }

  const nav = read('resources/js/storefront/components/layout/DesktopNavigation.vue');
  assert.match(nav, /MegaMenu/);
  assert.match(nav, /mega-menu\.config/);
  assert.match(nav, /activeMenu/);
  assert.ok(nav.split('\n').length < 220, 'DesktopNavigation.vue should stay modular and compact');
});

test('mega menu matches the prototype content structure', () => {
  assert.equal(fs.existsSync(path.join(root, 'public/images/storefront-vue/navigation/shop-mega-promo.jpg')), true, 'prototype promo crop must be bundled locally');
  const config = read('resources/js/storefront/components/layout/navigation/mega-menu.config.ts');
  const mega = read('resources/js/storefront/components/layout/navigation/MegaMenu.vue');

  for (const text of [
    'Top Choices',
    'BEST SELLERS',
    'ON SALE',
    'FEATURED FOR YOU',
    'SUMMER 2026',
    'NEW ARRIVALS',
    'MEN',
    'WOMEN',
    'KIDS',
    'Shop All Products',
  ]) {
    assert.match(config + mega, new RegExp(text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }
});

test('mega menu dimensions, colors and typography are centralized', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const mega = megaFiles.slice(0, 5).map(read).join('\n');

  for (const token of [
    '--np-mega-menu-height',
    '--np-mega-menu-rail-width',
    '--np-mega-menu-promo-width',
    '--np-mega-menu-footer-height',
    '--np-mega-menu-bg',
    '--np-mega-menu-rail-bg',
    '--np-mega-menu-heading-size',
    '--np-mega-menu-link-size',
  ]) {
    assert.match(tokens, new RegExp(token));
    assert.match(mega, new RegExp(`var\\(${token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\)`));
  }

  assert.doesNotMatch(mega, /#[0-9a-fA-F]{3,8}/, 'mega-menu modules should use centralized color tokens');
});

test('desktop mega menu includes accessible open close interactions', () => {
  const nav = read('resources/js/storefront/components/layout/DesktopNavigation.vue');
  assert.match(nav, /aria-expanded/);
  assert.match(nav, /@keydown\.esc/);
  assert.match(nav, /focusin/);
  assert.match(nav, /pointerdown/);
  assert.match(nav, /mouseenter/);
  assert.match(nav, /mouseleave/);
});

test('mobile navigation remains a separate component', () => {
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  const mobile = read('resources/js/storefront/components/layout/MobileNavigation.vue');
  assert.match(header, /MobileNavigation/);
  assert.match(mobile, /np-mobile-nav/);
  assert.doesNotMatch(mobile, /MegaMenu/);
});

test('mega menu uses compact centralized typography and reduced height', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const column = read('resources/js/storefront/components/layout/navigation/MegaMenuColumn.vue');
  const topChoices = read('resources/js/storefront/components/layout/navigation/MegaMenuTopChoices.vue');
  const promo = read('resources/js/storefront/components/layout/navigation/MegaMenuPromo.vue');
  const footer = read('resources/js/storefront/components/layout/navigation/MegaMenuFooter.vue');

  assert.match(tokens, /--np-mega-menu-height:\s*205px;/);
  assert.match(tokens, /--np-mega-menu-footer-height:\s*44px;/);
  assert.match(tokens, /--np-mega-menu-heading-size:\s*15px;/);
  assert.match(tokens, /--np-mega-menu-link-size:\s*14px;/);
  assert.match(tokens, /--np-mega-menu-eyebrow-size:\s*14px;/);
  assert.match(tokens, /--np-mega-menu-promo-size:\s*12px;/);
  assert.match(tokens, /--np-mega-menu-social-label-size:\s*11px;/);
  assert.match(tokens, /--np-mega-menu-footer-link-size:\s*11px;/);

  assert.match(column, /font-family:\s*var\(--np-font-display\)/);
  assert.match(column, /font-family:\s*var\(--np-font-body\)/);
  assert.match(topChoices, /font-family:\s*var\(--np-font-display\)/);
  assert.match(topChoices, /font-family:\s*var\(--np-font-body\)/);
  assert.match(promo, /font-family:\s*var\(--np-font-body\)/);
  assert.match(footer, /font-size:\s*var\(--np-mega-menu-footer-link-size\)/);
});


test('mega menu links reveal a left-to-right underline on hover and keyboard focus', () => {
  const column = read('resources/js/storefront/components/layout/navigation/MegaMenuColumn.vue');
  const topChoices = read('resources/js/storefront/components/layout/navigation/MegaMenuTopChoices.vue');

  const tokens = read('resources/js/storefront/styles/tokens.css');
  assert.match(tokens, /--np-mega-menu-underline-height:\s*1px;/);
  assert.match(tokens, /--np-mega-menu-underline-offset:\s*2px;/);

  for (const component of [column, topChoices]) {
    assert.match(component, /a::after\s*\{/);
    assert.match(component, /height:\s*var\(--np-mega-menu-underline-height\)/);
    assert.match(component, /bottom:\s*calc\(-1\s*\*\s*var\(--np-mega-menu-underline-offset\)\)/);
    assert.match(component, /transform:\s*scaleX\(0\)/);
    assert.match(component, /transform-origin:\s*left/);
    assert.match(component, /transition:\s*transform\s+var\(--np-transition-base\)/);
    assert.match(component, /a:hover::after[\s\S]*a:focus-visible::after[\s\S]*transform:\s*scaleX\(1\)/);
  }

  assert.doesNotMatch(column, /a:hover[\s\S]{0,160}color:\s*var\(--np-color-orange\)/);
  assert.doesNotMatch(topChoices, /a:hover[\s\S]{0,160}color:\s*var\(--np-color-orange\)/);
});
