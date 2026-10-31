# NextPlay — Complete My Account typography alignment (31 October 2026)

## Source of truth

The uploaded `NextPlay_Typography_Design_System (1) (1)(3).html` is the reference. The existing centralized `resources/css/storefront-theme.css` already contains the matching `--nps-*` tokens. This change reuses the tokens in `resources/css/storefront-typography.css` and regenerates `public/css/storefront-typography.css` via `deploy/build-storefront-typography.py`.

## What was corrected from the previous update

The previous account navigation used 14px/40px instead of the template's 15px/44px menu rule. Account pages also contained 900-weight `font-black` utilities, inconsistent 11/12px labels, and artwork/quote actions with typography smaller than the approved button scale. These visual exceptions are now mapped to semantic template roles **within the shared account frame**.

| Role | Template value | Scope |
| --- | --- | --- |
| Font | Expose, Segoe UI fallback | All customer account pages |
| Page H1 | 40px desktop / 32px mobile, 700 | Hero or content-only title |
| H2 | 32px desktop / 26px mobile, 700 | Page sections |
| H3 | 24px desktop / 22px mobile, 700 | Subsections and metric values |
| H4 | 20px, 700 | Compact panel headings |
| Body | 16px, 400 / 1.6 | Paragraphs, descriptions |
| Lead | 18px desktop / 16px mobile | Supporting hero copy |
| Menu/tabs | 15px, 500; minimum 44px target | Account sidebar, quote and rewards tabs |
| Button | 16px, 500; minimum 48px target | Form / proof / quote actions |
| Label / small | 14px, 500 for labels | Forms, card details |
| Caption | 13px, 400 | Dates, metadata, helper copy |
| Colors | `--nps-*` semantic tokens | Headings, body, link, muted text, focus |

The existing dark navy selected sidebar row with **white** text was intentionally retained for contrast and consistency with the existing NextPlay UI; the reference's orange underline is used for appropriate tab navigation. Existing red/green/orange status chips keep their semantic state colors. The sidebar Logout control remains compact as a utility rather than a full-page CTA.

## Pages covered

All **31** standalone Blade customer account views use the shared account page/shell/frame, including dashboard, profile/security, order center, history, shipment tracking, downloads, invoices, payments, returns and exchanges, refunds, addresses, artwork approvals, bulk quotes, rewards and referrals. The account unavailable page is separately scoped. A reused order payment partial is intentionally not counted as a standalone page.

## Protected areas

The following are not modified: canonical product card, catalog filtering UI (desktop/mobile), header menus, original `resources/css/storefront.css`, business logic, order queries, routes, authentication, payment, artwork operations, database schema and migrations.

## Files updated

- `resources/css/storefront-typography.css` — centralized scoped account styles
- `public/css/storefront-typography.css` — regenerated static asset; no new Vite compilation
- `resources/views/components/layouts/storefront.blade.php` — stylesheet URL version to invalidate browser cache
- `tests/Regression/NextPlayProductsAccountTypographyTest.php` — remove obsolete cache-version expectation
- `tests/Support/nextplay_account_template_static_check.php` — source-level regression and 31-page coverage
- This guide

## Verification performed

- `php tests/Support/storefront_design_system_static_check.php all` — pass
- `php tests/Support/nextplay_account_template_static_check.php` — pass (31 view wrappers)
- `php tests/Regression/AccountSharedFrameAndAdminCompactButtonsTest.php` — pass
- `php tests/Regression/AccountDashboardOrderHistoryPrototypeTest.php` — pass
- PHP syntax checks — pass
- Published stylesheet exact-content regeneration check — pass
- CSS parser top-level syntax — pass
- SHA-256 comparison of protected source files against uploaded project — pass
- **Not verified:** browser rendering with a fully functioning authenticated Laravel site and PHPUnit integration tests. Headless browser attempt did not complete within the environment; these checks must be completed in the running application.

## Deployment

Deploy changed files or the full project. No database migration and no Vite build are required for this isolated static-CSS change. Run:

```sh
php artisan view:clear
```

Then hard-refresh, test each customer account menu entry in desktop and mobile view, and verify Expose WOFF2 originals are installed under the expected server-side `public/fonts/expose/` paths. Font binaries are intentionally not included in the delivered archives.
