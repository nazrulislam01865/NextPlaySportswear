import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';

const root = new URL('../../', import.meta.url);
const read = (path) => readFileSync(new URL(path, root), 'utf8');

const registryPath = new URL('app/Support/HomepageImageAspectRatios.php', root);
const rulePath = new URL('app/Rules/ApproximateImageAspectRatio.php', root);

assert.ok(existsSync(registryPath), 'Homepage image aspect ratios must be centralized');
assert.ok(existsSync(rulePath), 'Homepage uploads need a reusable tolerant aspect-ratio rule');

const registry = read('app/Support/HomepageImageAspectRatios.php');
for (const [name, ratio] of [
  ['hero slide', '8:3'],
  ['audience tile', '672:427'],
  ['shop by sport', '3:1'],
  ['shop by category', '26:25'],
  ['season sale', '4:1'],
  ['design process', '16:9'],
]) {
  assert.ok(registry.includes(`'ratio' => '${ratio}'`), `${name} must expose target ratio ${ratio}`);
}
assert.ok(registry.includes('0.05'), 'Homepage ratio validation must allow a small 5% crop-safe tolerance');

const rule = read('app/Rules/ApproximateImageAspectRatio.php');
assert.ok(rule.includes('getimagesize'), 'Ratio rule must inspect the uploaded image dimensions');
assert.ok(rule.includes('matches'), 'Ratio rule must delegate ratio math to the central registry');
assert.ok(rule.includes('Any suitable resolution is accepted'), 'Validation message must explain that pixel resolution is flexible');

const slideRequest = read('app/Http/Requests/Admin/HomepageSlideRequest.php');
assert.ok(!slideRequest.includes('dimensions:ratio=8/3'), 'Hero slides must no longer use Laravel exact dimensions ratio validation');
assert.ok(slideRequest.includes('ApproximateImageAspectRatio'), 'Hero slides must use tolerant aspect-ratio validation');
assert.ok(slideRequest.includes('HomepageImageAspectRatios::heroSlide()'), 'Hero slide ratio must come from central configuration');

const sectionRequest = read('app/Http/Requests/Admin/HomepageSectionRequest.php');
assert.ok(sectionRequest.includes('HomepageImageAspectRatios::forSectionImage'), 'Section image ratios must come from central configuration');
assert.ok(sectionRequest.includes('HomepageImageAspectRatios::forSectionItem'), 'Section item ratios must come from central configuration');
assert.ok(sectionRequest.includes('ApproximateImageAspectRatio'), 'Homepage section uploads must use tolerant aspect-ratio validation');

const slideForm = read('resources/views/admin/homepage-slides/_form.blade.php');
const slideIndex = read('resources/views/admin/homepage-slides/index.blade.php');
assert.ok(slideForm.includes('Target aspect ratio'), 'Hero upload field must show the target aspect ratio');
assert.ok(slideForm.includes("$imageAspect['ratio']"), 'Hero upload field must render the centralized hero ratio');
assert.ok(!slideForm.includes('2560×960'), 'Hero editor must not require a pixel resolution');
assert.ok(!slideForm.includes('1920×720'), 'Hero editor must not advertise alternate pixel dimensions');
assert.ok(!slideIndex.includes('2560×960'), 'Hero slide index must describe ratio, not pixel dimensions');

const mediaField = read('resources/views/components/admin/homepage/media-field.blade.php');
assert.ok(mediaField.includes('aspectRatio'), 'Shared homepage media field must accept an aspect-ratio hint');
assert.ok(mediaField.includes('Target aspect ratio'), 'Shared homepage media field must show the target ratio');
assert.ok(mediaField.includes('Resolution is flexible'), 'Shared uploader must explain resolution is flexible');

for (const path of [
  'resources/views/admin/homepage-sections/partials/_audience.blade.php',
  'resources/views/admin/homepage-sections/partials/_shop-by-sport.blade.php',
  'resources/views/admin/homepage-sections/partials/_shop-by-category.blade.php',
  'resources/views/admin/homepage-sections/partials/_season-sale.blade.php',
  'resources/views/admin/homepage-sections/partials/_design-process.blade.php',
]) {
  const view = read(path);
  assert.ok(view.includes('aspect'), `${path} must pass or display its upload aspect ratio`);
}

console.log('Homepage image aspect-ratio contract passed.');
