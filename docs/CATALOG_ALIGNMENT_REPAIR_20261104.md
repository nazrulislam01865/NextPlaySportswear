# NextPlay Admin Catalog alignment repair — revision 2 (2026-11-04)

## Why revision 1 failed

The previous `public/css/nextplay-admin-alignment-fixes-20261104b.css` applied 4.25rem / 68px controls and 1rem / 16px radii broadly to the entire admin, overriding the design-system's 48px / 8px rules. It also assigned minimum widths and flex-wrap rules to grouped controls, resulting in separated Apply buttons and overflow inside three-column icon-upload cards. Search fields suffered nested borders because both their outer shells and native inputs were styled as standalone fields.

## Repairs

- Completely replaced the broad alignment stylesheet with scoped selectors, each limited to `body.np-catalog-system #admin-main-content`.
- Product filters: predictable first row plus actions row; 48px control height, 8px radius; no 68px/global button override.
- Product bulk actions: both Selected products and Production methods use the same three-column label/select/Apply grid on desktop; responsive stacking only at smaller widths.
- Production-method filter option text shortened without changing option values or filtering behavior.
- Product Detail Controls: keep 52px icon previews; let upload button and filename wrap inside the available card width; help text stays below.
- Product Highlights: maintain aligned icon preview, upload control and explanatory text.
- Media search and Navigation Menu search: one 1px outer border, no inner input border, search icon aligned to field text, focus outline on the whole control.
- All button and input dimensions follow the existing Catalog design-system's 48px/8px rules. No database, route, controller or product logic changed.

## Files

- `public/css/nextplay-admin-alignment-fixes-20261104b.css` — replaced with targeted repair CSS.
- `resources/views/components/layouts/admin.blade.php` — cache bust version updated to `v=2`.
- `resources/views/admin/products/index.blade.php` — display-only production filter label shortening.
- `resources/views/admin/menus/_form.blade.php` — supplied to preserve the search-shell markup needed by the repair.

## Integration

Use the **changed-files** archive on a project already running revision 1, maintaining folder paths. Use the **full-project** archive otherwise. Run `php artisan optimize:clear`, then hard-refresh the browser. No Vite build is required because the repair is a public CSS asset and the Blade source references that asset directly.

## Verification

- The replacement CSS passed `tinycss2` parsing with zero parser errors.
- PHP lint passed for edited Blade source (note this does not compile Blade directives).
- Source-level check found no remaining 4.25rem or 12.25rem rules in the replacement stylesheet and no unscoped top-level style selectors.
- A Chromium browser smoke test could not start successfully in the current sandbox. The visual presentation and authenticated Laravel workflows **must be checked locally** at desktop, tablet and mobile widths before production deployment.
