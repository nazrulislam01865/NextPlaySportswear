import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const cssBlock = (source, selector) => {
  const start = source.indexOf(selector);
  assert.notEqual(start, -1, `${selector} must exist`);
  const open = source.indexOf('{', start);
  const close = source.indexOf('}', open);
  return source.slice(open + 1, close);
};

test('Make It Yours no-price cards use the compact prototype body without changing shared card geometry', () => {
  const section = read('resources/js/storefront/features/home/components/MakeItYours.vue');
  const card = read('resources/js/storefront/components/common/ProductCard.vue');
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(section, /:show-price="false"/);
  assert.match(card, /'np-product-card--no-price':\s*!showPrice/);
  assert.match(tokens, /--np-make-it-yours-card-body-height:\s*104px;/);

  const compactBody = cssBlock(card, '.np-product-card--new-arrivals.np-product-card--no-price .np-product-card__body');
  assert.match(compactBody, /flex:\s*0 0 var\(--np-make-it-yours-card-body-height\)/);
  assert.match(compactBody, /height:\s*var\(--np-make-it-yours-card-body-height\)/);
  assert.match(compactBody, /max-height:\s*var\(--np-make-it-yours-card-body-height\)/);
  assert.match(compactBody, /padding:\s*14px var\(--np-product-card-body-padding-inline\)/);

  const sharedBody = cssBlock(card, '.np-product-card--new-arrivals .np-product-card__body');
  assert.match(sharedBody, /height:\s*var\(--np-product-card-body-height\)/);
  assert.match(sharedBody, /padding:\s*20px var\(--np-product-card-body-padding-inline\) 20px/);
});
