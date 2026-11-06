# NextPlay catalog filter: sticky rail + natural expansion (2026-11-03)

## Problem

The natural-expansion release removed `position: sticky` from the desktop catalog filter rail. This unintentionally changed its floating behavior on All Products, Sale Products, and Category Products.

## Correction

- Restored desktop `position: sticky` on the shared `.np-filter-shell--clean` while keeping `height: auto`, `overflow: visible`, and no internal vertical scroll for expanded desktop groups.
- Added a small `ResizeObserver`-based sticky offset: a compact filter stays near the header (8rem); when expanded beyond the viewport height, the top offset becomes negative to let the page scroll through the entire list, with the panel bottom reaching the viewport. No nested scroll area is introduced.
- Mobile filter drawer behavior is unchanged; its single accessible scrolling container remains intact.
- Updated the distributed Vite CSS/JS assets and manifest so replacing the project works without rebuilding dependencies. A future `npm run build` will produce equivalent behavior from source files.
- Updated affected source-only regression assertions for the restored sticky contract, while preserving existing filter search labels.

## Scope

No changes to Blade filter markup, database queries, product filtering, payments, or order logic.

## Verification

- `php tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php`
- `php tests/Regression/CatalogFilterNaturalExpansionTest.php`
- `php tests/Regression/AllProductsFilterCleanLayoutTest.php`
- `php tests/Regression/AllProductsFilterAutoApplyAndStableRailTest.php`
- `php tests/Regression/AllProductsFilterStableWidthAndFunctionTest.php`
- `node --check resources/js/storefront.js`
- `node --check public/build/assets/storefront-Sticky-359666bc4a.js`

Each command passed locally. Browser-level visual QA remains required on the running NextPlay app, especially when a very long filter is expanded.
