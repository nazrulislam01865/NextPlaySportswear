import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const footerFile = 'resources/js/storefront/components/layout/StorefrontFooter.vue';
const columnFile = 'resources/js/storefront/components/layout/FooterLinkColumn.vue';
const tokensFile = 'resources/js/storefront/styles/tokens.css';

test('footer uses the reusable prototype wordmark without a Vite-resolved absolute img import', () => {
  const footer = read(footerFile);
  const brandLogoFile = 'resources/js/storefront/components/common/BrandLogo.vue';
  const brandLogo = read(brandLogoFile);

  assert.equal(fs.existsSync(path.join(root, 'public/images/storefront-vue/brand/nextplay-wordmark.png')), true);
  assert.match(footer, /import\s+BrandLogo\s+from\s+['"]\.\.\/common\/BrandLogo\.vue['"]/);
  assert.match(footer, /<BrandLogo\b[^>]*class=['"]np-footer__logo['"]/);
  assert.doesNotMatch(footer, /<img[\s\S]*src=['"]\/images\/storefront-vue\/brand\/nextplay-wordmark\.png['"]/);
  assert.match(brandLogo, /const\s+logoSrc\s*=\s*['"]\/images\/storefront-vue\/brand\/nextplay-wordmark\.png['"]/);
  assert.match(brandLogo, /<img\s+:src=['"]logoSrc['"]/);
  assert.equal(fs.existsSync(path.join(root, columnFile)), true);
  assert.match(footer, /FooterLinkColumn/);
});

test('footer compact prototype sizing is centralized', () => {
  const tokens = read(tokensFile);
  const footer = read(footerFile) + '\n' + read(columnFile) + '\n' + read('resources/js/storefront/components/ui/AppButton.vue');

  const expected = [
    ['--np-footer-logo-width', '190px'],
    ['--np-footer-top-padding', '42px'],
    ['--np-footer-top-padding-bottom', '46px'],
    ['--np-footer-heading-size', '14px'],
    ['--np-footer-link-size', '13px'],
    ['--np-footer-brand-size', '13px'],
    ['--np-footer-signup-title-size', '12px'],
    ['--np-footer-signup-button-width', '96px'],
    ['--np-footer-signup-button-height', '48px'],
    ['--np-footer-signup-button-size', '13px'],
    ['--np-footer-social-band-height', '72px'],
    ['--np-footer-follow-size', '12px'],
    ['--np-footer-bottom-height', '66px'],
    ['--np-footer-bottom-size', '11px'],
  ];

  for (const [token, value] of expected) {
    assert.match(tokens, new RegExp(`${token.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}:\\s*${value.replace('.', '\\.')}\\s*;`));
    assert.match(footer, new RegExp(`var\\(${token.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}\\)`));
  }
});

test('footer links reuse mega-menu left-to-right underline hover behavior', () => {
  const column = read(columnFile);
  const footer = read(footerFile);

  for (const component of [column, footer]) {
    assert.match(component, /::after\s*\{/);
    assert.match(component, /height:\s*var\(--np-mega-menu-underline-height\)/);
    assert.match(component, /bottom:\s*calc\(-1\s*\*\s*var\(--np-mega-menu-underline-offset\)\)/);
    assert.match(component, /transform:\s*scaleX\(0\)/);
    assert.match(component, /transform-origin:\s*left/);
    assert.match(component, /transition:\s*transform\s+var\(--np-transition-base\)/);
    assert.match(component, /hover::after[\s\S]*focus-visible::after[\s\S]*scaleX\(1\)/);
  }
});

test('footer prototype content and three-band layout are preserved', () => {
  const footer = read(footerFile);
  for (const text of [
    'Quick Links', 'Wishlist', 'My Account', 'Offers', 'Sitemap',
    'Help & Support', 'FAQs', 'Delivery & Returns', 'Size Guide', 'Track Your Order',
    'Customer Service', 'Contact Us', 'Get a Quote',
    'JOIN NEXTPLAY CLUB &amp; GET 20% OFF', 'SIGN UP', 'Follow Us :',
    'Privacy Policy', 'Terms &amp; Conditions', 'Cookie Policy', 'Secured by Striped:'
  ]) {
    assert.match(footer, new RegExp(text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  }
  assert.match(footer, /np-footer__top/);
  assert.match(footer, /np-footer__socialBand/);
  assert.match(footer, /np-footer__bottomWrap/);
});


test('footer heading hierarchy and signup reuse centralized banner-style button hover', () => {
  const footer = read(footerFile);
  const column = read(columnFile);
  const tokens = read(tokensFile);
  const appButton = read('resources/js/storefront/components/ui/AppButton.vue');

  assert.match(tokens, /--np-footer-heading-weight:\s*600;/);
  assert.match(column, /font-size:\s*var\(--np-footer-heading-size\)/);
  assert.match(column, /font-size:\s*var\(--np-footer-link-size\)/);
  assert.match(footer, /import\s+AppButton\s+from\s+['"]\.\.\/ui\/AppButton\.vue['"]/);
  assert.match(footer, /<AppButton[\s\S]*href=['"]\/register['"][\s\S]*variant=['"]orange['"][\s\S]*size=['"]footer['"][\s\S]*hover-effect=['"]chevrons['"][\s\S]*>\s*SIGN UP\s*<\/AppButton>/);
  assert.match(appButton, /size\?:[\s\S]*'footer'/);
  assert.match(appButton, /\.np-button--footer\s*\{[\s\S]*var\(--np-footer-signup-button-width\)[\s\S]*var\(--np-footer-signup-button-height\)[\s\S]*var\(--np-footer-signup-button-size\)/);
});
