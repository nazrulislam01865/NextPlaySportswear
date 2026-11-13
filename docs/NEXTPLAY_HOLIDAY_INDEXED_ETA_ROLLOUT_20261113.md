# NextPlay Holiday Calendar: Indexed ETA Implementation and Rollout

**Scope:** holiday calendars, stored order delivery estimates, artwork approval anchors, calendar revision invalidation, queued re-evaluation and email outbox. Remote-area pricing, payment calculations, order-detail design and existing mail templates have not been changed.

## Runtime flow

```text
Calendar edit (admin request)
    |-> DB transaction: write holidays + advance country/year revision
    |-> AFTER COMMIT: queue ScheduleHolidayRecalculation
          |-> stream matching open order IDs in batches (default 150)
          |-> RecalculateHolidayOrdersBatch with row lock + shared revision lock
                |-> read immutable shipping durations + start event
                |-> if proof not approved: preserve pending/no fixed ETA
                |-> compute from cached yearly prefix + lower-bound search
                |-> only if changed: update order + eligible shipments + history
                |-> insert unique notification outbox row
                     |-> AFTER COMMIT: enqueue mail job
                     |-> scheduled recovery re-enqueues pending entries
Order Details (admin/account/tracking)
    |-> read stored estimated_delivery_start_at / estimated_delivery_end_at
    |-> NO holiday calculation or new delivery-related query on page view
```

Artwork publish or approval dispatches `RefreshHolidayOrderEta` after transaction commit. The job sets a pending state if any current proof has not yet been approved, otherwise uses the latest approval timestamp as the ETA anchor. Orders with only an order-placement start event retain their original anchor.

## Date algorithm and complexity

Per country and year: `holidays: date -> metadata` map, sorted holiday ISO date list, and `prefix[0..365/366]` cumulative working-day ranks. Prefix construction O(366 + H log H) if sorting is required; memory O(366 + H). Holiday membership O(1) average; same-year count O(1); delivery target O(log 366) per touched year; holiday explanation dates O(log H + K). Long windows cross years explicitly. 0-day semantics, weekend holidays, year boundaries and leap days retain legacy behavior.

## New data tables

- `holiday_calendar_revisions`: primary key `(country_code, year)`, authoritative monotonic version advanced in the same transaction as the edit. Immutable cache keys include country, year, policy version and committed revision.
- `holiday_eta_notifications`: unique logical event key, JSON email payload, `queued_at`, `processing_at`, `last_dispatched_at`. Durable deduplication and recovery of interrupted queue dispatches.

Old holiday and order tables, public response keys and storefront/admin templates remain unchanged. Product-specific duration and start-event fields are now included in newly placed orders' existing `shipping_method` JSON snapshot, preserving the old 8–12 business-day fallback only when no durations were configured. No automatic rewriting of historical order snapshots is performed.

## Safe deployment sequence (staging first)

1. Back up the database and deploy source files. Do not remove old cache records, saved order fields or holiday tables.
2. Install dependencies as needed (`composer install --no-dev --prefer-dist --optimize-autoloader` on production).
3. **Run migrations before serving new holiday requests:** `php artisan migrate --force`.
4. Configure a truly asynchronous queue for production, e.g. `QUEUE_CONNECTION=database` or `redis`; never use `sync` for this workload. Ensure database jobs/failed_jobs tables and a working queue worker. Keep the existing every-minute scheduler running (`php artisan schedule:run`).
5. For a compatibility-first rollout, set `NEXTPLAY_HOLIDAY_INDEX_ENABLED=false` and `NEXTPLAY_HOLIDAY_ASYNC_RECALC=false` initially, then `php artisan optimize:clear`. Run the baseline tests below.
6. Enable shadow comparisons with `NEXTPLAY_HOLIDAY_INDEX_ENABLED=true` and `NEXTPLAY_HOLIDAY_SHADOW_COMPARE=true`; inspect exception reporting for mismatches. Once verified, disable shadow comparisons.
7. Enable `NEXTPLAY_HOLIDAY_ASYNC_RECALC=true`; run `php artisan optimize:clear && php artisan queue:restart`. Confirm workers consume the configured `NEXTPLAY_HOLIDAY_QUEUE` (default `default`).
8. In staging, update one holiday calendar and verify HTTP response is prompt, new revision is stored, jobs are processed, only changed orders are written, and queued emails are not duplicated after retry.
9. Verify the admin and customer Order Details pages do not run `HolidayCalendarService` or change persisted ETA fields on page load.

**Environment flags** (all implemented under `config/nextplay_shipping.php`):

```ini
NEXTPLAY_HOLIDAY_INDEX_ENABLED=true
NEXTPLAY_HOLIDAY_SHADOW_COMPARE=false
NEXTPLAY_HOLIDAY_ASYNC_RECALC=true
NEXTPLAY_HOLIDAY_QUEUE=default
NEXTPLAY_HOLIDAY_BATCH_SIZE=150
```

If the async flag is disabled, calendar edit requests fall back to the existing synchronous recalculation path after commit. If the index flag is disabled, the legacy day-by-day calculator is used. Do not drop the newly added tables when rolling back the feature flags.

## Verification commands

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan test --filter=HolidayCalendarIndexedOptimizationTest
php artisan test --filter=HolidayCalendarServiceTest
php artisan test --filter=HolidayCalendarEndToEndFlowTest
php artisan test --filter=OrderRecalculationAndNotificationTest
php artisan test --filter=HolidayCalendarCrudTest
php artisan test --filter=ArtworkApprovalWorkflowTest
php artisan holidays:recalculate-orders --country=GB --dry-run
php artisan holidays:dispatch-notifications --limit=50
php artisan queue:failed
php artisan schedule:list
```

**Important:** the uploaded source ZIP does not include Composer's `vendor` directory. These framework tests must run in your actual installed Laravel environment. Standalone pure-algorithm differential checks were executed separately on the generated calculator (1,718 cases), along with PHP lint checks.

## Regression checklist

- Same date/holiday names under multiple calendars, weekend-only destination, missing-year calendar, leap day, 0-day start and multi-year durations.
- Calendar create/edit/delete/toggle and moving a calendar between countries or years; both old and new revision keys refreshed.
- Failed admin transaction does not dispatch recalculation; rapid edits coalesce via generation checks.
- Configured per-product duration is preserved; unconfigured production falls back to the original 8–12 days.
- Before artwork approval: pending without firm ETA; after all current proofs approved: latest approval anchors new estimate; newly published proof reopens pending state.
- Unchanged ETA does not create history, email or database write. Shipped/delivered orders and in-transit/delivered shipments are protected.
- Manual dry run is read-only, processes in bounded batches and limits display to 100 changed rows.
- Outbox writes are unique per actual change, queue retries do not re-enqueue previously recorded messages, and the minute scheduler recovers unqueued pending records.
- Benchmark checkout P50/P95, calendar cold/warm DB query counts, admin edit latency, outbox error count and 10k-order worker peak RSS in staging before enabling on production.

## Known infrastructure dependency

The outbox guarantees durable enqueue intent and deduplicates logical notification requests; mail-provider delivery itself cannot be guaranteed exactly once without a provider-level idempotency API. The `queued_at` field means accepted by the existing email service, not confirmed delivered to the recipient. The existing country filtering uses JSON address predicates; if `EXPLAIN ANALYZE` shows large scans at scale, add a separately tested normalized indexed order-country column and migrate old data safely.
