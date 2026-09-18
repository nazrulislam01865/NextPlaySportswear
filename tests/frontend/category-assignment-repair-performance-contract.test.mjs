import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const syncService = fs.readFileSync('app/Services/Catalog/CategoryProductAssignmentSyncService.php', 'utf8');
const catalogService = fs.readFileSync('app/Services/Storefront/ProductCatalogService.php', 'utf8');

test('category assignment repair is batch-oriented instead of querying per product assignment', () => {
  assert.match(syncService, /chunkById\((?:300|500),/);
  assert.match(syncService, /DB::table\('category_product'\)->upsert\(/);
  assert.match(syncService, /bulkUpdateProductLegacyCategories/);
  assert.doesNotMatch(syncService, /private function upsertAssignment\(/);
  assert.doesNotMatch(syncService, /private function ensureSinglePrimaryPerProduct\(/);
});

test('storefront category membership prefers canonical pivot assignments with a single legacy fallback', () => {
  assert.match(catalogService, /canonicalCategoryMembershipSubquery/);
  assert.match(catalogService, /whereNotExists[\s\S]*category_product/);
  assert.match(catalogService, /COALESCE\([^\n]*subcategory_id[^\n]*category_id/);
  assert.doesNotMatch(catalogService, /return \$pivot->unionAll\(\$legacyCategory\)->unionAll\(\$legacySubcategory\)/);
});
