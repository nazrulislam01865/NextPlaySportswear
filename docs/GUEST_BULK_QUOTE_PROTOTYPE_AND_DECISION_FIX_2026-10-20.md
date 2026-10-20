# NextPlay guest bulk quote – visual and decision-flow correction (20 Oct 2026)

## Scope
- Full project source is included, not just a patch.
- Only guest bulk quote templates/components, their scoped CSS and compiled CSS bundle, and the guest decision service/controller/test were updated.
- The original NextPlay header, footer, centralized Expose fonts, CSS theme variables, global button system, registered-user flows, other admin features, and production CAPTCHA setup were not changed.

## Visual changes
- Widened and re-spaced the guest pages to follow the four supplied prototypes more closely.
- Restyled quotation review/confirmation, quote lookup and tracking, file lists, badges, progress cards and change-request forms.
- Reused the guest panel/file components and added one small icon component shared by guest pages.
- Updated the packaged, compiled storefront CSS and Vite manifest so a deployment using the included public/build assets shows the new layout even before building again. A normal `npm ci && npm run build` will regenerate it from `resources/css/storefront.css`.
- Exact storefront header/footer remain the current project versions, because changing them would affect unrelated routes.

## Decision handling
- Two valid values on the review form: `accept_only` and `create_order`.
- Confirmation-only saves acceptance with no new order.
- Create-order saves a pending-payment manual-invoice order under the existing order module, and shows its order number on the review page.
- Clicking again cannot create another order for the same quotation.
- Validation rejects expired, withdrawn, revision-pending or incomplete quotations with a visible message.
- Unexpected persistence errors are reported internally and shown as generic, meaningful customer feedback; email-notification errors do not undo committed orders.
- After successful submission the customer returns to quotation review and sees the result.

## Local verification / limitations
- All application and test PHP files pass PHP syntax lint; CSS source and packaged bundle pass CSS parser checks.
- Full Laravel integration/browser tests have **not** been run here: Composer vendor dependencies were not included in the uploaded archive and npm installation could not reach the registry.
- Regression cases have been added to `tests/Feature/Storefront/GuestBulkQuoteJourneyTest.php`, but they must be run in your dev environment before deploying.

## Local commands
```bash
composer install
php artisan optimize:clear
php artisan view:clear
php artisan test --filter=GuestBulkQuoteJourneyTest
# Optional if you want to rebuild CSS/JS instead of using bundled public/build:
npm ci
npm run build
```

## Verify manually (use a disposable test quote)
1. Submit guest quote locally (local CAPTCHA bypass remains as previously configured).
2. Publish quotation from admin (quantity, prices, valid until, files).
3. Open guest quote link, track, review; verify file preview/download.
4. Click Confirm Quotation Only; the review should show confirmed and no order.
5. Click Confirm & Create Order after the previous acceptance; the page should display a new order ID and the admin order list should contain one pending-payment/manual-invoice order.
6. Click Create Order again; no second order should appear.
7. Repeat on another quote, starting with Confirm & Create Order directly.
8. Check expired/revision-pending quote errors and messages, both desktop and mobile layouts.

Do not overwrite your live `.env`, your production database, storage uploads, or installed dependencies with this archive. Deploy to staging and back up before production changes.
