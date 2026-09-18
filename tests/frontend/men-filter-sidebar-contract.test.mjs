import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const exists = (file) => fs.existsSync(path.join(root, file));

test('men page integrates a reusable catalog filter sidebar beside the existing product grid', () => {
  assert.equal(exists('resources/js/storefront/features/catalog/components/CatalogFilterSidebar.vue'), true);
  assert.equal(exists('resources/js/storefront/features/catalog/components/CatalogFilterChips.vue'), true);

  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  assert.match(men, /CatalogFilterSidebar/);
  assert.match(men, /CatalogFilterChips/);
  assert.match(men, /np-men-catalog-layout/);
  assert.match(men, /:filter-options="men\.filterOptions\.value"/);
  assert.match(men, /:model-value="men\.filters\.value"/);
  assert.match(men, /@change="men\.applyFilters"/);
  assert.match(men, /@remove="men\.removeFilter"/);
  assert.match(men, /@clear="men\.clearFilters"/);
  assert.doesNotMatch(men, /desktop-leading-spacer/);
});

test('filter sidebar follows the prototype section order and uses centralized storefront typography', () => {
  const sidebar = read('resources/js/storefront/features/catalog/components/CatalogFilterSidebar.vue');
  const option = read('resources/js/storefront/features/catalog/components/CatalogFilterOption.vue');
  const section = read('resources/js/storefront/features/catalog/components/CatalogFilterSection.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(sidebar, /title="COLOR"[\s\S]*title="PRICE"[\s\S]*v-for="section in categorySections"[\s\S]*title="SPORT"[\s\S]*title="PRODUCT TYPE"[\s\S]*title="MINIMUM ORDER QUANTITY"[\s\S]*title="CUSTOMIZATION"[\s\S]*title="FABRIC \/ MATERIAL"[\s\S]*title="AVAILABILITY"/);
  assert.match(sidebar, /label: 'ACCESSORIES'[\s\S]*label: 'BAGS'[\s\S]*label: 'DRINKWARE'[\s\S]*label: 'HEADWEAR'[\s\S]*label: 'PERFORMANCE APPAREL'/);

  assert.match(sidebar, /var\(--np-font-display\)/);
  assert.match(sidebar, /var\(--np-font-body\)/);
  assert.match(option, /np-catalog-filter__color-dot/);
  assert.match(sidebar, /np-catalog-filter__price-grid/);
  assert.match(section, /aria-expanded/);
  assert.match(section, /np-catalog-filter__toggle/);
  assert.match(tokens, /--np-men-filter-layout-gap:/);
  assert.match(tokens, /--np-men-filter-title-size:/);
  assert.match(tokens, /--np-men-filter-section-size:/);
  assert.match(tokens, /--np-men-filter-option-size:/);
});

test('men API sends selected filters to Laravel and consumes API filter options', () => {
  const api = read('resources/js/storefront/features/men/api/men.api.ts');
  const types = read('resources/js/storefront/features/men/types/men.types.ts');
  const useMen = read('resources/js/storefront/features/men/composables/useMen.ts');

  assert.match(types, /export interface MenCatalogFilters/);
  assert.match(types, /export interface MenFilterOptions/);
  assert.match(types, /filterOptions:\s*MenFilterOptions/);
  assert.match(api, /fetchMenPage\(page\s*=\s*1,\s*filters/);
  assert.match(api, /meta\.filter_options/);
  assert.match(api, /categories:/);
  assert.match(api, /sports:/);
  assert.match(api, /product_types:/);
  assert.match(api, /colors:/);
  assert.match(api, /materials:/);
  assert.match(api, /min_price:/);
  assert.match(api, /max_price:/);
  assert.match(api, /moq:/);
  assert.match(api, /customization:/);
  assert.match(api, /availability:/);

  assert.match(useMen, /const filters = ref<MenCatalogFilters>/);
  assert.match(useMen, /async function applyFilters/);
  assert.match(useMen, /async function clearFilters/);
  assert.match(useMen, /async function removeFilter/);
  assert.match(useMen, /fetchMenPage\(page, filters\.value\)/);
});

test('selected filters render as removable chips and Clear Filter only reflects real selections', () => {
  const chips = read('resources/js/storefront/features/catalog/components/CatalogFilterChips.vue');
  assert.match(chips, /v-for="chip in chips"/);
  assert.match(chips, /@click="emit\('remove', chip\)"/);
  assert.match(chips, /Clear Filter/);
  assert.match(chips, /@click="emit\('clear'\)"/);
  assert.match(chips, /np-catalog-filter-chips__chip/);
});

test('men filter typography matches the approved prototype scale', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(tokens, /--np-men-filter-title-size:\s*28px;/);
  assert.match(tokens, /--np-men-filter-section-size:\s*14px;/);
  assert.match(tokens, /--np-men-filter-count-size:\s*13px;/);
  assert.match(tokens, /--np-men-filter-option-size:\s*14px;/);
  assert.match(tokens, /--np-men-filter-price-label-size:\s*13px;/);
  assert.match(tokens, /--np-men-filter-chip-size:\s*17px;/);
  assert.match(tokens, /--np-men-filter-clear-size:\s*16px;/);
});
