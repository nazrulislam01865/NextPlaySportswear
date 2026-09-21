import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const partial = read('resources/views/admin/homepage-sections/partials/_shop-by-sport.blade.php');
const edit = read('resources/views/admin/homepage-sections/edit.blade.php');
const request = read('app/Http/Requests/Admin/HomepageSectionRequest.php');
const media = read('app/Services/Catalog/HomepageSectionMediaService.php');
const registry = read('app/Support/HomepageSectionRegistry.php');
const types = read('resources/js/storefront/features/home/types/home.types.ts');
const component = read('resources/js/storefront/features/home/components/ShopBySport.vue');

assert.match(partial, /Sport slides & background images/);
assert.match(partial, /Upload \/ replace background/);
assert.match(partial, /Background image alt text/);
assert.match(partial, /Buttons for this sport/);
assert.match(partial, /Each new sport starts with four editable buttons/);
assert.match(partial, /\+ Add Button/);
assert.match(partial, /Button text/);
assert.match(partial, /Button link/);
assert.match(partial, /items\[\$\{index\}\]\[buttons\]\[\$\{buttonIndex\}\]\[label\]/);
assert.match(partial, /items\[\$\{index\}\]\[buttons\]\[\$\{buttonIndex\}\]\[url\]/);
assert.match(partial, /items\[\$\{index\}\]\[buttons_configured\]/);
assert.match(partial, /\+ Add Sport/);
assert.doesNotMatch(partial, /settings\[quick_links\]/);

assert.match(edit, /addButton\(item\)/);
assert.match(edit, /removeButton\(item, buttonIndex\)/);
assert.match(edit, /buttons: kind === 'sport' \? freshSportButtons\(\) : \[\]/);
assert.match(edit, /Object\.prototype\.hasOwnProperty\.call\(item \|\| \{\}, 'buttons'\)/);
assert.match(edit, /item\?\.buttons_configured/);
assert.match(request, /items\.\*\.buttons\.\*\.label/);
assert.match(request, /items\.\*\.buttons\.\*\.url/);
assert.match(request, /Enter button text or remove this button/);
assert.match(media, /\$clean\['buttons'\] = \$this->cleanSportButtons/);
assert.match(registry, /unset\(\$merged\['quick_links'\]\)/);
assert.match(registry, /'label' => 'JERSEY'/);
assert.match(registry, /'label' => 'BOTTOMS'/);
assert.match(registry, /'label' => 'UNIFORM KITS'/);
assert.match(registry, /'label' => 'ACCESSORIES'/);
assert.match(registry, /array_key_exists\('buttons', \$item\)/);
assert.match(registry, /unset\(\$merged\['item_button_defaults'\]\)/);
assert.match(types, /interface HomeSportButton/);
assert.match(types, /buttons\?: HomeSportButton\[\]/);

assert.match(component, /v-if="active\.buttons\.length"/);
assert.match(component, /v-for="\(button, index\) in active\.buttons"/);
assert.match(component, /:href="button\.url"/);
assert.match(component, /\{\{ button\.label \}\}/);
assert.doesNotMatch(component, /@click="setActiveSport\(index\)"/);
assert.doesNotMatch(component, /generated automatically/);

console.log('Shop By Sport user-defined per-sport button controls contract passed.');
