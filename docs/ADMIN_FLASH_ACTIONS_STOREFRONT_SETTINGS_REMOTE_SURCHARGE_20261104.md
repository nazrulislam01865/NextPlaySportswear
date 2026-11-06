# NextPlay admin UX corrections and remote-area surcharge audit

Date: 2026-11-04. Scope: uploaded `Archive(20261104-052412).zip`.

## Implemented fixes

1. **Admin flash messages:** The shared admin status banner and the existing page-level status/error flash banners are tagged with `data-admin-flash`. A single vanilla-JavaScript handler adds an accessible close button and removes success notices after 4.5 seconds (flashed errors after 7 seconds). Form validation error summaries are not auto-removed. Both normal navigation and the optional soft-navigation event are supported. The same behavior works on the administrator login screen.
2. **Personalization template actions:** The three-dot control is a labeled accessible button. The menu is portalled outside the horizontally scrollable table while open, positioned against the viewport, flipped above the trigger when space is limited, and closed on Escape/outside click. Position updates on scroll and resize. The action column remains sticky to the right on narrow screens; edit, duplicate, configure fields, preview, and conditional delete routes/forms are retained.
3. **Storefront Settings:** About Page, Shipping & Delivery, Size Guide, Artwork Guidelines, How to Order, Returns & Refunds, Contact Us, Terms & Conditions, Payment Information, Privacy Policy, Sustainability, Home Page Control, and Newsletter Emails are now under **Store → Storefront Settings**. Each remains gated by its original admin permission; the Home Page Control nested editor links are retained. Branding, Bulk Quote Icons, Email Customization, Remote Surcharges, and Payment Methods remain separate, as they were not among the screenshot's requested page group.
4. **Deployment:** The shared fix is loaded from versioned `public/css/nextplay-admin-ui-fixes-20261104.css` and `public/js/nextplay-admin-ui-fixes-20261104.js`, so it works with the existing `public/build` assets. No npm build is required solely for these changes. Future builds can continue using the normal Vite assets.

## Current remote-area surcharge calculation (analyzed, not changed)

**The remote ZIP dataset is a qualification check, not the checkout fee source.**

1. The checkout gathers its main shipping address plus per-product shipment addresses from the checkout state. It skips duplicate `(postal_code, country, state, city)` combinations, checks addresses in order, and stops as soon as any address qualifies as remote (`CheckoutService::ruralSurchargeForState`).
2. `RuralAreaSurchargeService::resolve` normalizes postal code and city. It first queries active structured UPS rows, giving exact city matches priority, then numeric inclusive low/high ranges, then alphanumeric prefix/range comparisons. When structured lookup fails it falls back to manually maintained country/state postal patterns. Destination rows explicitly marked `No` are excluded from the structured queries.
3. Independently, `OrderShippingCharges::forCart` adds product quantities to get **one combined order quantity**. For each configured product price table it selects the remote-area column's value at that combined quantity tier, then uses **the maximum configured remote column amount** across the cart. It does not add one remote fee per item or per product.
4. If an address is remote, `CheckoutService::checkoutSummary` uses that table-derived remote rate once; otherwise remote fee is zero. The remote database row's own `extra_charge` is **not** the checkout amount; checkout replaces it with the price-table amount and marks the source as `product_price_table`.
5. Shipping total is `configured product shipping + automatic shipping fallback (when applicable) + one remote surcharge`. The order grand total includes that shipping amount once. On placement, `orders.rural_surcharge_total`, `orders.is_remote_area`, and `orders.shipping_total` are stored. If a destination is remote but no remote price-table column/value can be resolved for the quantity, order placement is blocked rather than using a guessed fee.

Example: Three line items with combined quantity 120; configured remote rates for that tier are $18, $22, and $22. If any of the order's shipping destinations matches the remote dataset, the additional remote amount is **$22 total once**, not $62 and not $22 per piece.

## Lookup complexity and practical scalability

Let `N` be active remote rules, `A` relevant alphanumeric rules for a country, `L` manual/legacy rules for the filtered country/state, `U` unique checked delivery addresses, `P` product lines, `H` price-table columns, and `T` quantity tiers. Let `s` be maximum postal-pattern string length.

| Stage | Time complexity | Additional space | Important qualification |
|---|---|---|---|
| Normalize one postal/city input | O(s) | O(s) | Input strings are short. |
| Indexed city match (ideal selective index) | ~O(log N) | O(1) PHP | Existing indexes do **not** guarantee this plan; SQL may scan depending on predicates and index order. |
| Numeric postal range lookup | Potential O(N log N) worst case (candidate scan + expression sort) | O(1) PHP result; up to O(N) DB sort/workspace | `low <= postal AND high >= postal` plus `ORDER BY (high-low)` may inspect many rows and sort. The current index is not a guaranteed logarithmic interval index. |
| Alphanumeric fallback | O(A log A + A·s) including DB sort/match | O(A) PHP, up to O(A) DB | Uses `get()` to load all relevant alphanumeric rules for matching. |
| Manual legacy-pattern fallback | O(L log L + L·s) including sort/matching | O(L) PHP, up to O(L) DB | Uses `get()` and then loops through patterns. |
| Remote fee extraction from cart price tables | O(P·(H+T)) typical | O(T) temporary per tier evaluation plus O(P) cart/output | Searches each product's remote column and its applicable quantity tier; exact work depends on tier parsing. |
| Address loop | Up to U resolver calls | O(U) for seen-address keys | Stops at first positive match. No persistent lookup cache in this service. |

**Bottom line:** It would be inaccurate to claim the entire remote detection is O(log N) or O(1). The numeric database path uses indexes where the optimizer can, but current range comparisons and computed sorting can be scan/sort-heavy. The alphanumeric and legacy paths explicitly load matching candidate records into PHP memory. The checkout fee *aggregation* is comparatively cheap, linear in cart-line/price-table sizes.

**Future performance improvements (not applied to avoid changing surcharge behavior):** collect representative `EXPLAIN ANALYZE` plans for common countries/postcodes; index country/active/type and actual lookup keys with matching predicate order; avoid computed per-request range sorting with a precomputed range width/priority; replace unbounded `get()` fallback with bounded country-specific lookup structure; cache normalized lookup results with clear invalidation on UPS imports/manual edits. Overlapping ranges require regression tests so the current narrowest-range/highest-charge precedence remains identical.

## Files and checks

- `resources/views/components/layouts/admin.blade.php`: shared banner and sidebar group; versioned asset links.
- `resources/views/admin/personalization-templates/index.blade.php`: accessible floating action button and menu.
- `resources/views/admin/...`: status/error flash markers on the existing admin pages.
- `resources/views/admin/auth/login.blade.php`: login flash support and shared assets.
- `public/js/nextplay-admin-ui-fixes-20261104.js`: shared dismissal and menu behavior.
- `public/css/nextplay-admin-ui-fixes-20261104.css`: shared notice and action presentation, sticky column.
- `tests/frontend/admin-flash-floating-actions.test.mjs`: source-level regression assertions.

Checks run: `node --check` on existing admin JS and new standalone JS; `node --test tests/frontend/admin-flash-floating-actions.test.mjs` (3 passing); PHP syntax on surcharge service and shipping calculator. Live Laravel/browser integration was **not** tested because the uploaded project does not contain installed Composer dependencies and the headless browser smoke attempt did not finish in this environment. No database migrations or surcharge logic have been altered.

After replacing the project files, clear the application caches with `php artisan optimize:clear`; if using an opcode cache/CDN, invalidate the relevant caches. Because these changes use versioned public assets, `npm run build` is not required for them, although it remains appropriate for other Vite source changes.
