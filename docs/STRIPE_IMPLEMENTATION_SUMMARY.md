# Stripe Payment Integration - Implementation Summary

Implemented on 2026-09-25.

## Production architecture

- Laravel remains the only authority for order totals and payment state.
- Stripe Hosted Checkout handles card entry; NextPlay never accepts raw card/CVV data.
- `PaymentOrchestrator` owns payment/refund lifecycle state.
- `PaymentGatewayManager` resolves centralized provider adapters.
- Stripe API secrets and webhook secrets are environment-only.
- Browser return URLs never mark an order paid.
- Signed Stripe webhooks are the primary source of payment truth.
- Payment reconciliation remains a scheduled safety net.

## Added/hardened

- Central provider capability rules for Stripe/manual payment methods.
- Server-side Stripe currency/positive-amount validation.
- Unique/idempotent webhook processing with retry recovery.
- Support for Stripe refund webhook events.
- Real Stripe refunds from the admin return/refund workflow.
- Stable refund idempotency keys to prevent double refunds.
- Provider refund references/statuses synchronized into `order_refunds`.
- `order_payments.refunded_amount` synchronized after confirmed refunds.
- Automatic credit note creation only after confirmed refund success.
- Safer generic payment-return screen that never falls back to demo order data.
- Migration to normalize payment-method capability flags and index refund provider references.
- Payment-focused feature tests and a fake Stripe gateway for deterministic test execution.
- Root `.gitignore` protection for `.env` and generated dependency/runtime files.

## Before enabling Stripe

Keep Stripe disabled until all server-side values are present:

```dotenv
STRIPE_ENABLED=false
STRIPE_SECRET_KEY=sk_test_or_live_value
STRIPE_PUBLISHABLE_KEY=pk_test_or_live_value
STRIPE_WEBHOOK_SECRET=whsec_value
STRIPE_CURRENCY=USD
```

After creating the Stripe webhook endpoint, subscribe to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`
- `refund.created`
- `refund.updated`
- `refund.failed`

Then set `STRIPE_ENABLED=true`, clear/cache Laravel config, run migrations, and ensure the `payments` queue worker plus scheduler are running.
