import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const requiredFiles = [
  'resources/js/storefront/main.ts',
  'resources/js/storefront/App.vue',
  'resources/js/storefront/api/client.ts',
  'resources/js/storefront/features/home/api/home.api.ts',
  'resources/js/storefront/features/home/pages/HomePage.vue',
  'resources/js/storefront/components/common/ProductCard.vue',
  'resources/js/storefront/components/ui/AppButton.vue',
  'resources/js/storefront/styles/tokens.css',
  'resources/views/storefront-vue/home.blade.php',
];

test('vue homepage architecture files exist', () => {
  for (const file of requiredFiles) {
    assert.equal(fs.existsSync(path.join(root, file)), true, `${file} must exist`);
  }
});

test('home feature uses centralized api endpoints', () => {
  const endpoints = read('resources/js/storefront/api/endpoints.ts');
  const homeApi = read('resources/js/storefront/features/home/api/home.api.ts');
  assert.match(endpoints, /\/storefront\/bootstrap/);
  assert.match(endpoints, /\/home/);
  assert.match(homeApi, /apiClient/);
});

test('vue components do not make unmanaged network requests', () => {
  const componentRoots = [
    'resources/js/storefront/components',
    'resources/js/storefront/features/home/components',
    'resources/js/storefront/features/home/pages',
  ];
  for (const componentRoot of componentRoots) {
    const absolute = path.join(root, componentRoot);
    if (!fs.existsSync(absolute)) continue;
    const stack = [absolute];
    while (stack.length) {
      const current = stack.pop();
      for (const entry of fs.readdirSync(current, { withFileTypes: true })) {
        const full = path.join(current, entry.name);
        if (entry.isDirectory()) stack.push(full);
        if (entry.isFile() && entry.name.endsWith('.vue')) {
          const source = fs.readFileSync(full, 'utf8');
          assert.doesNotMatch(source, /axios\s*\.|fetch\s*\(/, `${full} contains unmanaged HTTP`);
        }
      }
    }
  }
});

test('design tokens centralize theme and typography', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  for (const token of ['--np-color-navy', '--np-color-orange', '--np-font-display', '--np-font-body', '--np-container-max']) {
    assert.match(tokens, new RegExp(token));
  }
});

test('homepage and migrated Men route are cut over to the Vue host while legacy pages remain available', () => {
  const routes = read('routes/web.php');
  assert.match(routes, /Route::get\('\/', VueHomeController::class\)->name\('home'\)/);
  assert.match(routes, /Route::get\('\/men', VueMenController::class\)->name\('men'\)/);
  assert.match(routes, /homepage\/latest-products/);
  assert.equal(fs.existsSync(path.join(root, 'resources/views/storefront/home.blade.php')), true);
});

test('homepage product sections reuse the shared product carousel and card stack', () => {
  for (const file of [
    'resources/js/storefront/features/home/components/NewArrivals.vue',
    'resources/js/storefront/features/home/components/BestChoices.vue',
    'resources/js/storefront/features/home/components/MakeItYours.vue',
  ]) {
    assert.match(read(file), /ProductCarousel/);
  }
  assert.match(read('resources/js/storefront/components/common/ProductCarousel.vue'), /ProductCard/);
});
