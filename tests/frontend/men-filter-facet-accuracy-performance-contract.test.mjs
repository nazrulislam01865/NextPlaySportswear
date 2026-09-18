import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('catalog filter options use bounded facet scopes instead of per-option count queries', () => {
  const service = read('app/Services/Storefront/ProductCatalogService.php');

  assert.match(service, /private function facetScope\(array \$filters, array \$except = \[\]\): Builder/);
  assert.match(service, /private function categoryFacetCountMap\(Builder \$baseQuery\): Collection/);
  assert.match(service, /categoryFilterTree\([\s\S]*?\$categoryIds,[\s\S]*?\$this->facetScope\(\$filters, \['categories'\]\)[\s\S]*?\)/);
  assert.match(service, /sportFilterOptions\([\s\S]*?\$this->facetScope\(\$filters, \['sports'\]\),[\s\S]*?\$sportIds[\s\S]*?\)/);

  const categoryTree = service.slice(
    service.indexOf('public function categoryFilterTree'),
    service.indexOf('public function normalizeCategoryFilterIds'),
  );
  assert.doesNotMatch(categoryTree, /countProductsForCategoryFilter\(/);

  const sports = service.slice(
    service.indexOf('public function sportFilterOptions'),
    service.indexOf('private function listingIdSubquery'),
  );
  assert.doesNotMatch(sports, /clone \$baseQuery[\s\S]*count\('products\.id'\)/);
});

test('facet cache key includes the complete normalized filter state', () => {
  const service = read('app/Services/Storefront/ProductCatalogService.php');

  assert.match(service, /private function canonicalFacetFilters\(array \$filters\): array/);
  assert.match(service, /sha1\(json_encode\(\$this->canonicalFacetFilters\(\$filters\)/);
  for (const key of ['categories', 'sports', 'product_types', 'colors', 'materials', 'artwork_methods', 'min_price', 'max_price', 'moq', 'customization', 'availability', 'min_rating']) {
    assert.match(service, new RegExp(`['\"]${key}['\"]`));
  }
});

test('men sidebar consumes authoritative facet totals instead of substituting page product count', () => {
  const api = read('resources/js/storefront/features/men/api/men.api.ts');
  const types = read('resources/js/storefront/features/men/types/men.types.ts');
  const sidebar = read('resources/js/storefront/features/catalog/components/CatalogFilterSidebar.vue');

  assert.match(types, /facet_totals:\s*MenFacetTotals/);
  assert.match(api, /facet_totals:/);
  assert.match(sidebar, /filterOptions\.facet_totals\.colors/);
  assert.doesNotMatch(sidebar, /return props\.productCount;/);
});
