# NextPlay catalog filters: natural-height expansion

## Scope
All Products, Sale Products, and Category Products share these desktop filter components.
No changes to the filter values, filtering logic, URL/query parameters, typography, colors, buttons, or financial services.

## Fix
- Desktop sidebar and form use natural height and visible overflow; they are no longer viewport-height-capped or sticky. The **main browser page scroll** is used to reach lower filters when a group expands.
- Removed per-section auto-scroll caps (including category subtrees and color grids) from the reusable filter components and source stylesheet.
- Mobile/tablet (below `lg`/1024px) keeps its viewport-bounded drawer and **one** scrollable filter-content region. This is necessary so the last options and Clear action remain usable on shorter devices; individual expanded groups do not get nested scrollbars.
- The prebuilt Vite storefront CSS and `public/build/manifest.json` were updated too, so a ZIP replacement reflects the fix without requiring an immediate local Vite build. The source CSS remains authoritative for subsequent `npm run build` runs.

## Regression checks
Run `php tests/Regression/CatalogFilterNaturalExpansionTest.php` to confirm CSS, shared component usage, and shipped Vite asset. Also check `AllProductsFilterStableWidthAndFunctionTest.php` and `AllProductsFilterAutoApplyAndStableRailTest.php`. One existing unrelated clean-layout regression assertion still expects an older `Search` placeholder instead of the current `Search products`; this fix intentionally does not change existing UI copy.

## Manual UI checks
Open All Products, Sale, and a Category page on desktop: expand several filter sections and large category groups. The panel must grow vertically without an embedded scrollbar or clipped options; scroll the document to reach the Clear button. On mobile, open the filter drawer and verify it has one scrollable region and all options remain reachable. Check that applying filters and clearing selections still works.
