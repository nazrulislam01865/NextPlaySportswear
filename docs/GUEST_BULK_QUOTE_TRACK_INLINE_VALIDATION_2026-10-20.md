# Guest Bulk Quote - inline tracking form errors

## Scope
Only the guest "Track Bulk Quote" lookup form was changed. The form layout, theme, backend quote lookup rules, quote ownership checks, and all other pages remain unchanged.

## Behaviour
- Browser-native required/email validation bubbles are suppressed for this form only (`novalidate`).
- Submitting with an empty quote number displays "Please enter your quote number." below the quote number field.
- Submitting with an empty email displays "Please enter your email address." below the email field.
- Submitting an invalid email displays "Please enter a valid email address." below the email field.
- Laravel validation errors (including a quote/email mismatch) appear under the applicable input after redirect.
- Fields have `aria-invalid`, `aria-describedby`, and live error regions. Errors clear as the customer corrects values.
- The existing Laravel server-side validation and lookup authorization still run on every submitted request. No database migrations or route changes.

## Changed files
- `resources/views/storefront/bulk-quote/guest/track.blade.php`
- `resources/css/storefront.css`
- `public/build/manifest.json`
- `public/build/assets/storefront-guest-validation-0ae5c5d9b2.css` (new compiled asset for installations without a local frontend build)
- `tests/Feature/Storefront/GuestBulkQuoteJourneyTest.php`

## Verification
JavaScript syntax and mocked inline interactions (empty / malformed / corrected / valid values) passed. PHP syntax checks for 596 app and test files passed. Compiled CSS/manifest checks passed. Full Laravel feature tests must be run on a machine with Composer dependencies installed.

After replacing project files, retain the existing `.env`, `vendor/`, uploaded files and database. Run:

```bash
php artisan optimize:clear
php artisan test --filter=GuestBulkQuoteJourneyTest
```

Then browse `/bulk-quote/track`, leave both inputs empty and click "Track Quote"; messages should appear directly under each input without a popup.
