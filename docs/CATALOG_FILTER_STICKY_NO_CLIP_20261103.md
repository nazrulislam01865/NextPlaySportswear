# NextPlay catalog filter: no-clipping correction (3 Nov 2026)

## Root cause

The prior sticky implementation updated a negative `top` offset on **every** page-scroll event, including scrolls over product cards. For a sidebar taller than the viewport, it therefore shifted the filter heading and upper categories above the visible window. Increasing the grid height did not fix that behavior.

## Correct behavior

- **Compact desktop filters** (the full rail fits between the navigation and bottom edge) retain the familiar sticky/floating sidebar at `top: 8rem`.
- **Long or expanded desktop filters** cannot be shown in their entirety within one viewport. They now expand to their natural height and use the normal **document scrollbar**, while the Filters heading remains sticky. Their first and last options are reachable by scrolling the page up or down; none are hidden by a negative sticky offset.
- Opening a group that switches the sidebar from compact to long keeps the clicked summary approximately in place, avoiding a sudden jump.
- Closing groups re-evaluates the height and restores full sticky behavior when the content fits again.
- Mobile keeps the existing filter drawer's own viewport scrolling behavior.
- Sidebar width, fields, fonts, product grid, filter requests, price calculations, and backend are unchanged.

**Why the layout switches:** a rail taller than the viewport cannot keep all of its options visible and remain completely pinned without either an internal scrollbar or clipping. The safe solution is to keep the heading floating while the long filter itself uses ordinary page scrolling.

## Source and assets

- `resources/js/storefront.js`: replaces the scroll-direction-driven negative offsets with a viewport-fit class and click anchor compensation.
- `resources/css/storefront.css`: sticky for compact rails, naturally flowing long rails, sticky heading, no nested desktop overflow.
- `public/build/manifest.json` and the two `storefront-FilterNoClip-*.{js,css}` assets: provide the same fix without a Vite rebuild in this archive.
- `tests/frontend/catalog-filter-sticky-natural-height.test.mjs`: checks state transitions and source/compiled parity.
- `tests/Regression/CatalogFilterStickyWithoutNestedScrollTest.php` and `CatalogFilterNaturalExpansionTest.php`: update previous regression checks to the new, unclipped behavior.
- `tests/frontend/filter-no-clip-browser-qa.py`: optional Playwright/Chromium visual-layout QA using the shipped assets.

## Verification

- Node unit regressions: 4 passed.
- PHP standalone regressions: natural expansion, sticky/no nested scrollbar, stable width, automatic filter submission: all passed.
- Chromium fixture (1745x860) with actual compiled stylesheet and initializer: compact rail sticky; long heading sticks; no nested desktop scrollbar; expanded option and Clear button can be reached through document scrolling; compact-to-long click retained its row's position to within 4px; mobile class removed.

The browser fixture is not a live Laravel visit. After applying changed files, run `php artisan optimize:clear`, hard refresh the storefront and verify All Products, Sale, and Category pages. The Vite manifest is included and a rebuild is not required to see the supplied fix. No database migration is needed.
