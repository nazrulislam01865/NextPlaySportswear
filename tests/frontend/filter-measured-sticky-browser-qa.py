"""Browser QA for the shipped desktop catalog filter. Run with python3 and Playwright."""
from pathlib import Path
import json
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[2]
manifest = json.loads((root / 'public/build/manifest.json').read_text())
css = (root / 'public/build' / manifest['resources/css/storefront.css']['file']).read_text()
compiled = (root / 'public/build' / manifest['resources/js/storefront.js']['file']).read_text()
assert compiled.count('/* NEXTPLAY_FILTER_ANCHORED: canonical desktop handler */') == 1
script = compiled.split('/* NEXTPLAY_FILTER_ANCHORED: canonical desktop handler */', 1)[1]

category = ''.join(
    f'<details class="np-catalog-category-group"><summary>Category {i}</summary>'
    '<div class="np-catalog-category-children">'
    + ''.join(f'<label class="np-catalog-filter-option"><input type="checkbox">Option {j}</label>' for j in range(18))
    + '</div></details>' for i in range(7)
)
attributes = ''.join(
    f'<details class="np-catalog-filter-section"><summary>{name}</summary><div class="np-catalog-filter-options">'
    + ''.join(f'<label class="np-catalog-filter-option"><input type="checkbox">{name} {j}</label>' for j in range(10))
    + '</div></details>' for name in ['Sport', 'Product type', 'Gender', 'Price', 'Minimum quantity', 'Customization', 'Fabric']
)
html = (
    '<html><body class="storefront-clean-ui">'
    '<header class="storefront-site-header" style="height:90px;position:sticky;top:0;z-index:999;background:white">Menu</header>'
    '<section class="np-catalog-page" style="padding-top:60px">'
    '<div class="np-product-layout has-filters" style="display:grid;grid-template-columns:350px 1fr;gap:16px;padding:0 25px">'
    '<aside class="np-filter-shell np-filter-shell--clean" style="display:flex;flex-direction:column">'
    '<form class="np-catalog-filter-form"><div class="np-catalog-filter-header"><h3>Filters</h3></div>'
    f'<div class="np-catalog-filter-scroll"><div class="np-catalog-filter-category-section">{category}</div>{attributes}</div>'
    '<div class="np-catalog-filter-actions"><button type="button">Clear</button></div></form></aside>'
    '<div class="results" style="min-height:6500px">Product results</div></div></section></body></html>'
)

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True, executable_path='/usr/bin/chromium', args=['--no-sandbox'])
    page = browser.new_page(viewport={'width': 1700, 'height': 900})
    page.set_content(html)
    page.add_style_tag(content=css)
    page.add_script_tag(content=script)

    def info():
        return page.evaluate('''() => {
            const rail=document.querySelector('.np-filter-shell--clean');
            return {
                headerTop: rail.querySelector('h3').getBoundingClientRect().top,
                clearBottom: rail.querySelector('.np-catalog-filter-actions').getBoundingClientRect().bottom,
                offset: parseFloat(rail.style.getPropertyValue('--np-catalog-filter-sticky-top')),
                position: getComputedStyle(rail).position,
                innerScroll: getComputedStyle(rail.querySelector('.np-catalog-filter-scroll')).overflowY,
            };
        }''')

    page.wait_for_timeout(150)
    page.mouse.move(1050, 460)
    page.mouse.wheel(0, 650)
    page.wait_for_timeout(150)
    normal = info()
    assert normal['position'] == 'sticky' and normal['headerTop'] >= 90 and normal['innerScroll'] == 'visible', normal

    # Opening a section must not move the filter's top or hide its heading.
    page.locator('.np-catalog-filter-section summary').first.click()
    page.wait_for_timeout(150)
    expanded = info()
    assert expanded['headerTop'] >= 90 and abs(expanded['offset'] - normal['offset']) < 2, expanded

    # Intentionally navigate the filter itself, still using the PAGE scroll.
    page.mouse.move(200, 470)
    page.mouse.wheel(0, 900)
    page.wait_for_timeout(150)
    scrolled_filter = info()
    assert scrolled_filter['offset'] < normal['offset'], scrolled_filter
    assert scrolled_filter['clearBottom'] < 920, scrolled_filter

    # Moving back to the products may never conceal the first categories.
    page.mouse.move(1050, 460)
    page.mouse.wheel(0, 550)
    page.wait_for_timeout(150)
    restored = info()
    assert restored['headerTop'] >= 90 and abs(restored['offset'] - normal['offset']) < 2, restored

    # No desktop floating offsets may remain active in the mobile drawer.
    page.set_viewport_size({'width': 900, 'height': 900})
    page.wait_for_timeout(150)
    mobile = page.evaluate('''() => document.querySelector('.np-filter-shell--clean')
        .style.getPropertyValue('--np-catalog-filter-sticky-top')''')
    assert mobile == '', mobile

    browser.close()
    print('PASS: product scrolling stays anchored; expanded filters reachable; no inner scrollbar; mobile reset')
