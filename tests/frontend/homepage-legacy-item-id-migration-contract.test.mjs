import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const migration = readFileSync(
  new URL('../../database/migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php', import.meta.url),
  'utf8',
);

assert.ok(
  migration.includes("withStableItemIds($this->decodeItems($source->items ?? null), 'category')"),
  'Legacy Shop By Category items must receive stable item IDs during migration',
);

assert.ok(
  migration.includes("if ($key === 'shop_by_sport')"),
  'Existing Shop By Sport rows must be normalized during migration',
);

assert.ok(
  migration.includes("$normalizedItems = $this->withStableItemIds($existingItems, 'sport');"),
  'Existing Shop By Sport items must receive stable item IDs during migration',
);

assert.ok(
  migration.includes('private function withStableItemIds(array $items, string $prefix): array'),
  'Migration must define a deterministic stable-ID normalizer for legacy item collections',
);

console.log('Homepage legacy item ID migration contract passed.');
