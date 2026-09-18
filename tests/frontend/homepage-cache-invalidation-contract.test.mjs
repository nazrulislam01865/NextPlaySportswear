import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const controller = readFileSync(new URL('../../app/Http/Controllers/Admin/HomepageSectionController.php', import.meta.url), 'utf8');
const service = readFileSync(new URL('../../app/Services/Storefront/HomepageSectionService.php', import.meta.url), 'utf8');
const updateStart = controller.indexOf('public function update(');
const updateEnd = controller.indexOf('\n\n    /**', updateStart);
const updateBody = controller.slice(updateStart, updateEnd === -1 ? controller.length : updateEnd);
const transactionPos = updateBody.indexOf('DB::transaction(');
const flushPos = updateBody.indexOf('$this->sections->flushCache();');

assert.notEqual(transactionPos, -1, 'Homepage section update must be transactional');
assert.notEqual(flushPos, -1, 'Homepage section update must flush storefront cache');
assert.ok(flushPos > transactionPos, 'Cache flush must happen after the update transaction');
assert.ok(service.includes("private const CACHE_KEY = 'storefront.homepage-sections.v7';"), 'Homepage sections must use the v7 cache namespace');
assert.ok(service.includes('$this->runtimeSections = null;'), 'flushCache must clear runtime cache');
assert.ok(service.includes('Cache::forget(self::CACHE_KEY);'), 'flushCache must clear persistent cache');

console.log('Homepage cache invalidation contract passed.');
