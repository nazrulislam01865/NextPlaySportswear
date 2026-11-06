# NextPlay catalog filter — sticky expansion clipping correction

## Scope

The shared desktop catalog filter sidebar on All Products, Sale Products, and Category Products. No payment, pricing, database, controller, Blade layout, or filter-selection logic is changed.

## Root cause

The previous JavaScript calculated the sticky `top` from the **current total sidebar height** on every `ResizeObserver` notification. When a customer opened a category or attribute group, the new height could immediately produce a large negative `top`. CSS sticky positioning then moved the entire sidebar above the viewport, hiding the search field, categories, and even the first collapsed rows. The natural-height CSS fix was correct; the responsive sticky offset was not.

## Correction

`resources/js/storefront.js` preserves the currently visible sticky offset when a filter group grows. On **actual page scrolling only**, the offset advances with scrolling direction between its normal sticky top and a bottom-aligned limit for long sidebars. This makes the first rows remain visible on opening, allows the rest to be reached by ordinary page scroll, and reverses smoothly as the user scrolls back upward.

- Short filters still stick below the navigation.
- Long and expanded filters use the document scrollbar; no nested desktop filter scrollbars are introduced.
- Expansion does not immediately shift the sidebar upward.
- When an extremely long rail is taller than the products, the surrounding grid reserves one viewport of ordinary document scroll space so CSS sticky still has room to move; this does not change normal product grids.
- Collapse reclamps the sticky offset to the shorter content height and removes excess scroll space.
- Mobile keeps its existing scrolling drawer and never receives a desktop sticky offset.
- Layout and styles are unchanged.

## Files

- `resources/js/storefront.js` — source of truth for the position-preserving sticky algorithm.
- `public/build/assets/storefront-StickySafe-ddf5d70e04.js` — updated compatible prebuilt JS entry for the supplied Vite bundle.
- `public/build/manifest.json` — points Vite to the updated asset. Existing CSS entry remains unchanged.
- `tests/frontend/catalog-filter-sticky-natural-height.test.mjs` — runnable, dependency-free JavaScript behavior tests.
- `tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php` — updates an old assertion that explicitly expected the now-removed faulty sticky offset computation.

## Verification performed

- New Node behavior tests: **4 passed** (`node --test tests/frontend/catalog-filter-sticky-natural-height.test.mjs`).
- Existing PHP layout regression checks: `CatalogFilterStickyWithoutNestedScrollTest.php`, `CatalogFilterNaturalExpansionTest.php`, `AllProductsFilterAutoApplyAndStableRailTest.php`, `AllProductsFilterStableWidthAndFunctionTest.php`: **passed**.
- `node --check` both the source and compiled JS: **passed**.
- Headless Chromium measured the supplied CSS with the new initializer at desktop viewport 1745×860. Expanding a category at scrollY=480 changed the sidebar from 806px to 1,283px tall **without changing its top (38px)**. Scrolling down reached a bottom-aligned position without an inner scrollbar; scrolling up restored the upper rows. Mobile viewport disabled desktop sticky offset.
- Chromium stress check: **14 expanded groups**, 10,646px filter height, no instant sidebar jump, no inner scrollbar, and the Clear action reachable using only page scrolling.
- This is a controlled static HTML/browser fixture, not a live Laravel browser session; verify on your local site before production deployment.

## Installation

Replace changed files preserving their relative paths, or use the complete project ZIP. Run `php artisan optimize:clear`. The updated Vite JS bundle and manifest are supplied, so a frontend build is not required when using these exact assets. If you rebuild with `npm run build`, the source JavaScript contains the same fix.

Check All Products, Sale, and Category pages while scrolling with all filter groups collapsed, one group expanded, and several groups expanded. On desktop there must be **no scrollbar inside the filter**. The page scrollbar is used to reach the lower options; on mobile the drawer retains its own scrolling behavior.
