# NextPlay Sportswear — Secure Payment Phases 4 and 5

## Implementation boundaries

This patch extends the existing Phase 1–3 Stripe Checkout architecture. No administrator or super administrator can issue or directly mark refunds; Stripe Dashboard remains the sole refund initiation channel. No new payment gateway, admin payment status editor, card-capture form, production gate override, automatic refund action, or deposit percentage has been introduced.

## Phase 4 — Stripe Dashboard refund reconciliation

- The signed, queued Stripe webhook now handles `refund.created`, `refund.updated`, `refund.failed`, `charge.refund.updated`, and `charge.refunded` without relying on optional refund metadata.
- `StripeRefundProvider` retrieves the **current** Stripe refund and associated charge; this is read-only Stripe API access.
- Matching uses the charge's PaymentIntent to identify **one** paid Stripe attempt, plus matching live/test mode, amount, currency, captured charge, and order. Connect-account events are rejected; the current integration is a single Stripe account.
- A unique `(livemode, provider_refund_id)` record in `stripe_refund_syncs` handles duplicate webhook deliveries and stores any unmatched/conflicting refund as a review exception. Existing historical `order_refunds` are not rewritten or deleted.
- Pending and failed refunds do not increase confirmed refunded totals. A newly verified success records a single issued refund, updates the associated captured payment's refunded amount, computes derived order state, and creates a credit note only once. A stale event does not reverse a confirmed successful refund.
- The server-only `php artisan payments:sync-refund-exceptions --limit=50` command rechecks unresolved exceptions. It never sends a Stripe refund request. In addition to worker logs, inspect `stripe_refund_syncs` where `status=exception` until Phase 6 monitoring is implemented.
- **Important:** Stripe webhook subscriptions must include the refund events above and the existing payment events. The payment queue must be running, and `STRIPE_SECRET_KEY` must have permission to *read* refunds and charges but should not permit refund creation whenever restricted keys are available.

## Phase 5 — accepted quotation installments

- Additive schema: `order_installment_schedules`, `order_payment_installments`, and nullable `order_payments.order_payment_installment_id`.
- An installment contract is derived only from a **customer-accepted, converted guest bulk quotation** whose immutable quoted total, currency, version, and order identity agree. Neither a browser nor an administrator payment screen can choose installment amounts or edit a saved schedule.
- Quote `quotation_details.payment_installments` must be an ordered JSON list of exactly two structured entries:

```json
[
  {"kind":"deposit","amount_minor":4000},
  {"kind":"balance","amount_minor":7500}
]
```

For an accepted USD 115.00 quote, these represent USD 40.00 and USD 75.00. Each amount must be a positive integer in Stripe's currency minor units, and their sum must exactly match the immutable order total. Free-text terms such as "50% deposit" are **not parsed into charges**. If a quote mentions a deposit but has no structured amounts, payment is blocked pending approved terms instead of guessing.

- The feature is **off by default** with `PAYMENT_APPROVED_INSTALLMENTS_ENABLED=false` because the existing quotation publishing UI has only free-text payment terms. An authorized customer-accepted, structured quotation workflow must first be approved and connected before enabling it in production. Do not activate this flag as a workaround or manually inject JSON into live records.
- With that approved source and flag enabled, NextPlay offers the first unpaid installment only. Each attempt has its own immutable server amount, installment ID and Stripe idempotency reference. A live Checkout Session blocks additional attempts. PaymentIntent and Session metadata bind the installment to its payment attempt; the gateway verifies amount and currency before confirmation.
- A confirmed deposit is reported as `partially_paid`; fulfillment stays `pending_payment`. Only after both installments have verified receipts does the order become `paid` and advance to the existing design-review gate. No production release is authorized by a deposit alone.
- Old single-payment orders continue through the established checkout without extra installment database lookups when disabled. Legacy receipts are preserved and never automatically converted into installment receipts.

## Deployment / verification

1. Back up production database and separately record unresolved payment/refund totals and webhook configuration. Verify restore procedures.
2. Deploy the code, then run `php artisan migrate --force`. These migrations are **additive**. Do not roll them back after real refund/installment records are written.
3. Keep `PAYMENT_APPROVED_INSTALLMENTS_ENABLED=false` until the customer-accepted structured source is reviewed and available. Clear config cache after setting env flags.
4. Confirm Stripe webhook event subscriptions and finance-only Stripe Dashboard refund privileges. The NextPlay app key should not be able to create refunds.
5. Run:

```bash
php artisan optimize:clear
php artisan test --filter=PaymentReadOnlyPhase1Test
php artisan test --filter=OrderManagementTest
php artisan test --filter=StripePaymentArchitectureTest
php artisan test --filter=FinancialStatePhase23Test
php artisan test --filter=StripeRefundAndInstallmentPhase45Test
```

6. In **Stripe test mode**, verify full, partial, multiple, pending, failed, duplicated and delayed refund events; charge-refunded fallback; deposit then balance, conflicting payments, and expiring sessions. Compare Stripe charge/refund totals with local records before approving any deployment.
7. Run remaining project-wide tests separately. The prior baseline had unrelated failing full-suite tests and a PHP memory exhaustion. Those are not fixed by this payment-only patch.
8. If reverting code, keep and reconcile any financial records created while the updated code ran; never delete real Stripe events or captured payments.

## Verification limits

PHP syntax and archive integrity can be checked in this environment. The archive lacks `vendor/`, and this container does not include the testing SQLite extension or Composer dependencies; **Laravel integration tests and Stripe sandbox flows have not run here**. Do not treat this ZIP as production-approved without the above testing.
