import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
const index=readFileSync(new URL('../../resources/views/admin/homepage-sections/index.blade.php', import.meta.url),'utf8');
const edit=readFileSync(new URL('../../resources/views/admin/homepage-sections/edit.blade.php', import.meta.url),'utf8');
const layout=readFileSync(new URL('../../resources/views/components/layouts/admin.blade.php', import.meta.url),'utf8');
assert.ok(index.includes('np-home-admin'));
assert.ok(index.includes('Homepage Control Center'));
assert.ok(layout.includes('Homepage Controls'));
assert.ok(!layout.includes('@foreach($homepageDefinitions'));
for(const part of ['_hero','_audience','_shop-by-sport','_new-arrivals','_shop-by-category','_best-choices','_season-sale','_make-it-yours','_design-process']){
  assert.ok(edit.includes(part), `Missing partial map ${part}`);
}
for(const file of ['section-panel','text-field','link-field','media-field','visibility-field','item-card','save-bar']){
  assert.ok(existsSync(new URL(`../../resources/views/components/admin/homepage/${file}.blade.php`, import.meta.url)), `Missing ${file}`);
}
console.log('Homepage admin control center contract passed.');
