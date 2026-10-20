# Registered customer bulk quotation screens – 20 October 2026

## Scope
Three registered customer prototypes are implemented in the existing NextPlay My Account shell:

1. **My Account → Bulk Quotes**: customer-only list, five status statistics, six tabs, paginated quotation rows, version/file counts and actions.
2. **Quotation Details**: quotation summary, actual admin-provided totals, current quotation documents, terms, conversation, feedback and three decisions (confirm/order/change request) subject to quote state.
3. **Request Quotation Changes**: current quotation summary, documents, communication and a validated multi-option change form with private attachments.

No mock quotations or sample prices are inserted. Display values come from `bulk_quote_requests`, `bulk_quote_files` and `bulk_quote_messages`. The existing admin quotation publication/replies are enabled for both account-owned and guest records; registered customers receive a direct My Account link in transactional emails.

## Routes

- `GET /account/bulk-quotes` (filter with `?status=...`)
- `GET /account/bulk-quotes/{reference}`
- `GET|POST /account/bulk-quotes/{reference}/changes`
- `POST /account/bulk-quotes/{reference}/message`
- `POST /account/bulk-quotes/{reference}/decision`
- `GET /account/bulk-quotes/{reference}/file/{file}`

All routes use the existing `not.admin`, `auth:web`, `customer`, `verified` middleware. Every read and write checks `user_id`. Private documents are served from the Laravel private disk after quote and file ownership checks. Existing CSRF, upload validation, feedback rate limits, account/order linking and duplicate order prevention are preserved.

## Behavior

- Registered bulk quote submission redirects to its account detail, with a success message.
- Pending requests can be viewed, but cannot be accepted or modified until admin publishes a quotation.
- Admin may publish a versioned quotation and reply to registered customers from the existing bulk quote detail page.
- A change request updates quote status and appears in both account and admin conversations. A new version resets the review decision.
- **Confirm Quotation Only** records acceptance without creating an order.
- **Confirm & Create Order** creates one account-owned order with payment pending. Repeated requests are idempotent and do not create duplicate orders.
- Expired/closed quotes cannot be newly accepted; missing files display a useful message.

## Installation and verification

Back up live data and preserve `.env`, `storage` uploads, and any local vendor folders before replacing source. No new migrations are introduced by this update, but **all previously pending migrations must exist and run** for quote tables/columns to be available.

```bash
composer install --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
php artisan test --filter=RegisteredBulkQuoteCenterTest
php artisan test --filter=GuestBulkQuoteJourneyTest
```

Run `npm ci && npm run build` to regenerate assets from source in normal development. The provided archive also includes updated compiled storefront CSS so an immediate rebuild is not necessary for styling.

Manual acceptance: sign in as customer A, submit bulk quote, see it in My Account; admin publishes quote and file; A opens file/replies/request changes; admin sees message and publishes version 2; A confirms without order, then creates order, checks it in My Orders. Customer B must receive 404 for A's quote/files/actions. Verify responsive layout on desktop/tablet/mobile, and verify all status tabs including empty state. Verify both production email messages and the guest quote workflow still function.

## Validation status

PHP syntax and scoped CSS parse checks passed, along with Blade directive nesting and source ZIP integrity checks. The Composer `vendor` directory is absent from the uploaded source, so the Laravel integration suite and server-rendered pixel matching could not be executed inside this workspace. These remain release gates before production deployment.

A signed-in customer may only submit a quote using the verified/registered account email; this prevents a quote being linked to an account while the order conversion rejects the different quote email. The existing public guest submission remains unchanged.

The archive intentionally omits the uploaded archive's runtime cache, logs and user-uploaded `storage` files. These are **not** application source and should never be overwritten during deployment. Preserve the existing `storage` tree on your server, including private quote documents.
