import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const layout = read('resources/views/components/layouts/admin.blade.php');
const seasonSale = read('resources/views/admin/homepage-sections/partials/_season-sale.blade.php');
const heroPartial = read('resources/views/admin/homepage-sections/partials/_hero.blade.php');
const slideIndex = read('resources/views/admin/homepage-slides/index.blade.php');
const slideForm = read('resources/views/admin/homepage-slides/_form.blade.php');
const sectionRequest = read('app/Http/Requests/Admin/HomepageSectionRequest.php');
const slideRequest = read('app/Http/Requests/Admin/HomepageSlideRequest.php');
const sectionMedia = read('app/Services/Catalog/HomepageSectionMediaService.php');
const slideMedia = read('app/Services/Catalog/HomepageSlideMediaService.php');
const sliderService = read('app/Services/Storefront/HomepageSliderService.php');
const types = read('resources/js/storefront/features/home/types/home.types.ts');
const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');
const sale = read('resources/js/storefront/features/home/components/SeasonSaleBanner.vue');

assert.match(layout, /<x-admin\.sidebar-group[\s\S]*label="Homepage Controls"/);
assert.doesNotMatch(layout, /<x-admin\.sidebar-link[\s\S]{0,260}>Homepage Controls<\/x-admin\.sidebar-link>/);
for (const key of [
  'hero',
  'audience',
  'shop_by_sport',
  'new_arrivals',
  'shop_by_category',
  'best_choices',
  'season_sale',
  'make_it_yours',
  'design_process',
]) {
  assert.ok(layout.includes(`route('admin.homepage.sections.edit', '${key}')`), `Missing Homepage Controls submenu for ${key}`);
}
for (const label of [
  'Hero Banner',
  'Audience Tiles',
  'Shop By Sport',
  'New Arrivals',
  'Shop By Category',
  'Best Choices For You',
  'Season Sale',
  'Make It Yours',
  'Design Process',
]) {
  assert.ok(layout.includes(`>${label}</x-admin.sidebar-sub-link>`), `Missing submenu label ${label}`);
}

assert.ok(seasonSale.includes('label="Banner image"'), 'Season Sale must expose one banner image');
assert.doesNotMatch(seasonSale, /Mobile banner image|mobile_image/i);
assert.doesNotMatch(heroPartial, /desktop\/mobile|mobile image/i);
assert.doesNotMatch(slideIndex, /mobile banner|mobile image/i);
assert.doesNotMatch(slideForm, /mobile_image|Mobile image|mobile banner|Mobile preview/i);

for (const source of [sectionRequest, slideRequest, sectionMedia]) {
  assert.doesNotMatch(source, /mobile_image_(file|url|alt)|remove_mobile_image/);
}
assert.doesNotMatch(slideMedia, /\$request->(?:file|input|boolean)\([^\n]*mobile_image/);

assert.doesNotMatch(sliderService, /'mobile_image'\s*=>|mobile_image_path|mobile_image_url|mobile_image_alt/);
assert.doesNotMatch(types, /mobile_image/);
assert.doesNotMatch(hero, /slide\.mobile_image|<source[^>]+max-width:640px/);
assert.doesNotMatch(sale, /mobileBackground|np-sale__image--mobile|section\?\.mobile_image/);

console.log('Homepage submenu and single responsive image contract passed.');
