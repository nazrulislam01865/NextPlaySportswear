import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const root = new URL('../../', import.meta.url);
const file = (path) => readFileSync(new URL(path, root), 'utf8');

const adminView = file('resources/views/admin/bulk-quote-icons/edit.blade.php');
const field = file('resources/views/components/admin/bulk-quote-icon-field.blade.php');
const adminCss = file('public/css/admin-bulk-quote-icons.css');
const publicCss = file('public/css/bulk-quote-form-system.css');
const theme = file('resources/css/storefront-theme.css');
const routes = file('routes/web.php');

test('Bulk Quote Icons uses shared Product Detail Controls card and savebar patterns', () => {
  assert.match(adminView, /np-pdui-page-intro/);
  assert.match(adminView, /np-pdui-section__header/);
  assert.match(adminView, /np-pdui-savebar/);
  assert.match(field, /np-pdui-control-card/);
  assert.match(field, /np-pdui-icon-field/);
  assert.match(field, /np-pdui-upload-button/);
});

test('All five icon slots remain present with existing file input names', () => {
  for (const key of ['product', 'contact', 'delivery', 'attachments', 'help']) {
    assert.match(adminView, new RegExp(`['\"]${key}['\"]`));
  }
  assert.match(field, /name="icons\[\{\{ \$iconKey \}\}\]"/);
  assert.match(field, /name="remove\[\{\{ \$iconKey \}\}\]"/);
  assert.match(adminView, /admin\.bulk-quote-icons\.update/);
});

test('Admin icon preview and storefront icons are frameless and exactly 52px with 1px inset', () => {
  for (const [name, css] of [['admin', adminCss], ['storefront', publicCss]]) {
    const section = name === 'admin' ? css.slice(css.indexOf('.np-bqic-page .np-bqic-icon-preview')) : css.slice(css.indexOf('/* Final storefront override:'));
    assert.match(section, /width:\s*52px/);
    assert.match(section, /height:\s*52px/);
    assert.match(section, /padding:\s*1px/);
    assert.match(section, /margin:\s*0/);
    assert.match(section, /background:\s*transparent/);
    assert.match(section, /border:\s*0/);
  }
});

test('Icon fields preview newly selected images and retain restore-default behavior', () => {
  assert.match(field, /URL\.createObjectURL/);
  assert.match(field, /URL\.revokeObjectURL/);
  assert.match(field, /Use default icon instead/);
  assert.match(field, /x-ref="iconFile"/);
  assert.match(field, /\$refs\.iconFile\.value = ''/);
});

test('Preview links target the actual Bulk Quote route', () => {
  assert.match(routes, /name\('quote\.request'\)/);
  assert.match(adminView, /route\('quote\.request'\)/);
  assert.doesNotMatch(adminView, /quote\.request\.create/);
});

test('Admin font follows the existing Expose system with no new font family', () => {
  assert.match(theme, /--np-font-ui:\s*"Expose"/);
  assert.match(adminCss, /var\(--admin-font-sans, var\(--np-font-body\)\)/);
  assert.doesNotMatch(adminCss, /@font-face/);
});
