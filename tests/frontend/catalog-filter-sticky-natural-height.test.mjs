import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';

const root = path.resolve(import.meta.dirname, '../..');
const source = fs.readFileSync(path.join(root, 'resources/js/storefront.js'), 'utf8');
const begin = source.indexOf('const setupCatalogStickyFilterRails = () => {');
const end = source.indexOf('const bootStorefront = () => {', begin);
assert.ok(begin >= 0 && end > begin);
const initializer = `${source.slice(begin, end)}\nsetupCatalogStickyFilterRails();`;

function makeHarness(initialHeight = 1200, viewport = 900, resultHeight = 6000) {
    let height = initialHeight;
    let desktop = true;
    const styles = new Map();
    const gridStyle = new Map();
    const names = new Set();
    const handlers = new Map();
    let resizeHandler;
    const result = { getBoundingClientRect: () => ({ height: resultHeight }) };
    const grid = { children: [], style: {
        set minHeight(value) { gridStyle.set('min-height', value); },
        get minHeight() { return gridStyle.get('min-height') || ''; },
        removeProperty(name) { gridStyle.delete(name); },
    } };
    const rail = {
        isConnected: true,
        parentElement: grid,
        getBoundingClientRect: () => ({ height, top: Number.parseFloat(styles.get('--np-catalog-filter-sticky-top') || '128') }),
        classList: {
            toggle(name, enabled) { if (enabled) names.add(name); else names.delete(name); },
            remove(...items) { for (const item of items) names.delete(item); },
            contains(name) { return names.has(name); },
        },
        style: {
            setProperty(name, value) { styles.set(name, value); },
            getPropertyValue(name) { return styles.get(name) || ''; },
            removeProperty(name) { styles.delete(name); },
        },
        contains(target) { return target === rail; },
        addEventListener(name, handler) { handlers.set('rail:' + name, handler); },
    };
    grid.children = [rail, result];
    const window = {
        ResizeObserver: null,
        innerHeight: viewport,
        requestAnimationFrame(fn) { fn(); return 0; },
        matchMedia: () => ({ get matches() { return desktop; } }),
        addEventListener(name, fn) { handlers.set(name, fn); },
    };
    const document = {
        querySelectorAll: () => [rail],
        querySelector: () => ({ getBoundingClientRect: () => ({ bottom: 112 }) }),
    };
    class ResizeObserver { constructor(fn) { resizeHandler = fn; } observe() {} }
    window.ResizeObserver = ResizeObserver;
    vm.runInNewContext(initializer, { window, document, ResizeObserver, Math, Number });
    return {
        rail, window, gridStyle,
        stickyTop: () => Number.parseFloat(styles.get('--np-catalog-filter-sticky-top')),
        grow(size) { height = size; resizeHandler(); },
        wheelFilter(deltaY) { handlers.get('rail:wheel')({ deltaY, deltaMode: 0, ctrlKey: false }); },
        wheelProducts(deltaY) { handlers.get('wheel')({ deltaY, target: result }); },
        resize(y) { window.innerHeight = y; handlers.get('resize')(); },
        mobile() { desktop = false; handlers.get('resize')(); },
    };
}

test('scrolling product cards leaves all starting filter options anchored', () => {
    const h = makeHarness(1400);
    assert.equal(h.rail.classList.contains('np-filter-rail--long'), true);
    assert.equal(h.stickyTop(), 128);
    h.wheelProducts(300);
    h.wheelProducts(1100);
    assert.equal(h.stickyTop(), 128);
});

test('deliberate wheel navigation over long filters reveals bottom without nested scrolling', () => {
    const h = makeHarness(1400);
    h.wheelFilter(400);
    assert.equal(h.stickyTop(), -400 + 128);
    h.wheelFilter(1900);
    assert.equal(h.stickyTop(), 900 - 16 - 1400);
    h.wheelFilter(-2000);
    assert.equal(h.stickyTop(), 128);
    h.wheelFilter(1800);
    h.wheelProducts(100);
    assert.equal(h.stickyTop(), 128, 'product browsing must reset filter to top');
});

test('an expanded filter retains its sticky rail; mobile removes desktop offset', () => {
    const h = makeHarness(400, 900, 300);
    assert.equal(h.rail.classList.contains('np-filter-rail--compact'), true);
    h.grow(1900);
    assert.equal(h.rail.classList.contains('np-filter-rail--long'), true);
    assert.equal(h.stickyTop(), 128);
    assert.ok(h.gridStyle.get('min-height'), 'short product pages need page-scroll runway');
    h.wheelFilter(1500);
    assert.ok(h.stickyTop() < 128);
    h.mobile();
    assert.equal(h.rail.classList.contains('np-filter-rail--long'), false);
    assert.equal(h.rail.style.getPropertyValue('--np-catalog-filter-sticky-top'), '');
});

test('source and shipped assets use one scroll-intent handler, no scroll-driven offset', () => {
    const manifest = JSON.parse(fs.readFileSync(path.join(root, 'public/build/manifest.json'), 'utf8'));
    const compiledJs = fs.readFileSync(path.join(root, 'public/build', manifest['resources/js/storefront.js'].file), 'utf8');
    const compiledCss = fs.readFileSync(path.join(root, 'public/build', manifest['resources/css/storefront.css'].file), 'utf8');
    assert.ok(compiledJs.includes('NEXTPLAY_FILTER_ANCHORED'));
    assert.ok(compiledJs.includes("rail.addEventListener('wheel'"));
    assert.equal(compiledJs.includes('NEXTPLAY_FILTER_DIRECTIONAL_FLOATING_V5'), false);
    assert.equal(source.includes("window.addEventListener('scroll', onScroll"), false);
    assert.ok(compiledCss.includes('overflow:visible!important'));
});
