import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('main filter category hover uses the centralized orange theme while counts stay muted', () => {
  const section = read('resources/js/storefront/features/catalog/components/CatalogFilterSection.vue');

  assert.match(section, /np-catalog-filter-section__label[\s\S]*transition:\s*color var\(--np-transition-base\)/);
  assert.match(section, /np-catalog-filter__toggle[\s\S]*transition:\s*color var\(--np-transition-base\)/);
  assert.match(section, /np-catalog-filter-section__trigger:hover[\s\S]*np-catalog-filter-section__label[\s\S]*color:\s*var\(--np-color-orange\)/);
  assert.match(section, /np-catalog-filter-section__trigger:hover[\s\S]*np-catalog-filter__toggle[\s\S]*color:\s*var\(--np-color-orange\)/);
  assert.match(section, /np-catalog-filter-section__count[\s\S]*color:\s*var\(--np-men-filter-count-color\)/);
});

test('filter sub-option text reuses the mega-menu underline hover treatment without underlining counts', () => {
  const option = read('resources/js/storefront/features/catalog/components/CatalogFilterOption.vue');

  assert.match(option, /np-catalog-filter-option__label[\s\S]*position:\s*relative/);
  assert.match(option, /np-catalog-filter-option__label::after[\s\S]*height:\s*var\(--np-mega-menu-underline-height\)/);
  assert.match(option, /np-catalog-filter-option__label::after[\s\S]*bottom:\s*calc\(-1 \* var\(--np-mega-menu-underline-offset\)\)/);
  assert.match(option, /np-catalog-filter-option__label::after[\s\S]*transform:\s*scaleX\(0\)/);
  assert.match(option, /np-catalog-filter-option:hover[\s\S]*np-catalog-filter-option__label::after[\s\S]*transform:\s*scaleX\(1\)/);
  assert.doesNotMatch(option, /np-catalog-filter-option__count::after/);
});

test('selected filter checkbox matches prototype with solid navy fill and a white checkmark', () => {
  const option = read('resources/js/storefront/features/catalog/components/CatalogFilterOption.vue');

  assert.match(option, /np-catalog-filter-option__input:checked \+ \.np-catalog-filter-option__box[\s\S]*background:\s*var\(--np-color-navy-950\)/);
  assert.match(option, /np-catalog-filter-option__box::after[\s\S]*border:\s*solid var\(--np-color-white\)/);
  assert.match(option, /np-catalog-filter-option__input:checked \+ \.np-catalog-filter-option__box::after[\s\S]*opacity:\s*1/);
  assert.doesNotMatch(option, /box-shadow:\s*inset/);
});
