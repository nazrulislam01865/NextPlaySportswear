# NextPlay catalog filter — measured sticky correction (2026-11-03)

## Why prior releases clipped the filter

The desktop filter contained competing `position` overrides in its shipped Vite CSS. In addition, the `np-filter-rail--long` rule made only the **Filters** heading sticky. As the user scrolled product results, the heading overlaid category rows and made them look cropped.

## Single source of behavior

- Default: **normal document flow** (safe without JavaScript).
- Desktop rail proven shorter than the available screen: `np-filter-rail--compact`, `position: sticky; top: 8rem`.
- Desktop rail taller than the screen: `np-filter-rail--long`, normal relative positioning. **The heading also scrolls normally, not over the options.**
- Expanded lists use page scrolling, not an internal desktop scrollbar; the existing mobile drawer remains unchanged.
- Existing summary-click scroll anchoring is retained to avoid abrupt jumps when a short rail expands into a tall rail.
- No product-search, filtering, price, payment, or database behavior was changed.

## Shipped assets

`public/build/manifest.json` points to new fingerprinted JS/CSS filenames. The new CSS bundle has **one** authoritative override block rather than the prior three contradictory override blocks. The unmodified older bundles are left in place for URLs already in browser caches.

## Checks run

- `node --check` on source JS and the shipped JS bundle.
- Four Node unit/asset consistency tests.
- Four standalone PHP filter regression checks.
- Chromium test using *the shipped CSS and script*, including compact sticky, expanding and clicked-row position, tall natural scroll, no heading overlap, mobile breakpoints, and all sections expanded with short product results.
- ZIP integrity and changed-file inventory.

These are isolated frontend tests, **not** a claim that the entire Laravel site and all live catalog datasets have passed visual QA.

## Apply

Use the changed-files ZIP on the previous full project. Or replace with the full project ZIP. Afterward run `php artisan optimize:clear`, then hard-refresh (Cmd+Shift+R). No migration or Vite rebuild is required for the packaged assets. A later Vite rebuild will use the corrected source styles and script.
