# NextPlay guest bulk quotation: compact pages and account-based order conversion

## Scope (October 20, 2026)

- Reduced typography and vertical density on the **four existing guest bulk quotation screens** (submitted, tracking, review, changes). The provided card/grid designs and colors, global navigation/footer, shared button styles, and the registered-user checkout flow were not redesigned.
- Guest may submit, track, review, request changes, and **confirm quotation only** without an account.
- **Create Order requires an active NextPlay customer account.** Guests see Register and Sign in links, each returning to the same review page after authentication. They then explicitly click Confirm & Create Order; logging in or visiting a GET page never creates an order.
- The account email must match the quote email (case-insensitive). When the application's email-verification setting is enabled, the account must be verified.
- The new order's `orders.user_id` and its originating `bulk_quote_requests.user_id` are both set to the account ID inside the existing row-locked transaction. Orders appear in **My Account > Orders**. Existing quoted price, manual-invoice payment-pending state, and admin quotation flow are preserved.
- If an order was created by the previous guest-only implementation, the matching customer may use **Link Order to Account** after signing in. No second order is created. Historical orders are not silently linked during deployment.
- After linking, the quote pages/files only open for the authenticated owner. Prior guest tokens and anonymous quote sessions cannot access the converted quote.
- Existing duplicate-order protection, quote status validation, CSRF, throttling, private file storage, and local-only CAPTCHA behavior are unchanged.

## Modified project files

- `app/Http/Controllers/Storefront/GuestBulkQuoteController.php`
- `app/Services/BulkQuote/BulkQuoteGuestService.php`
- `resources/views/storefront/bulk-quote/guest/review.blade.php`
- `resources/css/storefront.css` (guest-only scoped styles)
- `public/build/manifest.json` and new fingerprinted storefront CSS asset (previous asset retained for cached clients)
- `tests/Feature/Storefront/GuestBulkQuoteJourneyTest.php`

No new database schema, global theme, header, footer, or route change is required.

## Local verification

```bash
php artisan optimize:clear
php artisan view:clear
php artisan test --filter=GuestBulkQuoteJourneyTest
```

Test manually:

1. Submit a bulk quote as guest (CAPTCHA bypass only in local environment), track and review it.
2. On quotation review, **Confirm Quotation Only** must work without registration and must not create an order.
3. Select **Create Account to Order** / **Already registered? Sign in**. Log in or register using the same quote email; you return to quotation review.
4. Click **Confirm & Create Order**. Check the success message and **View Order in My Account**. Confirm the quote and order have the same `user_id`, the order has payment `pending`, and there is exactly one order after retrying.
5. Repeat with a different customer email; conversion must be blocked with a readable error.
6. If email verification is enabled, confirm that an unverified customer must verify first.
7. Check a prior accepted guest quote with an existing guest order: the **Link Order to Account** path must attach the existing order without duplication.
8. Confirm the four guest pages look compact on desktop, tablet, and mobile, while the NextPlay header/footer remain unchanged.
9. Verify a signed-out visitor cannot reopen an account-linked quote using an old guest token.

## Deployment safeguards

Back up live application/database first. Preserve the existing server `.env`, database, uploaded files and private quote documents. Update project files, ensure Composer dependencies are present, run the test suite and rebuild assets as needed in staging before production rollout. Do not set `APP_ENV=local` on production.

Source PHP syntax checks and CSS parse checks passed when packaged. Full Laravel feature tests and browser-level visual comparisons could **not** run in the packaging environment because `vendor` and a runnable Laravel test environment were not included in the user-provided archive; these checks remain necessary before deployment.
