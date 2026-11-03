# NextPlay Payment Security — Phase 2 + Phase 3

Scope: approved Option 1 engineering plan, Phase 2 (centralized financial projection) and Phase 3 (failure tracking / secure retry). Preserves Phase 1 read-only administrator payment and refund lock. **Not a Stripe Dashboard refund synchronization or installment-payment release.**

## Changes

- `app/Services/Payments/FinancialStateService.php`: centralized read-only projection of recorded captures, confirmed refunds, net received, collection state, refund state, contractual remaining due, and historical unverified-receipt flag. All monetary calculations are in integer minor units. Paid receipts in the old administrative/manual system remain historically preserved and flagged, not silently deleted.
- `app/Models/Order.php`: exposes `financialState()`, uses centralized `outstandingAmount()`; `config/commerce.php` recognizes `partially_paid` for display. A partial receipt **does not** qualify an order for production or authorize another installment. Full installment scheduling remains Phase 5.
- `app/Payments/Money.php`: centralizes round-half-up minor-unit calculations from decimal strings. `app/Models/OrderPayment.php` protects amounts and identities once a provider session is posted and prevents confirmed receipts from being rewritten or downgraded by ordinary Eloquent updates.
- `app/Services/Payments/PaymentOrchestrator.php`: validates IDs/order association and amount/currency; recognizes PaymentIntent failure and success; stores a sanitized recoverable decline without closing active Checkout; reconciles a late intent success against the actual Session; serializes post-payment state updates; never erases a previously confirmed receipt; updates the legacy payment status from the central projection; guards duplicate/overlapping payment sessions; keeps refund initiation disabled.
- `app/Payments/Gateways/StripeGateway.php`: checks live/test mode of signed webhooks, checks Checkout Session order and payment metadata and PaymentIntent association on retrieval, and retains the Phase 1 refund block.
- `app/Http/Controllers/Payments/PaymentReturnController.php`: customer-friendly failed/expired/declined/pending messages, while payment success still requires provider verification.
- `tests/Fakes/FakeStripeGateway.php`, `tests/Feature/FinancialStatePhase23Test.php`: amount/currency-bearing provider test fixture and targeted regression tests for partial captures, refunds as a separate dimension, decline, terminal failure, delayed success, duplicate events, mismatched IDs/amounts, concurrent payment sessions and immutable records.

## Security invariants

1. Admin/super admin still cannot update payment status, enter received amounts, or issue refunds from NextPlay (Phase 1 unchanged).
2. A customer-facing redirect never establishes payment success. Only verified Stripe Checkout settlement can confirm a receipt.
3. Every Stripe payment confirmation must match the local payment attempt, Session/Intent reference, amount and currency.
4. An active Checkout's `payment_intent.payment_failed` is a *declined method*, not necessarily a terminal failed order.
5. Pending/failed/expired attempts cannot overwrite confirmed captures; a later verified success can recover after an earlier failed event.
6. No second Checkout is opened while an existing attempt may still settle, including when a different payment method is requested.
7. A confirmed partial receipt does not release production or permit an unapproved remaining-balance charge.
8. Captured receipts and confirmed refunds are tracked separately. Refunds do not automatically create a new amount due.
9. If historical payment/refund currencies conflict, fail closed and require financial review rather than inventing balances.
10. Receipt notifications are queued after database commit; notification failure does not undo provider-confirmed payment state.

## Required Stripe webhook subscriptions

On the same signed webhook endpoint, verify subscriptions to `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `checkout.session.expired`, `payment_intent.payment_failed`, and `payment_intent.succeeded`. Retain existing refund subscriptions for later Phase 4, but note that Stripe Dashboard initiated refunds are **not yet fully supported** by the legacy refund matcher.

## Local release gate

Install the dependencies from `composer.lock` and run:

```bash
php artisan optimize:clear
php artisan test --filter=PaymentReadOnlyPhase1Test
php artisan test --filter=OrderManagementTest
php artisan test --filter=StripePaymentArchitectureTest
php artisan test --filter=FinancialStatePhase23Test
php artisan test
```

Then verify in **Stripe test mode**: card decline and retry; async failure and later settlement; expired session and refresh; two overlapping payment attempts; duplicate and out-of-order webhooks; non-matching metadata/amount; correct customer notifications; application queue processing and supervisor restart. Compare Stripe actual capture totals with local `order_payments` before enabling on production. Do not deploy based only on PHP syntax checks.

## Production deployment and rollback

- Back up the database, record existing payment/refund totals and active Stripe Sessions, and retain the last tested code release.
- No new database migration is required for this phase. Historical receipts are preserved as-is.
- Deploy the entire tested codebase together, clear caches, restart the **NextPlay payment queue worker**, verify Stripe webhook subscriptions, and monitor `payment_webhook_events` and failed jobs.
- On failure, roll back the application code only. Do not delete or roll back financial events received during the deployment; reconcile them against Stripe.

## Explicit exclusions / outstanding phase approvals

- **Phase 4**: reliable Stripe Dashboard-initiated refunds, partial refunds, refund lifecycle conflict protection and unmatched-refund reconciliation. Until implemented, refund results must be verified directly in Stripe Dashboard.
- **Phase 5**: authorizing deposit/balance schedules and Checkout Sessions for remaining installments. `partially_paid` is an accurate *reporting state only* at this phase.
- **Phase 6**: complete reconciliation monitoring, incident alerting, and exception workflows. Existing webhooks, payment queues and basic reconciliation remain; full production resilience requires this later phase.
- Phase 1 administrator payment controls are intentionally unchanged. No checkout redesign, change to pricing formulas or unrelated application logic.

## Verification performed in packaging environment

All PHP source and test files passed `php -l`. The Word plan was used as the scope and security gate. Composer packages and the Laravel test runner are not included in the uploaded project, so automated feature tests and live Stripe sandbox verification cannot be executed in this environment. The tests included in this archive must pass locally before production deployment.
