import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('men category carousel keeps the four designed cards first and appends unused API categories', () => {
  const useMen = read('resources/js/storefront/features/men/composables/useMen.ts');

  assert.match(useMen, /const primaryCards\s*=\s*CARD_DEFINITIONS\.map/);
  assert.match(useMen, /const remainingCards\s*=\s*categories\.filter/);
  assert.match(useMen, /!used\.has\(key\)/);
  assert.match(useMen, /Boolean\(category\.short_title \|\| category\.title\)/);
  assert.doesNotMatch(useMen, /Boolean\(category\.banner \|\| category\.image\)/);
  assert.match(useMen, /return \[\.\.\.primaryCards,\s*\.\.\.remainingCards\]/);
});

test('men carousel opts into edge-aware controls without changing homepage default behavior', () => {
  const menPage = read('resources/js/storefront/features/men/pages/MenPage.vue');
  const categoryCarousel = read('resources/js/storefront/components/common/CategoryCarousel.vue');
  const sectionHeader = read('resources/js/storefront/components/ui/AppSectionHeader.vue');
  const controls = read('resources/js/storefront/components/common/CarouselControls.vue');

  assert.match(menPage, /<CategoryCarousel[\s\S]*?edge-aware-controls/);
  assert.match(categoryCarousel, /edgeAwareControls\?:\s*boolean/);
  assert.match(categoryCarousel, /const canSlidePrevious = ref\(false\)/);
  assert.match(categoryCarousel, /const canSlideNext = ref\(false\)/);
  assert.match(categoryCarousel, /instance\.isBeginning/);
  assert.match(categoryCarousel, /instance\.isEnd/);
  assert.match(categoryCarousel, /:previous-disabled="edgeAwareControls && !canSlidePrevious"/);
  assert.match(categoryCarousel, /:next-disabled="edgeAwareControls && !canSlideNext"/);

  assert.match(sectionHeader, /previousDisabled\?:\s*boolean/);
  assert.match(sectionHeader, /nextDisabled\?:\s*boolean/);
  assert.match(sectionHeader, /:previous-disabled="previousDisabled"/);
  assert.match(sectionHeader, /:next-disabled="nextDisabled"/);

  assert.match(controls, /previousDisabled\?:\s*boolean/);
  assert.match(controls, /nextDisabled\?:\s*boolean/);
  assert.match(controls, /:disabled="previousDisabled"/);
  assert.match(controls, /:disabled="nextDisabled"/);
});
