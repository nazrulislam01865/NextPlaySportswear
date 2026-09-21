import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const resource=readFileSync(new URL('../../app/Http/Resources/Api/V1/Storefront/HomepageResource.php', import.meta.url),'utf8');
const service=readFileSync(new URL('../../app/Services/Storefront/HomepageSectionService.php', import.meta.url),'utf8');
const homeService=readFileSync(new URL('../../app/Services/Storefront/HomePageService.php', import.meta.url),'utf8');
for(const oldKey of ["'buyer_paths'", "'best_selling_gear_categories'", "'process_steps'", "'faqs'"]) assert.ok(!resource.includes(oldKey), `Legacy API key still exposed: ${oldKey}`);
assert.ok(service.includes("storefront.homepage-sections.v7"));
assert.ok(service.includes("whereIn('key'"));
for(const retiredMethod of ['buyerPaths','faqItems','bestSellingGearCategories','processSteps','faqs']) assert.ok(!homeService.includes(`function ${retiredMethod}(`), `Legacy homepage service method still present: ${retiredMethod}`);

assert.ok(!homeService.includes('NavigationService'), 'Homepage service must not depend on legacy navigation');
assert.ok(!homeService.includes('storefrontMenus'), 'Homepage service must not build legacy storefront menus');
assert.ok(!resource.includes("'navigation' =>"), 'Homepage API must not duplicate bootstrap navigation');
assert.ok(!resource.includes("'menus' =>"), 'Homepage API must not duplicate bootstrap menus');
assert.ok(homeService.includes("config('storefront.homepage.featured_products_limit', 12)"), 'Homepage featured query must be bounded');
console.log('Homepage API source contract passed.');
