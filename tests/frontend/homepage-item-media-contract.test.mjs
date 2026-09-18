import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const php = readFileSync(new URL('../../app/Services/Catalog/HomepageSectionMediaService.php', import.meta.url), 'utf8');
for (const needle of ['syncSectionMedia', 'syncItemMedia', 'deleteOwnedItemPath', 'homepage/sections/{$section->key}/items/{$itemId}/']) {
  assert.ok(php.includes(needle), `Missing item media behavior: ${needle}`);
}
assert.ok(!php.includes('syncHeroSlides'), 'HomepageSectionMediaService must not manage hero slides');
console.log('Homepage item media contract passed.');
