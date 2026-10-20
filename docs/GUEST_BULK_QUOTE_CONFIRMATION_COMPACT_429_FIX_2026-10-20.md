# NextPlay Guest Bulk Quotation - Compact Buttons and 429 Fix (20 Oct 2026)

## Root causes
- Guest quote decision route used `throttle:5,1`, a shared five-POST-per-minute/IP restriction; repeated confirmations and shared networks surfaced Laravel's 429 "Too Many Requests" page.
- The decision buttons were oversized relative to the existing compact NextPlay UI. They also did not opt in to the storefront's existing single-submit protection.

## Updates (scoped to guest quote decisions)
- Named limiter `guest-quote-decision`: **12 attempts per minute per quote/IP**, with a separate **60 per hour per IP** cap to prevent cycling quote references. This does **not** remove security throttling, session authorization, CSRF checks or server-side duplicate-order prevention.
- Limit violations redirect back with a readable inline error; if a quote is already confirmed, users can refresh the review page to see its state.
- Confirmation forms use existing `data-single-submit` storefront JavaScript to prevent fast repeat taps. No new JS is needed.
- Sequential replayed confirmations no longer trigger redundant admin feedback notifications. Existing order creation is idempotent.
- Quotation review Next Step buttons only: label 14px (13px small screens), supporting caption 11px (10.5px small screens), icons 18px, minimum height 50px, reduced padding. Global buttons are not changed.
- Updated source CSS and the compiled, hash-versioned CSS asset/manifest are included so the design displays without a Vite rebuild when using the provided assets.

## Local deployment
1. Back up your current project, `.env`, uploads and database before replacing the source. The ZIP omits runtime `.env`, `vendor/`, `node_modules/`, and logs.
2. Use your current `.env`. Keep `APP_ENV=local` for local CAPTCHA bypass, **not** for a production server.
3. If dependencies are not already installed, run `composer install` and `npm ci` as appropriate. Compiled CSS and JS files are already included.
4. Run `php artisan optimize:clear` and `php artisan view:clear`.
5. Run `php artisan test --filter=GuestBulkQuoteJourneyTest` from the project root.
6. Test quote review > Confirm Quotation Only; then create order. For another quote, test Confirm & Create Order directly. Verify exactly one order and no duplicated messages; after repeated rapid retries, the user should see a helpful inline warning instead of a raw 429 page.
7. Refresh the browser. Compiled asset name has changed to avoid stale CSS.

## Verification and limitations
- PHP `-l`: all 725 application/routes/database/tests PHP files passed.
- Route, Blade form values, compiled manifest and CSS brace balance verified.
- Laravel feature tests were added but cannot be executed in this packaging environment because `vendor/autoload.php` is absent. Run those tests in the actual local Laravel setup before production deployment.
- No database migration, admin change, authentication relaxation or global theme change in this patch.
