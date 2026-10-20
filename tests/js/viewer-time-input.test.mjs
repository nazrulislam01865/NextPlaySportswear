import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Execute with TZ=Asia/Dhaka or America/New_York to simulate browser zones.
const code = readFileSync(new URL('../../public/js/viewer-time.js', import.meta.url), 'utf8');
const handlers = new Map();
const input = {
    nodeType: 1,
    value: process.env.ORIGINAL_UTC || '2026-10-20T05:34', // Laravel UTC datetime-local value
    name: 'estimated_delivery_at',
    dataset: {},
    matches() { return false; },
    addEventListener(type, callback) { handlers.set(type, callback); },
    setCustomValidity(msg) { this.validation = msg; },
};
const querySelectorAll = (sel) => sel.includes('data-np-utc-input') ? [input] : [];
const document = {
    nodeType: 9,
    readyState: 'complete',
    body: {},
    querySelectorAll,
    addEventListener(type, callback) { handlers.set(type, callback); },
};
vm.runInNewContext(code, { document, Date, Intl, MutationObserver: undefined });
const expected = process.env.EXPECTED_LOCAL || (process.env.TZ === 'Asia/Dhaka' ? '2026-10-20T11:34' : '2026-10-20T01:34');
assert.equal(input.value, expected, 'Edit form shows the browser local time');
const posted = {};
handlers.get('formdata')({ target: { querySelectorAll }, formData: { set(key, value) { posted[key] = value; } } });
assert.equal(posted.estimated_delivery_at, process.env.ORIGINAL_UTC || '2026-10-20T05:34', 'Submitted local time maps to the original UTC instant');
input.value = '';
handlers.get('formdata')({ target: { querySelectorAll }, formData: { set(key, value) { posted[key] = value; } } });
assert.equal(posted.estimated_delivery_at, process.env.ORIGINAL_UTC || '2026-10-20T05:34', 'Cleared field does not submit a false datetime');
console.log(`UTC edit/submit round-trip passed for ${process.env.TZ}`);
