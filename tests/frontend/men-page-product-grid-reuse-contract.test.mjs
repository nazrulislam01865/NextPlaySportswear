import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const exists = (file) => fs.existsSync(path.join(root, file));

test('men page renders a reusable product grid below the category carousel', () => {
  assert.equal(exists('resources/js/storefront/components/common/ProductGrid.vue'), true);
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  assert.match(men, /ProductGrid/);
  assert.match(men, /:products="men\.products\.value"/);
  assert.match(men, /card-variant="new-arrivals"/);
  assert.match(men, /@add="actions\.addToCart"/);
  assert.match(men, /@wishlist="actions\.addToWishlist"/);
});

test('shared product grid reuses the exact ProductCard component used by New Arrivals', () => {
  const grid = read('resources/js/storefront/components/common/ProductGrid.vue');
  const newArrivals = read('resources/js/storefront/features/home/components/NewArrivals.vue');

  assert.match(grid, /import ProductCard from '\.\/ProductCard\.vue'/);
  assert.match(grid, /<ProductCard/);
  assert.match(grid, /:variant="cardVariant"/);
  assert.match(newArrivals, /card-variant="new-arrivals"/);
  assert.doesNotMatch(grid, /np-product-card__media/);
  assert.doesNotMatch(grid, /np-product-grid__cell\s+:deep\(\.np-product-card\)[\s\S]*?height:\s*100%/);
});

test('men product grid keeps the approved New Arrivals card scale with a blank left desktop area', () => {
  const grid = read('resources/js/storefront/components/common/ProductGrid.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  const newArrivals = read('resources/js/storefront/features/home/components/NewArrivals.vue');

  assert.match(tokens, /--np-men-product-grid-gap:\s*10px/);
  assert.match(tokens, /--np-men-product-grid-margin-top/);
  assert.match(tokens, /--np-men-product-grid-desktop-width:\s*75%/);
  assert.match(newArrivals, /max-width:\s*min\(var\(--np-showcase-max\),\s*calc\(100vw - 24px\)\)/);
  assert.match(newArrivals, /padding-inline:\s*var\(--np-showcase-gutter\)/);

  assert.match(grid, /desktopLeadingSpacer/);
  assert.match(grid, /np-product-grid--desktop-leading-spacer/);
  assert.match(grid, /width:\s*var\(--np-product-grid-desktop-width,\s*75%\)/);
  assert.match(grid, /margin-left:\s*auto/);
  assert.match(grid, /margin-right:\s*0/);
  assert.match(grid, /repeat\(4,\s*minmax\(0,\s*1fr\)\)/);
  assert.doesNotMatch(grid, /repeat\(5,\s*minmax\(0,\s*1fr\)\)/);
  assert.doesNotMatch(grid, /nth-child\(4n\+1\)/);
  assert.match(men, /desktop-leading-spacer/);
  assert.match(men, /--np-product-grid-desktop-width:\s*var\(--np-men-product-grid-desktop-width\)/);

  assert.match(grid, /repeat\(3,\s*minmax\(0,\s*1fr\)\)/);
  assert.match(grid, /repeat\(2,\s*minmax\(0,\s*1fr\)\)/);
  assert.match(grid, /grid-template-columns:\s*1fr/);
});

test('men API renders one backend-paginated product page at a time instead of fetching the full catalog', () => {
  const api = read('resources/js/storefront/features/men/api/men.api.ts');
  const types = read('resources/js/storefront/features/men/types/men.types.ts');
  const composable = read('resources/js/storefront/features/men/composables/useMen.ts');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');
  const pagination = read('resources/js/storefront/components/ui/AppPagination.vue');
  const catalogConfig = read('config/catalog.php');
  const productRequest = read('app/Http/Requests/Api/V1/Catalog/ProductIndexRequest.php');
  const catalogRead = read('app/Services/Catalog/CatalogReadService.php');

  assert.match(catalogConfig, /products_page_size'\s*=>\s*\(int\) env\('CATALOG_PRODUCTS_PAGE_SIZE',\s*24\)/);
  assert.match(types, /products:\s*StorefrontProduct\[\]/);
  assert.match(types, /currentPage:\s*number/);
  assert.match(types, /lastPage:\s*number/);
  assert.match(types, /perPage:\s*number/);
  assert.match(api, /fetchMenPage\(page\s*=\s*1\)/);
  assert.match(api, /params:\s*\{\s*q:\s*['"]men['"],\s*page,\s*per_page:\s*24\s*\}/);
  assert.match(productRequest, /['"]per_page['"]\s*=>\s*\[[^\]]*['"]integer['"][^\]]*['"]max:60['"]/s);
  assert.match(catalogRead, /searchPaginated\(\$filters,\s*\$perPage\)/);
  assert.doesNotMatch(api, /Promise\.all\(Array\.from/);
  assert.match(api, /function toStorefrontProduct/);
  assert.match(composable, /currentPage/);
  assert.match(composable, /lastPage/);
  assert.match(composable, /async function load\(page = 1\)/);
  assert.match(men, /AppPagination/);
  assert.match(men, /@change="men\.load"/);
  assert.match(pagination, /currentPage/);
  assert.match(pagination, /lastPage/);
});

test('home and men pages share one centralized product action composable', () => {
  assert.equal(exists('resources/js/storefront/composables/useProductActions.ts'), true);
  const actions = read('resources/js/storefront/composables/useProductActions.ts');
  const homeActions = read('resources/js/storefront/features/home/composables/useHomeActions.ts');
  const men = read('resources/js/storefront/features/men/pages/MenPage.vue');

  assert.match(actions, /addProductToCart/);
  assert.match(actions, /addProductToWishlist/);
  assert.match(homeActions, /useProductActions/);
  assert.match(men, /useProductActions/);
});
