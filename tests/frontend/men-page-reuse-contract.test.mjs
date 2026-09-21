import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const exists = (file) => fs.existsSync(path.join(root, file));

const menFiles = [
  'resources/js/storefront/features/men/api/men.api.ts',
  'resources/js/storefront/features/men/composables/useMen.ts',
  'resources/js/storefront/features/men/pages/MenPage.vue',
  'resources/js/storefront/features/men/types/men.types.ts',
  'resources/js/storefront/components/common/Breadcrumbs.vue',
];

test('men page feature exists and is served by Laravel at /men', () => {
  for (const file of menFiles) assert.equal(exists(file), true, `${file} must exist`);
  const routes = read('routes/web.php');
  assert.match(routes, /Route::get\('\/men',\s*VueMenController::class\)->name\('men'\)/);
  assert.equal(exists('app/Http/Controllers/Storefront/VueMenController.php'), true);
});

test('storefront app mounts the new men page without duplicating the shared header', () => {
  const app = read('resources/js/storefront/App.vue');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  assert.match(app, /MenPage/);
  assert.match(app, /window\.location\.pathname/);
  assert.match(men, /StorefrontHeader/);
  assert.match(men, /StorefrontFooter/);
  assert.doesNotMatch(men, /<header[\s>]/i);
});

test('men category strip reuses homepage category tiles and carousel controls', () => {
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  const carousel = read('resources/js/storefront/components/common/CategoryCarousel.vue');
  assert.match(men, /CategoryCarousel/);
  assert.match(men, /variant="showcase"/);
  assert.match(men, /controls-variant="showcase"/);
  assert.match(carousel, /ImageCategoryTile/);
  assert.match(carousel, /AppSectionHeader/);
  assert.match(read('resources/js/storefront/components/ui/AppSectionHeader.vue'), /CarouselControls/);
});

test('men page uses centralized APIs and authoritative product pagination total', () => {
  const endpoints = read('resources/js/storefront/api/endpoints.ts');
  const api = read('resources/js/storefront/features/men/api/men.api.ts');
  assert.match(endpoints, /categories:\s*['"]\/categories['"]/);
  assert.match(endpoints, /products:\s*['"]\/products['"]/);
  assert.match(api, /apiClient\.get/);
  assert.match(api, /params:\s*\{\s*q:\s*['"]men['"],\s*page,\s*per_page:\s*24,\s*\.\.\.filterParams\(filters\)\s*\}/);
  assert.match(api, /meta\.total/);
});

test('men page typography and layout values come from centralized design tokens', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  for (const token of [
    '--np-men-content-max',
    '--np-men-content-gutter',
    '--np-men-page-title-size',
    '--np-men-product-count-size',
    '--np-men-breadcrumb-size',
  ]) assert.match(tokens, new RegExp(token));
  assert.match(men, /var\(--np-font-display\)/);
  assert.match(men, /var\(--np-font-body\)/);
  assert.match(men, /var\(--np-men-page-title-size\)/);
  assert.match(men, /var\(--np-men-product-count-size\)/);
});

test('desktop and mobile header MEN links use the new /men page', () => {
  const settings = read('app/Services/Storefront/StorefrontSettingsService.php');
  const header = read('resources/js/storefront/components/layout/StorefrontHeader.vue');
  const mobile = read('resources/js/storefront/components/layout/MobileNavigation.vue');
  assert.match(settings, /'label'\s*=>\s*'MEN'[\s\S]{0,180}'url'\s*=>\s*'\/men'/);
  assert.match(header, /DesktopNavigation[^>]*:items="headerNavigation"/s);
  assert.match(header, /MobileNavigation[^>]*:items="headerNavigation"/s);
  assert.match(mobile, /item\.url/);
});
