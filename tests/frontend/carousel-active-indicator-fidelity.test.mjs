import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('carousel controls move the active dash from the current carousel index', () => {
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');

  assert.match(controls, /activeIndex\?:\s*number/);
  assert.match(controls, /markerCount/);
  assert.match(controls, /activeMarkerIndex/);
  assert.match(controls, /props\.activeIndex/);
  assert.match(controls, /variant\s*===\s*'hero'\s*\?\s*3\s*:\s*2/);
  assert.match(controls, /v-for="markerIndex in markerCount"/);
  assert.match(controls, /markerIndex\s*-\s*1\s*===\s*activeMarkerIndex/);
  assert.match(controls, /np-carousel-controls__marker--active/);
});

test('hero feeds Swiper realIndex into the three-state indicator for autoplay and arrows', () => {
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');

  assert.match(hero, /const activeIndex = ref\(0\)/);
  assert.match(hero, /instance\.realIndex/);
  assert.match(hero, /@slide-change="setActiveIndex"/);
  assert.match(hero, /<CarouselControls[^>]*variant="hero"[^>]*:active-index="activeIndex"/s);
});

test('shared homepage showcase controls receive each section current index', () => {
  const header = read('resources/js/storefront/components/ui/AppSectionHeader.vue');
  const products = read('resources/js/storefront/components/common/ProductCarousel.vue');
  const categories = read('resources/js/storefront/components/common/CategoryCarousel.vue');
  const sport = read('resources/js/storefront/features/home/components/ShopBySport.vue');

  assert.match(header, /activeIndex\?:\s*number/);
  assert.match(header, /:active-index="activeIndex"/);

  for (const source of [products, categories]) {
    assert.match(source, /const activeIndex = ref\(0\)/);
    assert.match(source, /instance\.activeIndex/);
    assert.match(source, /@slide-change="setActiveIndex"/);
    assert.match(source, /:active-index="activeIndex"/);
  }

  assert.match(sport, /:active-index="activeIndex"/);
});
