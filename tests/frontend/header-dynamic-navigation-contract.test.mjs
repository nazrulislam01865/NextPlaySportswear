import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const exists = (file) => fs.existsSync(path.join(root, file));

test('approved Vue header menu defaults live in the standalone navigation settings key', () => {
  const service = read('app/Services/Storefront/StorefrontSettingsService.php');

  assert.match(service, /public const NAVIGATION_KEY = 'navigation'/);
  assert.match(service, /public function navigation\(\): array/);
  assert.match(service, /public function updateNavigation\(/);
  assert.match(service, /self::NAVIGATION_KEY\s*=>\s*\[/);
  for (const label of ['SHOP', 'SPORTS', 'MEN', 'WOMEN', 'KIDS', 'CUSTOM TEAMWEAR', 'EXPLORE']) {
    assert.match(service, new RegExp(`'label'\\s*=>\\s*'${label.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}'`));
  }
  for (const megaText of ['Top Choices', 'BEST SELLERS', 'ON SALE', 'FEATURED FOR YOU', 'SUMMER 2026', 'NEW ARRIVALS', 'Shop All Products']) {
    assert.match(service, new RegExp(megaText.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')));
  }
});


test('navigation extraction migration copies header navigation only when the standalone row is absent', () => {
  const migration = read('database/migrations/2026_09_21_160000_extract_navigation_from_header_settings.php');
  assert.match(migration, /where\('key', 'navigation'\)->exists\(\)/);
  assert.match(migration, /\$settings\['navigation'\]/);
  assert.match(migration, /'key'\s*=>\s*'navigation'/);
  assert.match(migration, /Intentionally keep the extracted navigation row on rollback/);
});

test('header settings no longer owns or preserves navigation', () => {
  const controller = read('app/Http/Controllers/Admin/HeaderSettingsController.php');
  const request = read('app/Http/Requests/Admin/HeaderSettingsRequest.php');
  const admin = read('resources/views/admin/storefront-settings/header.blade.php');

  assert.doesNotMatch(controller, /array_key_exists\('navigation'/);
  assert.doesNotMatch(controller, /\$submitted\['navigation'\]/);
  assert.doesNotMatch(request, /navigation\.items|cleanNavigationItems|cleanMegaColumns/);
  assert.doesNotMatch(admin, /header-navigation|data-header-navigation|Header Menu &amp; Mega Menu/);
});

test('standalone navigation admin owns the mega-menu editor and four-column guard', () => {
  const controller = read('app/Http/Controllers/Admin/NavigationSettingsController.php');
  const request = read('app/Http/Requests/Admin/NavigationSettingsRequest.php');
  const admin = read('resources/views/admin/storefront-settings/partials/navigation-menu-editor.blade.php');
  const page = read('resources/views/admin/storefront-settings/navigation.blade.php');

  assert.match(controller, /updateNavigation/);
  assert.match(controller, /prepareNavigation/);
  assert.match(request, /mega_menu\.columns/);
  assert.match(request, /'max:4'/);
  assert.match(admin, /data-column/);
  assert.match(admin, /querySelectorAll\('\[data-column\]'\)\.length\s*>=\s*4/);
  assert.match(page, /admin\.navigation-settings\.update/);
  assert.match(page, /Navigation Menu/);
});

test('legacy navigation admin surface is removed while legacy backend models can remain', () => {
  const routes = read('routes/web.php');
  const sidebar = read('resources/views/components/layouts/admin.blade.php');
  const rbac = read('app/Support/AdminRbac.php');

  assert.doesNotMatch(routes, /Route::resource\('menus'/);
  assert.match(routes, /navigation-settings/);
  assert.doesNotMatch(sidebar, /admin\.menus|menus\.view|Navigation Menus/);
  assert.match(sidebar, /navigation_settings\.view/);
  assert.doesNotMatch(rbac, /'menus\.view'|'menus\.manage'|admin\.menus/);
  assert.match(rbac, /navigation_settings\.view/);
  assert.match(rbac, /navigation_settings\.manage/);

  assert.equal(exists('app/Http/Controllers/Admin/MenuController.php'), false);
  assert.equal(exists('app/Http/Requests/Admin/MenuFormRequest.php'), false);
  assert.equal(exists('resources/views/admin/menus/index.blade.php'), false);
});

test('bootstrap and Vue header use standalone navigation settings instead of legacy NavigationService or header.navigation', () => {
  const bootstrap = read('app/Services/Storefront/StorefrontBootstrapService.php');
  const resource = read('app/Http/Resources/Api/V1/Storefront/StorefrontBootstrapResource.php');
  const store = read('resources/js/storefront/stores/storefront.store.ts');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');

  assert.doesNotMatch(bootstrap, /NavigationService|storefrontMenus\(/);
  assert.match(bootstrap, /\$this->settings->navigation\(\)/);
  assert.match(resource, /'navigation'\s*=>\s*\(array\)\s*\(\$this->resource\['navigation'\]/);
  assert.doesNotMatch(resource, /NavigationItemResource|private function menus|private function navigationItems/);
  assert.match(store, /interface NavigationSettings/);
  assert.match(store, /navigation:\s*NavigationSettings/);
  assert.doesNotMatch(store, /navigation\?:\s*\{[\s\S]*HeaderNavigationItemSetting/);
  assert.match(header, /storefront\.navigation\.items/);
  assert.doesNotMatch(header, /storefront\.header\.navigation/);
});

test('desktop and mobile navigation continue to share the same dynamic item array', () => {
  const mobile = read('resources/js/storefront/components/layout/MobileNavigation.vue');
  const desktop = read('resources/js/storefront/components/layout/DesktopNavigation.vue');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');

  assert.match(mobile, /defineProps/);
  assert.match(desktop, /defineProps/);
  assert.match(header, /DesktopNavigation[^>]*:items="headerNavigation"/s);
  assert.match(header, /MobileNavigation[^>]*:items="headerNavigation"/s);
});
