import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const hero = read('resources/views/admin/homepage-sections/partials/_hero.blade.php');
const index = read('resources/views/admin/homepage-slides/index.blade.php');
const create = read('resources/views/admin/homepage-slides/create.blade.php');
const edit = read('resources/views/admin/homepage-slides/edit.blade.php');
const layout = read('resources/views/components/layouts/admin.blade.php');
const controller = read('app/Http/Controllers/Admin/HomepageSlideController.php');

assert.ok(hero.includes('Manage Slides'), 'Hero editor must expose a Manage Slides action');
assert.ok(hero.includes("route('admin.homepage-slides.index')"), 'Manage Slides must use existing slide manager');

for (const [name, view] of [['index', index], ['create', create], ['edit', edit]]) {
  assert.ok(view.includes('np-home-admin'), `${name} slide screen must inherit Homepage Controls theme`);
  assert.ok(view.includes("route('admin.homepage.sections.edit', 'hero')"), `${name} slide screen needs Back to Hero Banner link`);
}

assert.ok(!layout.includes('>Homepage Slider</x-admin.sidebar-link>'), 'Standalone Homepage Slider sidebar entry must stay retired');
for (const method of ['store', 'update', 'toggle', 'destroy']) {
  const start = controller.indexOf(`function ${method}(`);
  assert.notEqual(start, -1, `Missing ${method} mutation`);
  const next = controller.indexOf('\n    public function ', start + 1);
  const body = controller.slice(start, next === -1 ? controller.length : next);
  assert.ok(body.includes('$this->slider->flushCache();'), `${method} must flush slider cache`);
}

console.log('Homepage hero slide admin contract passed.');
