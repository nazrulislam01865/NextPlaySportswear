import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const builder = read('public/js/nextplay-quote-builder.js');
const view = read('resources/views/storefront/bulk-quote/create.blade.php');
const request = read('app/Http/Requests/Storefront/StoreBulkQuoteRequest.php');
const controller = read('app/Http/Controllers/Storefront/BulkQuoteController.php');

// No invented material, colour, size or budget fields in an existing product editor.
test('existing catalogue fields come only from product groups and size groups', () => {
    assert.match(builder, /const groups = product\.groups \|\| \[\]/);
    assert.match(builder, /groups\.forEach\(group =>/);
    assert.doesNotMatch(builder, /npq-existing-(?:material|colors|budget|sizes)/);
    assert.match(builder, /sizeHeading\.replaceChildren\('Quantity '/);
    assert.match(builder, /product\.material = materialGroup \? lookupValues\(materialGroup\) : ''/);
    assert.match(builder, /product\.colors = colorGroup \? lookupValues\(colorGroup\) : ''/);
    assert.match(view, /id="npq-existing-size-heading"/);
});

test('unlisted products retain mandatory specifications while catalogue requirements are conditional', () => {
    assert.match(request, /if \(\(\$product\['mode'\] \?\? ''\) === 'new'\) \{/);
    assert.match(request, /'material' => 'material \/ fabric'/);
    assert.match(request, /'colors' => 'colours'/);
    assert.match(request, /'sizes' => 'size \/ dimensions'/);
    assert.match(controller, /\$configuredValue\(\$snapshot\['options'\], '\/colou\?r\/i'\)/);
    assert.match(controller, /'budget' => \$snapshot \? ''/);
});
