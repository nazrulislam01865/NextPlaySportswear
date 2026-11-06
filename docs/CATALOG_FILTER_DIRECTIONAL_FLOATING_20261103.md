# NextPlay desktop filter — sticky, without nested scrolling

## Why the previous release disabled sticky behavior

The previous release used `position: relative` whenever the sidebar exceeded the visible viewport height. On many catalog pages even the closed filter was slightly taller than that height, so the sidebar lost sticky positioning entirely.

## Corrected behavior

- On desktop, the filter rail **always** has `position: sticky`, including tall filters.
- On page scroll, the rail's top offset changes gradually between the normal header offset and a bottom-aligned offset. Short rails stay below the header. A taller rail scrolls naturally *within the page*, anchors with its bottom accessible, and reveals upper content as the customer scrolls upward.
- Opening a category does not immediately set a huge negative top offset or replace sticky with relative positioning.
- The Filters heading is part of the rail and **not independently sticky**; it cannot overlay category rows.
- Filter lists have no desktop nested scrolling or max-height. Mobile retains its existing scrolling drawer.
- When products are shorter than a tall filter, the grid gets enough normal-page height to make the filter's bottom reachable.
- The shared implementation covers All Products, Sale, and Category listing pages.

## Scope and verification

Source: `resources/css/storefront.css` and `resources/js/storefront.js`.
Compiled assets: new fingerprinted CSS/JS and `public/build/manifest.json`.
Tests: `tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php`, `tests/Regression/CatalogFilterNaturalExpansionTest.php`, `tests/frontend/catalog-filter-sticky-natural-height.test.mjs`.

Chromium static-page layout QA checked closed, expanded, downward and upward scroll, mobile breakpoint, and short product results. The live Laravel site must still be visually checked with real filter data.

There are no migrations, backend query changes, payment changes, or storefront redesigns.
