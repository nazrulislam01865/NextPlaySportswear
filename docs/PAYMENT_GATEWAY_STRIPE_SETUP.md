# NextPlay Central Payment Gateway + Stripe Setup Guide

## What this update changes

The payment layer is now provider-agnostic. Checkout and order controllers do not contain Stripe API logic. `PaymentOrchestrator` owns NextPlay payment state, `PaymentGatewayManager` resolves a provider, and `StripeGateway` is the Stripe-specific adapter.

Security changes included:

- Raw card/CVV collection was removed from the customer account area.
- Stripe is hidden from customer checkout until the SDK, secret key and webhook secret are all configured. The code can be deployed before the webhook secret exists; leave `STRIPE_ENABLED=false` until it is added.
- Provider codes are centrally registered in `config/payments.php`; admins cannot invent an unimplemented provider code.
- Every payment attempt gets its own idempotency key.
- Stripe Checkout is created only after the order DB transaction has committed.
- The browser success redirect never marks an order paid.
- Stripe webhooks are signature-verified from the raw request body.
- Webhook event IDs are deduplicated in `payment_webhook_events`.
- Webhook payloads are encrypted at rest using Laravel's encrypted cast.
- Amount and currency are checked against the server-side order payment before `paid` is accepted.
- Payment/order rows are locked while finalizing to prevent double processing.
- Webhook processing is queued, duplicate jobs are uniquely claimed, and reconciliation runs every 15 minutes as a safety net.
- Stripe refunds are initiated through the same centralized gateway adapter, use a stable refund idempotency key, and update payment/refund totals only after provider success.
- Security-sensitive provider behavior (hosted redirect/manual review/saved-card capability) is defined in `config/payments.php`, not trusted from editable admin database flags.
- API keys are environment-only and never stored in the admin payment method table.

## Architecture

```text
Checkout / Retry Payment
        |
        v
PaymentOrchestrator
        |
        v
PaymentGatewayManager
        |
        +--> ManualGateway
        |
        +--> StripeGateway
        |
        +--> future PayPalGateway / BkashGateway / SSLCommerzGateway
```

Webhook flow:

```text
Stripe
  -> POST /webhooks/stripe
  -> verify Stripe-Signature against the raw body
  -> deduplicate provider event ID
  -> queue ProcessPaymentWebhook
  -> verify internal payment + amount + currency
  -> row lock
  -> mark payment paid
  -> mark order payment_status=paid and status=payment_review
```

## Step 1 - Install project dependencies

`stripe/stripe-php` is already declared in `composer.json` and locked in `composer.lock`. Do not run a targeted Stripe update during a normal production deployment.

Use the lock file:

```bash
composer install --no-dev --optimize-autoloader
```

Requirements used by Stripe PHP include PHP, cURL, JSON and mbstring.

## Step 2 - Migrate the database

Back up production first, then run:

```bash
php artisan migrate
```

The new migration:

- expands `order_payments`
- creates `payment_webhook_events`
- creates `customer_gateway_profiles`
- disables the unimplemented PayPal option
- disables Stripe saved-card selection until real Stripe vaulting/Setup mode is added

## Step 3 - Get values from Stripe

From the Stripe Dashboard collect these for **test mode first**:

1. Secret API key, preferably a restricted key if its permissions fit this integration (`rk_test_...`), otherwise `sk_test_...`.
2. Publishable key (`pk_test_...`). Hosted Checkout currently does not need it in browser code, but keep it for future Stripe components.
3. Webhook endpoint signing secret (`whsec_...`). This is NOT the API secret key.
4. Stripe account ID (`acct_...`) for operations/support records; it is optional for runtime.
5. Confirm Cards and any desired Stripe payment methods are enabled.
6. Configure business name, statement descriptor, support details, payout bank account and Checkout branding in Stripe Dashboard. Payout-bank credentials do not belong in this Laravel app.

Never paste live secret keys into source code, database fields, admin forms, logs, tickets or Git.

## Step 4 - Configure local/test `.env`

```dotenv
PAYMENT_DEFAULT_GATEWAY=stripe
PAYMENT_WEBHOOK_QUEUE=payments
PAYMENT_WEBHOOK_CLAIM_TIMEOUT_MINUTES=10
PAYMENT_RECONCILE_AFTER_MINUTES=10
PAYMENT_RECONCILE_BATCH_SIZE=100

STRIPE_ENABLED=true
STRIPE_SECRET_KEY=sk_test_replace_me
STRIPE_PUBLISHABLE_KEY=pk_test_replace_me
STRIPE_WEBHOOK_SECRET=whsec_replace_me
STRIPE_CURRENCY=USD
STRIPE_CHECKOUT_EXPIRES_MINUTES=30
```

Then:

```bash
php artisan optimize:clear
php artisan config:cache
```

If any of the SDK/secret key/webhook secret is missing, Stripe deliberately does not appear as an available checkout method.


## Can the code be deployed before the webhook secret exists?

Yes. The implementation does not require a real `whsec_...` value to be present in source code. Deploy the code with `STRIPE_ENABLED=false`, add the API/webhook secrets directly to the server environment later, then enable Stripe and rebuild Laravel's config cache. This deliberately prevents customers from starting live Stripe Checkout while the signed webhook source-of-truth is unavailable.

```bash
php artisan optimize:clear
php artisan config:cache
```

Never use the publishable key as the webhook secret. The webhook secret always begins with `whsec_...` and belongs to the exact Stripe webhook endpoint/environment.

## Step 5 - Local webhook testing

Install Stripe CLI on your development machine, authenticate, then forward Stripe events to Laravel:

```bash
stripe login
stripe listen --forward-to http://127.0.0.1:8000/webhooks/stripe
```

The CLI prints a temporary `whsec_...` signing secret. Put that value in `STRIPE_WEBHOOK_SECRET` for that local listener and refresh config.

## Step 6 - Queue worker

Webhook work should not block Stripe's HTTP delivery. Run a worker locally:

```bash
php artisan queue:work --queue=payments,default --tries=5
```

For production use Supervisor/systemd and preferably Redis for cache/queue at scale. The code still works with Laravel's database queue for smaller deployments.

## Step 7 - Scheduler

The project now schedules reconciliation every 15 minutes. Production must execute Laravel scheduler:

```cron
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

Reconciliation is only a safety net. Signed webhooks remain the primary source of payment truth.

Manual reconciliation can be run with:

```bash
php artisan payments:reconcile
php artisan payments:reconcile --provider=stripe --limit=50
```

## Step 8 - Stripe Dashboard webhook endpoint

For production create a Stripe webhook endpoint:

```text
https://YOUR-DOMAIN.com/webhooks/stripe
```

Subscribe at minimum to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`
- `refund.created`
- `refund.updated`
- `refund.failed`

Copy that endpoint's live `whsec_...` value into the production environment. Test-mode and live-mode webhook secrets are different.

## Step 9 - HTTPS and server configuration

Do not enable live payments on a plain public IP over HTTP. Use a domain with a valid TLS certificate. Nginx/Apache document root must point to Laravel's `/public` directory, not the repository root.

Example target:

```text
https://shop.example.com
```

The `.env` file must stay outside public web access and should have restrictive OS permissions. For higher-security production deployments use the cloud provider's secret manager to inject payment secrets.

## Step 10 - Test the complete payment lifecycle

Use Stripe test mode and validate at least:

- normal successful Checkout payment
- card decline
- 3DS/authentication flow
- customer cancels the Stripe page
- duplicate Place Order click
- duplicate webhook delivery
- webhook arriving before browser return
- browser never returning after payment
- temporary Stripe API failure
- queue retry after worker restart
- expired Checkout Session
- retry payment from an existing order
- amount/currency mismatch protection
- reconciliation after a deliberately missed payment webhook
- full and partial Stripe refund
- duplicate refund submission (must not double-refund)
- pending/failed refund webhook update

Do not switch to live mode after only testing one successful card.

## Step 11 - Production switch

When test-mode validation is complete:

1. Create/use production live Stripe API key.
2. Create production webhook endpoint and copy its LIVE signing secret.
3. Set production `APP_URL=https://...`.
4. Set `STRIPE_ENABLED=true` only after the keys/webhook are ready.
5. Run `php artisan optimize:clear && php artisan config:cache`.
6. Confirm queue workers and scheduler are running.
7. Make a small real transaction and confirm Stripe Dashboard, `order_payments`, `payment_webhook_events`, and order state all agree.

## Exact Stripe values the Laravel app needs

```text
STRIPE_SECRET_KEY      = sk_test_... / sk_live_... or an appropriate restricted rk_... key
STRIPE_WEBHOOK_SECRET  = whsec_...
STRIPE_PUBLISHABLE_KEY = pk_test_... / pk_live_... (kept for future client-side Stripe features)
```

Do NOT put bank credentials, card credentials, API secrets, webhook secrets or recovery codes in the admin Payment Methods screen.

## Adding another gateway later

1. Create a class such as `app/Payments/Gateways/BkashGateway.php`.
2. Implement `App\Payments\Contracts\PaymentGateway`.
3. Add a provider entry to `config/payments.php`.
4. Put secrets in environment/secret manager only.
5. Add the provider webhook route/controller if that gateway uses webhooks.
6. Create/enable a `payment_methods` database record with that provider code.
7. Add provider-specific tests.

Checkout controllers and order domain logic should not need gateway-specific code.

## Saved cards

The insecure Laravel raw-card form has intentionally been removed. The current Stripe payment is hosted Checkout only.

When saved cards are required, implement Stripe Customer + Checkout/Setup mode so Stripe collects the card and NextPlay stores only `cus_...`, `pm_...`, brand, last four digits and expiry. Never reintroduce PAN/CVV fields into Laravel.

## Key files

```text
config/payments.php
app/Payments/Contracts/PaymentGateway.php
app/Payments/PaymentGatewayManager.php
app/Payments/Gateways/StripeGateway.php
app/Payments/Gateways/ManualGateway.php
app/Services/Payments/PaymentOrchestrator.php
app/Http/Controllers/Webhooks/StripeWebhookController.php
app/Jobs/Payments/ProcessPaymentWebhook.php
app/Console/Commands/ReconcilePayments.php
database/migrations/2026_09_07_000001_harden_payment_gateway_architecture.php
```
