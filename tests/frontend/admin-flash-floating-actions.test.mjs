import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const root = new URL('../../', import.meta.url);
const source = (name) => readFileSync(new URL(name, root), 'utf8');

test('all shared admin flash messages have a dismissal and a timer', () => {
    const layout = source('resources/views/components/layouts/admin.blade.php');
    const script = source('public/js/nextplay-admin-ui-fixes-20261104.js');
    assert.match(layout, /data-admin-flash role="status"/);
    assert.match(script, /initializeTransientAdminFlash/);
    assert.match(script, /window\.setTimeout\(dismiss, isError \? 7000 : 4500\)/);
    assert.match(script, /nextplay:admin-navigated', initializeTransientAdminFlash/);
});

test('the store pages share one permission-aware Storefront Settings disclosure', () => {
    const layout = source('resources/views/components/layouts/admin.blade.php');
    const group = layout.slice(layout.indexOf('label="Storefront Settings"'), layout.indexOf('label="Email Customization"'));
    for (const name of [
        'About Page', 'Shipping & Delivery Page', 'Size Guide Page', 'Artwork Guidelines',
        'How to Order Page', 'Returns & Refunds Page', 'Contact Us Page', 'Terms & Conditions',
        'Payment Information Page', 'Privacy Policy Page', 'Sustainability Page',
        'Home Page Control', 'Newsletter Emails',
    ]) assert.ok(group.includes(name), `${name} should remain accessible`);
    assert.match(group, /request\(\)->routeIs\('admin\.about-page/);
    assert.ok(group.indexOf('                        </x-admin.sidebar-group>\n                    @endif\n                    @if($canAdmin(\'email_customization.view\')') > 0);
});

test('table overflow cannot clip portalled action menu and the action column is sticky', () => {
    const view = source('resources/views/admin/personalization-templates/index.blade.php');
    const styles = source('public/css/nextplay-admin-ui-fixes-20261104.css');
    const script = source('public/js/nextplay-admin-ui-fixes-20261104.js');
    assert.match(view, /data-pt-action-trigger/);
    assert.match(view, /class="pt-menu-panel"/);
    assert.match(view, /id="pt-actions-\{\{ \$template->id \}\}" hidden/);
    assert.match(script, /document\.body\.appendChild\(panel\)/);
    assert.match(script, /const openAbove =/);
    assert.match(script, /document\.addEventListener\('scroll', reposition, true\)/);
    assert.match(styles, /body > \.pt-floating-menu/);
    assert.match(styles, /\.pt-list-card \.pt-table th:last-child/);
});
