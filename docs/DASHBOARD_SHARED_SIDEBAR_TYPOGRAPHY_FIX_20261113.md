# NextPlay Admin Dashboard – Sidebar and Typography Correction

## Root cause
The previous dashboard iteration injected a second dashboard-only sidebar and wrapped the original permission-aware admin menu in an `All admin tools` disclosure. It also imposed a dashboard-specific sidebar width, background, and footer. Dashboard text included 8–10px values not aligned with the supplied NextPlay typography template.

## Corrections
- Reinstated the **single original shared admin sidebar** on the dashboard. Its items, permissions, scroll state, resized width, and original Manage Store/Sign out footer are unchanged from the other admin pages.
- Removed the dashboard-only duplicate navigation markup, temporary `All admin tools` disclosure, special sidebar footer, and special sidebar CSS. Deleted the now-unused `resources/views/components/admin/dashboard-navigation.blade.php` component from the full release.
- Aligned dashboard typography to the project's existing `--nps-*` semantic design tokens and Expose font-family (`resources/css/storefront-theme.css`) without introducing another font family, CSS design system, or font files.
- Improved overly tiny dashboard labels, action/control sizing, card overflow, and small-screen arrangement; dashboard data, events and actions are unchanged.
- Added a display-only fallback when an uploaded sidebar footer logo URL fails (try configured website logo, then existing `/images/logo.png`); no database or storage reads were added.
- Bumped the dashboard CSS URL cache version to `20261113b` to avoid stale styles.

## Files changed
1. `resources/views/components/layouts/admin.blade.php` – restore existing full sidebar for every admin route and bump dashboard stylesheet version.
2. `public/css/nextplay-dashboard-prototype.css` – remove sidebar overrides and align dashboard typography to existing tokens.
3. `resources/views/components/admin/brand-identity.blade.php` – logo loading fallback only, preserving configured footer/main logo preference.
4. `tests/Feature/Admin/NextPlayDashboardSharedSidebarTest.php` – new Laravel feature regression test.
5. `tests/Regression/NextPlayDashboardDesignSystemStaticCheck.php` – new dependency-free static regression test.
6. `resources/views/components/admin/dashboard-navigation.blade.php` – removed from the full project (no longer referenced).

## Validation performed
- Dependency-free static sidebar/typography regression checks: **14 passed**.
- CSS syntax: **260 rules, 0 parse errors** (tinycss2).
- Existing dashboard JavaScript `node --check`: passed.
- PHP syntax on new tests: passed.
- Laravel feature tests: not executed, because the supplied archive has no `vendor/autoload.php`.
- Pixel-identical rendering: not verified; current screenshot is from the previous dashboard build.

## Run in your installed local NextPlay project
```bash
php artisan optimize:clear
php tests/Regression/NextPlayDashboardDesignSystemStaticCheck.php
php artisan test --filter=NextPlayDashboardSharedSidebarTest
php artisan test --filter=NextPlayPrototypeDashboardTest
php artisan test --filter=StorefrontBrandingControlTest
php artisan view:cache
```

**Important font prerequisite:** The project already declares Expose in `resources/css/storefront-theme.css`, but the uploaded archive has no files under `public/fonts/expose`. If Expose is not installed in your running project, use the existing `deploy/install-expose-fonts.sh` with your licensed original `Expose_Complete.zip`. These font files are intentionally not included in this release. Confirm that `/fonts/expose/Expose-Regular.woff2` responds with HTTP 200 on your server. Otherwise, the documented Segoe UI fallback will display instead.

## Applying changed-files-only ZIP
Merge the files into your current project directory. Optional cleanup: delete `resources/views/components/admin/dashboard-navigation.blade.php` if it remains from the old dashboard build; nothing references it. Do not replace other admin page templates, edit queries, or run migrations.
