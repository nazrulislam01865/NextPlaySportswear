# NextPlay — All Products, Sale Products and Customer My Account typography

**Scope:** Frontend typography, visual button styling and responsive text only. No database migrations, API/controller changes, order/customer business logic or UI content removals.

## Reference

The provided NextPlay Typography Design System v1.1 is the source of typography/brand values. All rules reuse the existing centralized variables defined in `resources/css/storefront-theme.css`, principally `--nps-*` and `--np-color-*`. Primary font: self-hosted Expose (regular 400, medium 500, bold 700); fallback: Segoe UI, sans-serif. There are no new font binaries in the updated package.

## Coverage

- **All Products (`/products`)** — existing category design-system titles retained, shared catalog typography namespace applied, pagination navigation uses template tokens.
- **Sale Products (`/sale`)** — editorial hero H1, lead text, eyebrow, results count and desktop/mobile section headings use the matching typography tokens. The copy and sale/category data are unchanged.
- **All customer My Account screens (`/account/...`)** — shared `components/storefront/account/frame.blade.php` now opts every page into the centralized account system. This covers dashboard, order tracking/history/detail and post-purchase actions, returns/exchanges/refunds, rewards/referrals, bulk quotation center, artwork approvals, profile, addresses and payment methods. `account/section.blade.php` (rare unavailable-page fallback) is scoped as well.

## Token application

| Element | Token / style |
| --- | --- |
| Page H1 | `--nps-h1` (40px, 700); mobile `--nps-h1-mobile` (32px) |
| Section H2 | `--nps-h2` (32px, 700); mobile `--nps-h2-mobile` (26px) |
| H3 | `--nps-h3` (24px, 700); mobile `--nps-h3-mobile` (22px) |
| H4 / H5 / H6 | `--nps-h4` / `--nps-h5` / `--nps-h6` |
| Body, descriptions | `--nps-body` (16px, 400), line height 1.6 |
| Lead description | `--nps-lead` (18px); mobile 16px |
| Menu and text links | `--nps-menu` (15px, 500) |
| Form labels | `--nps-small` (14px, 500) |
| Captions and support hints | `--nps-caption` (13px) |
| Buttons | `--nps-button-size` (16px, 500), `--nps-control` (48px), `--nps-radius` (8px) |
| Colors | Brand and semantic color tokens (`--nps-navy`, `--nps-text`, `--nps-muted`, `--nps-link`, etc.) |

The existing account layout/spacing, feature content, status indicators, alerts, permissions, validation, and backend actions are not changed. Checkbox/radio/file input sizes, status-pill colors, and inverse text on dark panels are preserved.

## Explicit no-touch components

The following are identical to the uploaded project archive (checked byte-for-byte):

- `resources/views/components/storefront/product-card.blade.php`
- `resources/views/components/storefront/product/category-filter-panel.blade.php`
- `resources/views/components/storefront/catalog/shared-filter-sections.blade.php`
- `resources/views/storefront/products/_results.blade.php`
- `resources/views/storefront/products/_sale-results.blade.php`
- `resources/css/storefront.css`
- `resources/css/storefront-theme.css`

The new catalog rules do **not** style filter panel, mobile drawer, sort controls, active-filter bar or product cards.

## Deployment

`public/css/storefront-typography.css` is rebuilt from the two centralized source CSS files via `python deploy/build-storefront-typography.py`. Its cache version is bumped in the storefront layout. The compiled Vite build stays in place; no `npm run build` and no database migration are necessary for the static CSS update.

After uploading, run `php artisan view:clear`. Preserve the existing server's Expose font files at `public/fonts/expose/`; if missing, install them separately from a legitimately obtained Expose archive via `bash deploy/install-expose-fonts.sh /path/to/Expose_Complete.zip`.

## Verification

- Centralized design-system PHP static check: PASS.
- New source CSS and published CSS: parsed cleanly.
- Protected components, product-result includes, and existing CSS: byte-for-byte unchanged from Archive 4.
- PHP test file syntax: PASS.
- Complete Laravel integration suite: **not run** (the uploaded archive does not contain `vendor`).
- Browser-level account/sale rendering: **not verified** (headless-browser smoke check could not finish in this environment). Confirm visually on local/staging at both mobile and desktop widths before deploying to production.
