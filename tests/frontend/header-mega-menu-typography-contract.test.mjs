import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('header navigation uses the approved centralized weight without changing its font size', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const navigation = read('resources/js/storefront/components/layout/DesktopNavigation.vue');

  assert.match(tokens, /--np-header-nav-font-size:\s*14px/);
  assert.match(tokens, /--np-header-nav-font-weight:\s*600/);
  assert.match(navigation, /font-size:\s*var\(--np-header-nav-font-size\)/);
  assert.match(navigation, /font-weight:\s*var\(--np-header-nav-font-weight\)/);
});

test('mega menu typography is slightly larger through centralized tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const column = read('resources/js/storefront/components/layout/navigation/MegaMenuColumn.vue');
  const topChoices = read('resources/js/storefront/components/layout/navigation/MegaMenuTopChoices.vue');

  assert.match(tokens, /--np-mega-menu-heading-size:\s*15px/);
  assert.match(tokens, /--np-mega-menu-link-size:\s*14px/);
  assert.match(tokens, /--np-mega-menu-eyebrow-size:\s*14px/);
  assert.match(column, /font-size:\s*var\(--np-mega-menu-heading-size\)/);
  assert.match(column, /font-size:\s*var\(--np-mega-menu-link-size\)/);
  assert.match(topChoices, /font-size:\s*var\(--np-mega-menu-eyebrow-size\)/);
  assert.match(topChoices, /font-size:\s*var\(--np-mega-menu-heading-size\)/);
});
