import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('homepage no longer rebuilds legacy navigation after catalog cache invalidation', () => {
  const source = read('app/Services/Storefront/HomePageService.php');
  assert.doesNotMatch(source, /NavigationService/);
  assert.doesNotMatch(source, /storefrontMenus/);
  assert.doesNotMatch(source, /featured\(null\)/);
  assert.match(source, /featured_products_limit/);
});

test('header footer and navigation uploads use application-owned public media urls', () => {
  const media = read('app/Services/Storefront/StorefrontSettingsMediaService.php');
  const settings = read('app/Services/Storefront/StorefrontSettingsService.php');
  assert.match(media, /PublicMedia::storedPathUrl/);
  assert.match(media, /PublicMedia::storedPathFromUrl/);
  assert.match(settings, /normalizeManagedMediaUrls/);
  assert.match(settings, /PublicMedia::storedPathUrl/);
});

test('homepage section uploads use the same public media route', () => {
  const registry = read('app/Support/HomepageSectionRegistry.php');
  assert.match(registry, /PublicMedia::storedPathUrl/);
  assert.doesNotMatch(registry, /Storage::disk\('public'\)->url\(\$path\)/);
});
