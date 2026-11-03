# NextPlay Sportswear — Payment Security Phase 6

## Purpose / scope

This release extends the existing Option 1 Stripe-controlled financial system with bounded recovery, safe webhook storage, fair payment/refund reconciliation, append-only transaction audit records, and operator-only monitoring. It **does not** create a NextPlay refund interface, authorize administrators to change financial results, change checkout layout/prices, or activate installments. Stripe Dashboard remains the only channel for authorized refunds. Accepted structured installment terms are still required and `PAYMENT_APPROVED_INSTALLMENTS_ENABLED=false` remains the default.

## Changes and security controls

- **Webhook ingress:** Stripe's raw request body is checked using stripe-php signature verification and the server-only signing secret. The configured API key's live/test mode must match the event; Connect account events are denied. A verified event ID uniquely identifies one payload hash; a conflicting event ID/payload gets HTTP 409. Oversized bodies are denied. Payloads are persisted encrypted but **reduced to allowed financial identifiers, amounts/statuses, and approved order/installment metadata only**. Billing details, email/card objects and detailed decline messages are omitted. The raw body is used for verification and hashing, not retained as a database field.
- **Queue failure response:** HTTP 503 is returned if a verified event cannot be queued. A separate recovery command requeues stale verified events (received/failed/abandoned processing) in a bounded, fair, cooled-down batch. Jobs lock the event row, never steal a live claim, count processing attempts, and never overwrite a processed record. Event-level unique keys and existing transaction/idempotency checks protect duplicate deliveries. Failed jobs store only an exception class, not exception text or card data. After the configured cap, an event remains visible for operator investigation rather than retrying forever.
- **Reconciliation fairness:** `payments:reconcile` now records `last_reconciled_at` and error counts, selects unchecked/due attempts first and skips recently retried attempts. `payments:sync-refund-exceptions` similarly advances a provider-error item after attempting it. Neither command invents paid/refunded status or initiates any refund. Existing Stripe read-back and locked financial transition rules remain the only authority.
- **Append-only audit:** `payment_audit_entries` stores a unique transition key, event type, Stripe source, optional live/test mode and provider reference, linked order/payment/refund, previous/new states, currency, amount in minor units, and timestamps. Financial audit inserts occur **inside the payment/refund database transaction**, so a rolled-back change leaves no false success audit. A unique transition key prevents duplicate audit rows. No update/delete endpoints or administrative edit interface exist for this table. Server-shell operators can inspect bounded entries using `php artisan payments:audit --order=123 --limit=50`. Database-owner permissions remain an external operational responsibility.
- **Read-only health:** `payments:health` counts stale/failed/exhausted webhooks, refund exceptions, pending payment age, failed payment queue jobs, and mismatches in a **bounded sample** of recent Stripe-paid orders. It emits aggregate-only warning logs and can send a throttled, aggregate-only email to an operator address configured at server level. The report is not a full historical financial reconciliation.
- **Scheduler:** automatically registers `payments:recover-webhooks` every five minutes, `payments:reconcile` and `payments:health` every fifteen minutes, and `payments:sync-refund-exceptions` every thirty minutes, using Laravel's `withoutOverlapping`. Actual execution requires your existing scheduler cron and payment queue workers to be running. No storefront query is added.

### Additive migration

`2026_11_03_220000_harden_payment_webhook_recovery_and_audit.php`: adds retry/reconciliation timestamps and indices and creates `payment_audit_entries`. It neither rewrites nor deletes existing financial/payment/refund events. Do not reverse this migration after live financial events have been recorded.

### Server-side optional configuration

```dotenv
PAYMENT_WEBHOOK_QUEUE=payments
PAYMENT_WEBHOOK_MAX_PAYLOAD_BYTES=262144
PAYMENT_WEBHOOK_CLAIM_TIMEOUT_MINUTES=10
PAYMENT_WEBHOOK_REDISPATCH_MINUTES=5
PAYMENT_WEBHOOK_MAX_RECOVERY_ATTEMPTS=30
PAYMENT_RECONCILE_AFTER_MINUTES=10
PAYMENT_RECONCILE_BATCH_SIZE=100
PAYMENT_RECONCILE_COOLDOWN_MINUTES=20
PAYMENT_HEALTH_STALE_MINUTES=30
PAYMENT_HEALTH_SAMPLE_SIZE=75
PAYMENT_HEALTH_UNSETTLED_THRESHOLD=20
PAYMENT_HEALTH_ALERT_EMAIL=
PAYMENT_HEALTH_ALERT_COOLDOWN_MINUTES=60
PAYMENT_APPROVED_INSTALLMENTS_ENABLED=false
```

Configure `PAYMENT_HEALTH_ALERT_EMAIL` to a **named, authorized finance/security operations inbox** if alert emails are required. Never place Stripe keys or webhook secrets into the admin CMS. The server should have a dedicated payment queue worker; the scheduler must run once per minute and `schedule:list` should show the Phase 6 commands. Restrict Stripe Dashboard refunds with MFA and least privilege. Verify that the app's Stripe key has only required read/payment scopes where possible.

## Testing and verification

After database backup and deploy, run `php artisan migrate --force` and `php artisan optimize:clear`, then:

```bash
php -d memory_limit=512M artisan test --filter=PaymentOperationalAssurancePhase6Test
php -d memory_limit=512M artisan test --filter=StripeRefundAndInstallmentPhase45Test
php -d memory_limit=512M artisan test --filter=FinancialStatePhase23Test
php -d memory_limit=512M artisan test --filter=PaymentReadOnlyPhase1Test
php -d memory_limit=512M artisan test --filter=StripePaymentArchitectureTest
php -d memory_limit=512M artisan test --filter=OrderManagementTest
php artisan payments:recover-webhooks --limit=50
php artisan payments:reconcile --provider=stripe --limit=50
php artisan payments:sync-refund-exceptions --limit=50
php artisan payments:health --json
php artisan payments:audit --limit=50
php artisan schedule:list
```

`payments:health` returns exit code 1 if it finds anomalies. That is an operational alert, not permission for an admin to adjust payment records. Use **Stripe test mode** to confirm real signed webhook replay, invalid signature/mode, delayed success, duplicate notifications, pending/failed and multiple partial refunds, provider outage, queue outage, and Stripe Dashboard-only refund flow. Verify audit entries and compare Stripe charge/refund records against NextPlay at the intended provider mode/account. Test cancelled/held order late payment handling and deposit/balance production gates.

**Release gate:** Syntax checks and packaging alone are not enough. The archive has no Composer vendor directory and the current execution environment lacks needed PHP testing extensions; Laravel feature tests and Stripe sandbox scenarios must pass in the user's environment. The prior unrelated project-wide failures and memory exhaustion are not repaired by this payment-only workstream.

## Recovery and operational limitations

- A database outage can prevent both webhook persistence and audit writing; Stripe must be configured to retry deliveries, the payment worker and scheduler must be healthy, and operators must inspect exceptions.
- Event uniqueness is enforced within this single Stripe account integration. Do not connect multiple Stripe accounts or modes to the same webhook/DB without a scoped provider-account migration.
- Retry counts and alerting help identify failures but do not guarantee that a provider has been reconciled. A backend/operator with unrestricted production DB or Stripe secret access can bypass application authorization; restrict those credentials separately.
- Existing historical webhook rows may retain older, broader payloads. This release sanitizes **new incoming events** and intentionally does not rewrite historical financial data. Review retention with the finance/security owner.
- The monitoring sample is bounded. It does not prove that all legacy/historical orders agree with Stripe. Finance must perform a complete provider-led accounting reconciliation before release approval.
- To roll back application code, keep any real financial events/audit records created while Phase 6 ran. Do not delete Stripe captures/refunds or blindly reverse the additive migration; inspect and replay unresolved events after recovery.
