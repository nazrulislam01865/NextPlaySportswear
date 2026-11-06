import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('bulk quote delivery retains required address and adds optional shipment notes with count', () => {
    const template = read('resources/views/storefront/bulk-quote/partials/contact-delivery.blade.php');
    assert.match(template, /name="shipping_address"[\s\S]*?required/);
    assert.match(template, /Shipment notes/);
    assert.match(template, /name="additional_notes"/);
    assert.match(template, /maxlength="2000"/);
    assert.match(template, /id="bulk-shipment-notes-length"/);
    assert.match(template, /<x-storefront\.bulk-quote\.field-error name="additional_notes"/);
});

test('shipment notes use existing validated payload rather than new schema', () => {
    const request = read('app/Http/Requests/Storefront/StoreBulkQuoteRequest.php');
    const model = read('app/Models/BulkQuoteRequest.php');
    const validator = read('public/js/bulk-quote-form-validation.js');
    assert.match(request, /'additional_notes' => \['nullable', 'string', 'max:2000'\]/);
    assert.match(model, /'additional_notes'/);
    assert.match(validator, /shipmentNotes\.addEventListener\('input', updateShipmentNotesCount\)/);
});

test('top bar has no bulk order link and hero slider has no teams badge', () => {
    const topbar = read('resources/views/components/storefront/topbar.blade.php');
    const hero = read('resources/views/components/storefront/home/hero.blade.php');
    assert.doesNotMatch(topbar, /bulk_order|<span>Bulk Order<\/span>/);
    assert.match(topbar, /topbar\['track_order_label'\]/);
    assert.match(topbar, /topbar\['whatsapp_label'\]/);
    assert.match(topbar, /route\('orders\.track'\)/);
    assert.doesNotMatch(hero, /class="hero-stat"/);
    assert.match(hero, /hero-carousel__dots/);
});
