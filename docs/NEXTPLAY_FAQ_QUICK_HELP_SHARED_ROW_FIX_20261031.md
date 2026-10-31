# NextPlay homepage FAQ / Quick Help alignment — 31 October 2026

## Problem

When Quick Help's optional description was removed in the admin, its first card moved upward relative to the first Common Questions accordion. The two independent `<header class="section-head">` blocks had different bottom margins (the FAQ defaults to 34px while Quick Help defaults to 16px). The earlier nested-subgrid CSS workaround did not reliably enforce a single heading/content track in the user's rendered page.

## Structural fix

The FAQ component now renders **both section headings as direct children of the same parent CSS grid**. FAQ accordion content and the reusable Quick Help aside are also direct siblings, occupying the second row. This makes the beginning of each content group identical by grid geometry, regardless of whether one, both, or neither heading has an optional description.

Desktop: `grid-template-columns: minmax(0, 1fr) minmax(0, 436px)` with shared `grid-template-rows: max-content auto`. The existing FAQ 94% width and Quick Help 436px maximum width are retained. On mobile, explicit row ordering retains FAQ heading/content, followed by Quick Help heading/content. When Quick Help is disabled, the original single-column FAQ mode remains.

The reusable `quick-help` component keeps its heading by default for other potential callers; the homepage opts out of the duplicate heading with `:show-heading="false"`. Its accessible `aria-labelledby` reference still resolves to the shared heading in the parent.

## Files changed

- `resources/views/components/storefront/home/faq.blade.php`
- `resources/views/components/storefront/home/quick-help.blade.php`
- `resources/css/storefront-typography.css` (centralized source)
- `public/css/storefront-typography.css` (built public asset)
- `resources/views/components/layouts/storefront.blade.php` (cache-busted CSS)
- `tests/Regression/EmailVerificationHomeAlignmentTest.php` (updated existing layout assertions)
- `tests/Regression/HomeFaqQuickHelpStructuralAlignmentTest.php` (new targeted regression)
- This documentation

No JavaScript, account verification, admin persistence, routes, business logic, product cards, filters, input fields, icons, card content, or database schema were modified.

## Verification

Executed without Composer dependencies:

```bash
php tests/Regression/HomeFaqQuickHelpStructuralAlignmentTest.php
php tests/Regression/EmailVerificationHomeAlignmentTest.php
php tests/Regression/HomeQuickHelpAdminAndLayoutTest.php
php tests/Support/storefront_design_system_static_check.php all
```

All passed. The generated public CSS is reproducible with `python3 deploy/build-storefront-typography.py`. The stylesheet URL version was updated to `?v=20261031faqsharedrow2` to bypass stale browser caches.

Browser-level screenshot verification could not be completed in this execution environment because headless Chromium stalled. Verify the first FAQ accordion and first Quick Help card have the same top edge on the live site, both with and without the admin Quick Help description, at desktop and mobile breakpoints.

## Deployment

Deploy all changed files together; the supplied published CSS is already built. No Vite build or database migration is required. Run `php artisan view:clear`, then hard refresh the homepage or purge any CDN cache if one is enabled. The CSS URL's new version should appear in page source.
