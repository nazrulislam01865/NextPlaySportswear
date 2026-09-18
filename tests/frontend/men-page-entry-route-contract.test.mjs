import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('homepage MEN audience tile points to the new Vue men page', () => {
  const audience = read('resources/js/storefront/features/home/components/AudienceTiles.vue');
  const registry = read('app/Support/HomepageSectionRegistry.php');
  const realignMigration = read('database/migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php');

  assert.match(audience, /\{ id: 'men', title: 'MEN', url: '\/men' \}/);
  assert.match(registry, /\['id' => 'men', 'title' => 'MEN', 'url' => '\/men'/);
  assert.match(realignMigration, /\['id' => 'men', 'title' => 'MEN', 'url' => '\/men'/);
  assert.doesNotMatch(audience, /\{ id: 'men', title: 'MEN', url: '\/products\?q=men' \}/);
});

test('existing homepage audience configuration is migrated from the legacy MEN URL', () => {
  const migration = read('database/migrations/2026_09_18_183000_update_homepage_audience_men_url.php');
  assert.match(migration, /homepage_sections/);
  assert.match(migration, /audience/);
  assert.match(migration, /\/products\?q=men/);
  assert.match(migration, /\/men/);
});
