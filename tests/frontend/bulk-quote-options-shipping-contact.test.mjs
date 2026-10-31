import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const quote = read('resources/views/storefront/bulk-quote/create.blade.php');
const productJs = read('public/js/bulk-quote-product.js');
const selectJs = read('public/js/bulk-quote-selects.js');
const optionService = read('app/Services/BulkQuote/QuoteProductInformation.php');
const css = read('public/css/bulk-quote-enhancements.css');
const admin = read('resources/views/admin/bulk-quote-icons/edit.blade.php');
const routes = read('routes/web.php');
const layout = read('resources/views/components/layouts/storefront.blade.php');

test('existing product-option images are exposed without any new query or field', () => {
    assert.match(optionService, /'image' => \$value->publicImageUrl\(\)/);
    assert.match(optionService, /'id' => \(string\) \$value->id/);
    assert.match(productJs, /bulk-quote-product-image-choices/);
    assert.match(productJs, /optionPicture\(value\)/);
    assert.match(productJs, /input\('radio', name, value\.id\)/);
    assert.match(productJs, /\[options\]\[\$\{group\.id\}\]/);
});

test('shipping method options have icons but preserve the original select', () => {
    assert.match(quote, /id="bulk-shipping-method" name="preferred_shipping_method"/);
    for (const value of ['standard', 'express', 'rush', 'recommend']) {
        assert.match(quote, new RegExp(`'${value}' =>`));
    }
    assert.match(quote, /data-icon-url="\{\{ \$shippingOptionIcons\[\$value\] \}\}"/);
    assert.match(selectJs, /option\?\.dataset\.iconUrl/);
    assert.match(selectJs, /document\.createElement\('img'\)/);
    assert.doesNotMatch(selectJs, /createElementNS|const paths =/);
    assert.match(selectJs, /select\.id === 'bulk-shipping-method'/);
    assert.match(selectJs, /select\.dispatchEvent\(new Event\('change'/);
});

test('attachments first, notes full width, no privacy icon or request summary', () => {
    assert.ok(quote.indexOf('id="bulk-attachment"') < quote.indexOf('id="bulk-notes"'));
    assert.match(quote, /bulk-quote-field nps-field bulk-quote-full" x-data="\{ count:/);
    assert.match(css, /bulk-quote-attachments-card \.bulk-quote-grid > \.bulk-quote-field\.bulk-quote-full/);
    assert.doesNotMatch(quote, /bulk-quote-privacy-icon|bulk-quote-request-summary|bulk-quote-summary\.js/);
    assert.match(quote, /Your details are used to prepare and manage your quotation/);
    assert.match(quote, /Need a hand\?/);
});

test('WhatsApp number is admin editable with old permissions and no DB migration', () => {
    assert.match(admin, /admin\.bulk-quote-icons\.contact\.update/);
    assert.match(admin, /name="whatsapp_number"/);
    assert.match(routes, /bulk-quote-icons\/contact/);
    assert.match(quote, /BulkQuoteContact::whatsappUrl\(\)/);
    assert.match(layout, /bulk-quote-enhancements\.css/);
    assert.doesNotMatch(read('app/Support/BulkQuoteContact.php'), /DB::|::query\(/);
    assert.match(read('app/Http/Controllers/Admin/BulkQuoteIconController.php'), /canAdmin\('storefront_branding\.manage'\)/);
});


test('shipping uploads retain original 20px size; five section uploads remain at 52px', () => {
    assert.match(selectJs, /image\.width = 20;\s*image\.height = 20;/);
    assert.match(css, /flex: 0 0 20px;[^\n]*width: 20px; height: 20px;/);
    assert.match(read('app/Support/BulkQuoteIcons.php'), /'product' => 'Product & Customisation'/);
    for (const title of ['Contact Information', 'Delivery Requirements', 'Attachments & Notes', 'Need a hand?']) {
        assert.ok(read('app/Support/BulkQuoteIcons.php').includes(title));
    }
    assert.match(read('public/css/bulk-quote-form-system.css'), /\.bulk-quote-uploaded-icon \{ display:block; width:52px; height:52px/);
});
