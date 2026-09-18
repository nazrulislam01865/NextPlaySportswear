import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const php = readFileSync(new URL('../../app/Http/Requests/Admin/HomepageSectionRequest.php', import.meta.url), 'utf8');
for (const needle of [
  "'items.*.id'",
  "'items.*.image_path'",
  "'items.*.image_file'",
  "'items.*.remove_image'",
  "'settings' => ['nullable', 'array']",
  'validateAudience',
  'validateBestChoices',
  'validateDesignProcess',
  'cleanSettings',
  "HomepageSectionRegistry::definition((string) $this->route('key'))['sort_order']",
]) assert.ok(php.includes(needle), `Missing request contract: ${needle}`);
console.log('Homepage admin request contract passed.');
