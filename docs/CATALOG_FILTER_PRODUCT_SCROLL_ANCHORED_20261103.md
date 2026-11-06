# Catalog filter sticky: prevent hidden options during product scrolling

The previous code subtracted every `window.scrollY` change from the sticky filter's `top`. Consequently, scrolling products moved the *entire filter* above the sticky navigation and hid categories despite no interaction with the filter.

## Corrected behavior

- Shared sidebar in All Products, Sale and Category listing pages remains `position: sticky` on desktop.
- Scrolling the **product grid** keeps the filter heading and starting categories anchored under the sticky navigation.
- Opening or closing category/attribute groups does not change the sticky offset or impose an inner scrollbar.
- For a rail taller than the viewport, deliberately scrolling **over the filter** moves through the tall contents using the normal document scrollbar. Mousewheel over product cards resets the filter to its top position. This is necessary to reach options below the viewport without an inner scrollbar.
- Mobile drawers retain their existing independent accessible scrolling behavior.
- The filter does not pin past the bottom of its containing listing section; it yields before the next page section/footer.
- No storefront design, filter query, backend, order or payment logic was changed.

## Validation

- `node --test tests/frontend/catalog-filter-sticky-natural-height.test.mjs`
- `php tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php`
- `php tests/Regression/CatalogFilterNaturalExpansionTest.php`
- `php tests/Regression/AllProductsFilterCleanLayoutTest.php`
- `python3 tests/frontend/filter-measured-sticky-browser-qa.py` (optional Chromium + Playwright)

The browser QA uses the compiled JS/CSS files referenced by the shipped Vite manifest. The page in production still requires a final visual check with the real product data and navigation heights.
