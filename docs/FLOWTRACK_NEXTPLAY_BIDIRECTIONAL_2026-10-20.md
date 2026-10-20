# NextPlay <> FlowTracker order integration (2026-10-20)

This release **extends** the existing NextPlay integration. Do not replace existing `.env`, product uploads, database records, or payment configuration. Deploy **both** project ZIPs as a coordinated release.

## Responsibilities

- NextPlay: source of truth for buyer identity, purchased-product/order snapshots, pricing, payment, shipping/checkout, registered account and public order tracking.
- FlowTracker: per-buyer `clients` records and production workflow jobs. The e-commerce order snapshot includes purchased product specs, selections, roster, supplier-independent customization, artwork references, amounts, contact, shipping/billing, and history. No unrelated wholesale catalog or credentials are replicated.
- Status callbacks add **separate** operational status/stage/progress to NextPlay; they do **not** update payment, refund, or checkout status automatically.
- NextPlay checkout AND approved-bulk-quote conversion trigger the existing idempotent API integration.
- All artwork transfers use the existing authenticated file endpoint and SHA-256 integrity check. Optionally queue artwork asynchronously to avoid delaying order receipt.

## 1. Staging only: backup and migrations

Stop automatic deployments until staging succeeds. Back up BOTH databases, both `.env` files, and the storage trees on their respective machines. Copy each complete source tree while retaining current `.env`/uploads/vendor; run `composer install` as in each project's existing deployment instructions.

**NextPlay only:**

```bash
cd /var/www/nextplay
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

**FlowTracker:**

```bash
cd /var/www/flowtrack
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan nextplay:provision-workflow
```

The new NextPlay migration adds four operational progress columns. FlowTracker uses its existing client, workflow and ecommerce snapshot tables; no new FlowTracker tables are required.

## 2. Environment configuration (examples; use your real HTTPS hosts)

NextPlay `.env` (keep your current APP_KEY):

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=https://YOUR_FLOWTRACK_HOST
FLOWTRACK_INTEGRATION_TOKEN=YOUR_STRONG_RANDOM_SHARED_SECRET
FLOWTRACK_QUEUE_ENABLED=true
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
FLOWTRACK_QUEUE_TRIES=5
FLOWTRACK_QUEUE_BACKOFF=1,3,10,30,90
FLOWTRACK_REQUEST_TIMEOUT=10
```

FlowTracker `.env`:

```dotenv
NEXTPLAY_INTEGRATION_TOKEN=THE_SAME_STRONG_RANDOM_SHARED_SECRET
NEXTPLAY_SOURCE_BASE_URL=https://YOUR_NEXTPLAY_HOST
NEXTPLAY_RECEIVER_USER_ID=YOUR_ACTIVE_FLOWTRACK_INTEGRATION_USER_ID
NEXTPLAY_ORDER_PROGRESS_ENABLED=true
NEXTPLAY_PROGRESS_QUEUE_CONNECTION=database
NEXTPLAY_PROGRESS_QUEUE_NAME=integrations
NEXTPLAY_ARTWORK_TRANSFER_ENABLED=true
NEXTPLAY_ARTWORK_QUEUE_ENABLED=true
```

Use **HTTPS with valid certificates** on BOTH domains; production outgoing integration refuses HTTP. Local `http://127.0.0.1:8000` and `http://127.0.0.1:8001` are supported with `APP_ENV=local`. Generate a secret using `openssl rand -hex 32`; never store it in Git, JavaScript, or screenshots. The FlowTracker receiver user must have create-order permission. For volume above 60 new orders/min adjust `NEXTPLAY_RATE_LIMIT_PER_MINUTE` and provision worker capacity appropriately, never disable API authentication.

Run `php artisan optimize:clear && php artisan config:cache` after updating `.env` on both systems.

## 3. Critical: dedicated always-on queue workers on BOTH systems

```bash
# On NextPlay server, managed by Supervisor or systemd, not a browser request:
cd /var/www/nextplay
php artisan queue:work database --queue=integrations --sleep=0 --tries=5 --timeout=70 --max-time=3600

# On FlowTracker server, a SEPARATE long-running managed worker:
cd /var/www/flowtrack
php artisan queue:work database --queue=integrations --sleep=0 --tries=7 --timeout=70 --max-time=3600
```

Set your database queue `retry_after` to at least 90 seconds (greater than worker timeout); keep workers under Supervisor or systemd with autorestart, monitor queue depth/failed jobs, and run `php artisan queue:restart` on deployments. In production prefer a monitored Redis queue if available. `--sleep=0` reduces idle worker wait; it does not guarantee network latency. If your current setup uses a different queue connection, use that connection in both `.env` and worker commands.

**Service level:** Target 1–2 sec for order delivery and small progress events on healthy infrastructure. Hard zero-delay guarantees cannot be made across two networked servers, and multi-megabyte file copies can take longer. Queue retries preserve eventual delivery; monitor time-from-checkout to FT order and FT stage-change to NextPlay callback to validate actual P95/P99 latency.

## 4. Staging verification order

1. On FlowTracker, run `php artisan nextplay:provision-workflow` and `php artisan nextplay:inspect-order <number>` as applicable.
2. On NextPlay, run `php artisan flowtrack:health`; FlowTracker health requires completed workflow and valid artwork callback base URL.
3. Place one **registered checkout** test order. Confirm matching NextPlay ID, exact quantities/prices, buyer name, private artwork and operational FT job.
4. Create a registered customer order from a **bulk quote**. Confirm a single FT job and a buyer-linked order in My Account.
5. Change an FT job's stage. Confirm NextPlay `orders.flowtrack_operational_stage`, `flowtrack_operational_status`, `flowtrack_operational_updated_at` update and the buyer sees it on account order details and verified public tracking. Verify payment state is unchanged.
6. Repeat the same checkout/order callback: no duplicate FT job, no regression to stale stage.
7. Temporarily stop a worker; restart it and verify replay without duplicate jobs/files.
8. To seed status for preexisting imported jobs: on FlowTracker run `php artisan nextplay:push-order-progress --order=NP-ORDER-NUMBER` (or `--limit=25`), with FlowTracker worker active.
9. Test unauthorized API request (401), invalid payload (422), conflicting job mapping (409), bad/tampered file checksum, rate limiting, and network failure and recovery.

## API contract

- NextPlay -> FlowTracker: `POST /api/integrations/nextplay/orders` with Bearer token and Idempotency-Key. FlowTracker persists source customer and purchased-product snapshots; order is assigned to customer-specific client code `NP-U-<source-user-id>` or hashed guest code. `/api/integrations/nextplay/health` must be ready.
- FlowTracker -> NextPlay: `POST /api/integrations/flowtrack/orders/{orderNumber}/progress` with the same server-side token, JSON fields `source_order_id`, `flow_job_id`, `status`, `stage`, `progress`, `changed_at`. Only the mapped order is updated; earlier timestamps and duplicate callbacks do not overwrite current progress. FT event observers enqueue this callback after database commit.
- FlowTracker -> NextPlay private artwork: existing token-protected, path-allowlisted file fetch + SHA-256 verification, stored privately in FlowTracker.

## Scope and limitations

This is **order-related customer and product synchronization**, not an unrelated full-catalog or full-CRM mirror. The source customer profile and individual purchased-product snapshot are synchronized as needed to fulfill each order, but changes to unpurchased products or customer profile edits unconnected to an order are not independently mirrored. Payment/refund state continues to be managed in NextPlay. 1–2s depends on both servers, queue workers, network and file sizes. No security review can honestly certify zero possible vulnerabilities; run staging integration/security tests and production monitoring first.

### Test commands

```bash
# NextPlay (requires Composer vendor/DB test setup):
php artisan test --filter=FlowTrackOrderProgressTest
php artisan test --filter=FlowTrackOrderSyncAdminTest
php artisan test --filter=GuestBulkQuoteJourneyTest

# FlowTracker:
php artisan test --filter=NextPlay
```
