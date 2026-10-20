import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const code = readFileSync(new URL('../../public/js/viewer-time.js', import.meta.url), 'utf8');

function display(utc, fmt, zone) {
    const element = {
        nodeType: 1,
        dataset: { npPhpFormat: fmt },
        attributes: { datetime: utc },
        getAttribute(key) { return this.attributes[key]; },
        matches() { return true; },
        textContent: '',
    };
    const doc = {
        readyState: 'complete',
        nodeType: 9,
        querySelectorAll(selector) { return selector === 'time[data-np-local-time]' ? [element] : []; },
        addEventListener() {},
        body: {},
    };
    // Supply an Intl wrapper resolving the requested IANA zone (each simulated
    // client has its own device timezone, independent of the Laravel server).
    function BrowserDateTimeFormat(locale, opts) {
        return new Intl.DateTimeFormat(locale, opts?.timeZone ? opts : { ...opts, timeZone: zone });
    }
    vm.runInNewContext(code, {
        document: doc,
        Intl: { DateTimeFormat: BrowserDateTimeFormat },
        Date,
        MutationObserver: undefined,
        Number,
        Object,
        String,
    });
    return { text: element.textContent, title: element.title };
}

assert.equal(display('2026-10-20T05:34:26.000000Z', 'M j, Y · g:i A', 'Asia/Dhaka').text, 'Oct 20, 2026 · 11:34 AM');
assert.equal(display('2026-10-20T05:34:26.000000Z', 'M j, Y · g:i A', 'America/New_York').text, 'Oct 20, 2026 · 1:34 AM');
assert.equal(display('2026-10-20T01:30:00.000000Z', 'M j, Y · g:i A', 'America/Los_Angeles').text, 'Oct 19, 2026 · 6:30 PM');
assert.equal(display('2026-03-08T06:30:00.000000Z', 'M d, Y H:i', 'America/New_York').text, 'Mar 08, 2026 01:30');
assert.equal(display('2026-03-08T07:30:00.000000Z', 'M d, Y H:i', 'America/New_York').text, 'Mar 08, 2026 03:30');
assert.equal(display('2026-10-20T01:30:00.000000Z', 'd M Y, h:i A', 'Europe/London').text, '20 Oct 2026, 02:30 AM');
assert.equal(display('2026-10-20T00:00:00.000000Z', 'l, F j, Y', 'Asia/Tokyo').text, 'Tuesday, October 20, 2026');
assert.equal(display('2026-10-20T01:30:00.000000Z', 'Y-m-d\\TH:i', 'Asia/Dhaka').text, '2026-10-20T07:30');
assert.match(display('2026-10-20T00:00:00.000000Z', 'M j, Y', 'Asia/Dhaka').title, /Asia\/Dhaka/);
console.log('Viewer timezone conversion: 9 cases passed (IANA zones, midnight boundaries, DST, and PHP formats)');
