import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

const project = new URL('../../', import.meta.url);
const source = fs.readFileSync(new URL('resources/js/storefront.js', project), 'utf8');
const start = source.indexOf('const createProductSocialActions =');
const end = source.indexOf('\n\nconst normalizeProductPreviewUrl', start);
assert.ok(start >= 0 && end > start, 'share actions should be extractable');

let clipboardText = '';
const shared = [];
const opened = [];
const location = {
    origin: 'http://127.0.0.1:8000',
    pathname: '/product/current-basketball-jersey',
    href: 'http://127.0.0.1:8000/product/current-basketball-jersey?cart_item=5',
};
const sandbox = {
    window: {
        location,
        isSecureContext: true,
        open(url) { opened.push(url); return { opener: null }; },
        showStorefrontToast() {},
    },
    navigator: {
        clipboard: { async writeText(text) { clipboardText = text; } },
        async share(payload) { shared.push(payload); },
    },
    document: { title: 'Product page' },
    URL,
};
const script = `${source.slice(start, end)}\ncreateProductSocialActions({ product_id: 2, slug: 'current-basketball-jersey', title: 'Custom Basketball Jersey', url: '/product/current-basketball-jersey', authenticated: false });`;
const actions = vm.runInNewContext(script, sandbox);

assert.equal(actions.shareOpen, false);
actions.shareProduct();
assert.equal(actions.shareOpen, true, 'button reveals the site menu');
assert.equal(shared.length, 0, 'button does not launch native share automatically');
assert.equal(actions.sharePayload().url, 'http://127.0.0.1:8000/product/current-basketball-jersey');
await actions.copyProductLink();
assert.equal(clipboardText, 'http://127.0.0.1:8000/product/current-basketball-jersey');
assert.equal(actions.shareOpen, false);
for (const [channel, domain] of [['whatsapp', 'wa.me'], ['facebook', 'www.facebook.com'], ['x', 'twitter.com']]) {
    actions.shareThrough(channel);
    const url = new URL(opened.at(-1));
    assert.equal(url.hostname, domain, `${channel} should open the correct destination`);
    assert.ok(url.search.includes(encodeURIComponent(clipboardText)), `${channel} should contain the public product URL`);
}
await actions.shareWithDevice();
assert.equal(shared.length, 1, 'native option available on explicit click');
assert.equal(shared[0].url, clipboardText);
assert.equal(Object.hasOwn(shared[0], 'files'), false, 'native sharing must not copy an image file');

actions.socialConfig.url = 'https://old-example.invalid/404-product';
assert.equal(actions.sharePayload().url, 'http://127.0.0.1:8000/product/current-basketball-jersey', 'off-site stale canonical must not be shared');
console.log('Product sharing browser interaction tests passed.');
