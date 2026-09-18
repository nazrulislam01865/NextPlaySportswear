import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('men category carousel opts into a contained exact-card viewport', () => {
  const menPage = read('resources/js/storefront/features/men/pages/MenPage.vue');
  const categoryCarousel = read('resources/js/storefront/components/common/CategoryCarousel.vue');

  assert.match(menPage, /<CategoryCarousel[\s\S]*?contained/);
  assert.match(categoryCarousel, /contained\?:\s*boolean/);
  assert.match(categoryCarousel, /contained:false/);
  assert.match(categoryCarousel, /:slides-per-view="contained \? 1 : 1\.25"/);
  assert.match(categoryCarousel, /contained\s*\?\s*\{520:\{slidesPerView:2,spaceBetween:8\},900:\{slidesPerView:3,spaceBetween:10\},1280:\{slidesPerView:4,spaceBetween:10\}\}/);
  assert.match(categoryCarousel, /np-category-carousel--contained/);
  assert.match(categoryCarousel, /np-category-carousel--contained :deep\(\.swiper\)\{overflow:hidden\}/);
});

test('homepage category carousel keeps its existing peek behavior by default', () => {
  const categoryCarousel = read('resources/js/storefront/components/common/CategoryCarousel.vue');

  assert.match(categoryCarousel, /contained:false/);
  assert.match(categoryCarousel, /np-category-carousel :deep\(\.swiper\)\{overflow:visible\}/);
  assert.match(categoryCarousel, /2\.05/);
  assert.match(categoryCarousel, /3\.05/);
});
