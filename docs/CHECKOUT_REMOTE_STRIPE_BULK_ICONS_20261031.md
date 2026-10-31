# NextPlay — Remote-area warning, Stripe checkout and Bulk Quote heading icons

## Changes

1. Shipping step only shows the remote-area delivery notice after a matching postcode/city/country rule is found. The request uses the existing `RuralAreaSurchargeService` matcher and a debounced, authenticated POST endpoint (`checkout.remote-area-status`). The existing order-level surcharge calculation remains unchanged, once per order.
2. Review remains the final on-site checkout stage. The Review submission continues to `checkout.place-order.submit`, which creates the server-confirmed order and redirects to Stripe Checkout. A gateway availability check now prevents creating an unpaid order when Stripe is not configured. If Stripe fails *after* the order is stored, the customer sees a visible payment-retry message on the order page.
3. Admin > Storefront > Bulk Quote Icons allows upload/replacement/removal of Product & Customisation, Contact Information, Delivery Requirements, Attachments & Notes, and Need a hand? heading icons. Each uploaded image is displayed uncropped at 52 x 52 pixels. The default SVG icons remain when no image is uploaded. File settings are stored locally in persistent Laravel storage, and images on the `public` disk; no database schema change or additional read queries.

## Configuration needed for Stripe test mode

In `.env`, supply your own Stripe test-mode credentials and make sure a Stripe payment method record is active:

```ini
APP_URL=http://127.0.0.1:8000
STRIPE_ENABLED=true
STRIPE_SECRET_KEY=sk_test_YOUR_KEY
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SIGNING_SECRET
STRIPE_CURRENCY=USD
```

Never paste live Stripe keys into chat, source control, or public files. `STRIPE_WEBHOOK_SECRET` must be the webhook signing secret for the correct endpoint. Test mode's webhook delivery to a local Laravel server requires a webhook forwarding method such as Stripe CLI. Stripe payments cannot redirect if your gateway is disabled, unconfigured, blocked by network access, or the gateway API rejects the session creation.

After editing `.env`, run `php artisan config:clear`. Verify an active Stripe-backed payment method exists in Admin > Payment Methods. Do not enter card numbers into NextPlay; Stripe collects card details on its own hosted page.

## Local installation

Preserve existing `.env`, database, `vendor`, `storage`, and user uploads. Apply the patch to the current NextPlay folder or carefully merge the full project source.

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan storage:link
npm ci
npm run build
php artisan test --filter=CheckoutRemoteAndBulkIconsTest
php artisan test --filter=CheckoutPrototypeFlowTest
node --test tests/frontend/*.test.mjs
```

No migrations are required. Icon files need the storage symbolic link and writable storage paths. Admin icon editor URL: `/admin/bulk-quote-icons`.

## Verification checklist

- Enter an ordinary postcode: remote notice stays hidden.
- Enter a postcode matching an active Remote Area Surcharge rule for the selected country: notice appears, and the surcharge is included in totals after continuing.
- Switch to a nonmatching postcode: remote notice disappears. Check an existing saved address as well.
- Review and accept terms, click Place order & pay: Stripe Checkout opens when gateway is configured.
- Disable Stripe deliberately in local: submission returns an inline message without placing an additional order.
- Test Stripe API/network failure after order creation: order details displays payment retry guidance.
- Upload a PNG/WebP icon as admin and verify 52x52 rendering; restore default and check fallback.

The uploaded archive lacks Composer's `vendor`, so Laravel integration tests and Stripe-hosted redirect require execution in your installed environment. PHP syntax checks and 32 frontend regression tests passed during packaging.
