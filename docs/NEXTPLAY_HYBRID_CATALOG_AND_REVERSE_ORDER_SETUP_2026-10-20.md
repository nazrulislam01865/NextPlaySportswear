# NextPlay + FlowTracker: lightweight catalog, on-demand details and reverse orders

## Purpose and architectural boundaries

- NextPlay owns customer identities, store products, fabric/size/option rules and customer invoices/payments.
- FlowTracker keeps **only indexed product/customer summaries** in its existing `master_records` and `clients` modules. No new customer or product screens/tables are introduced. An external NextPlay ID is an identity mapping, not a second master catalog.
- Full current product configuration is retrieved from a **private API** only when an operator opens the product details or selects it in Create Order. Details are cached for at most 60 seconds and invalidated when NextPlay sends a change event. The Refresh button forces a reload.
- Each successfully created order saves its own full immutable snapshot; historical records are not altered by catalog edits.
- FlowTracker-created orders are preflighted by NextPlay and sent through an idempotent, queued server-to-server API. The customer sees the NextPlay order after successful delivery. Payments always remain pending until processed in NextPlay.
- Production stage/progress changes are pushed **back to NextPlay**, including FlowTracker-originated orders.

## Safety / known scope boundaries

**Do not enable reverse order creation for all products on a production server until the unsupported configurations are addressed and tested.** The current server rejects required artwork, required jersey roster, multi-selection groups and file/text-only option groups rather than creating incomplete customer orders. In particular, this release does **not** transfer FlowTracker-created private artwork or player roster to the NextPlay customer order. Those orders can still be created directly on NextPlay and imported into FlowTracker using the existing working flow.

The reverse-order monetary amounts are **provisional catalog tier amounts**, with `quote_invoice_requested` and `payment_status=pending`. Discounts/promotions, option surcharges, tax, shipping and other extras are **not automatically finalized** by this integration. Verify and finalize an invoice on NextPlay before collecting a payment or treating the amount as confirmed. Production tasks and shipment dates remain owned by FlowTracker; unrelated commercial updates do not automatically propagate. Product edits synchronized from FlowTracker are limited to **name and availability**; customer edits are limited to **contact name, phone and company name**. Detailed product options, fabrics, size charts and prices must be edited in NextPlay for authoritative validation; their summary and cached details are then refreshed in FlowTracker.

An HTTP request may fail due to network outages, database constraints, queued job failures or unlinked customers. The UI shows Pending/Failed/Synced status and supports retries; no implementation can guarantee an error-free or fixed-latency cross-server update.

## Install safely (local XAMPP)

1. Back up both project directories (especially `.env`, `storage`, `vendor`) and both databases. Stop the current joint launcher (`Ctrl+C`).
2. Install the **full source** from each supplied ZIP into its correct Laravel project directory. **Preserve local `.env`, `storage/`, `vendor/`, private artwork files and databases.** Never replace local `.env` with defaults or run `php artisan key:generate`.
3. NextPlay directory: `/Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear`
4. FlowTracker directory: `/Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo`
5. Confirm the existing shared integration token and base URLs still match. Set opt-in flags (below).
6. On each project run `php artisan migrate` and `php artisan optimize:clear`. On the StepPromo project, run the initial catalog import once and inspect conflicts.
7. Restart the existing joint launcher from **StepPromo**: `bash scripts/run-nextplay-local.sh` (both web servers + two integration workers). Never start a second set of workers/servers on the same ports.

### NextPlay `.env` (add the new flag; keep the previously working values)

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=http://127.0.0.1:8001
FLOWTRACK_INTEGRATION_TOKEN=YOUR_ALREADY_SHARED_SECRET
FLOWTRACK_CATALOG_EVENTS_ENABLED=true
```

### StepPromo `.env` (add the two new flags; keep existing values)

```dotenv
NEXTPLAY_SOURCE_BASE_URL=http://127.0.0.1:8000
NEXTPLAY_INTEGRATION_TOKEN=YOUR_ALREADY_SHARED_SECRET
NEXTPLAY_CATALOG_SYNC_ENABLED=true
NEXTPLAY_REVERSE_ORDER_SYNC_ENABLED=true
NEXTPLAY_ORDER_PROGRESS_ENABLED=true
NEXTPLAY_PROGRESS_QUEUE_CONNECTION=database
NEXTPLAY_PROGRESS_QUEUE_NAME=integrations
```

These base URLs must use **HTTPS in production**. The same bearer token must be kept confidential and never passed to a browser. Use your existing application queue database migration/setup, not a new database connection.

### Initial import and customer linking

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan migrate
php artisan nextplay:sync-catalog --customers
# One product refresh, if needed:
php artisan nextplay:sync-catalog --product=245
# If a previously existing FlowTracker client shares an email with a NextPlay customer,
# the importer deliberately skips auto-merging. After verifying identity, link manually:
php artisan nextplay:link-customer FLOWTRACK_CLIENT_ID NEXTPLAY_USER_ID
```

Run this once initially, then event-driven change notifications refresh summaries. Re-run periodically to reconcile missed messages or after deployment. If an import fails with a unique-code collision, review the existing `master_records` SKU mapping instead of deleting/recreating a real product. The import never bulk-deletes catalog records. Inactive or deleted NextPlay products become unavailable in FlowTracker.

### FlowTracker Create Order: supported basic happy path

1. Sign in to FlowTracker with Create Order permission. Select a **synchronized NextPlay customer**. If an existing FlowTracker customer is not mapped, use the manual link command after confirming the email.
2. Select an imported NextPlay product that does not require private artwork, a required roster, multi-select or file/text-only options.
3. The Create Order card loads fabric, size and single-value choices **once**. Fill required choices. The UI offers a Refresh button; pricing is provisional and final validation happens on NextPlay.
4. Submit the order. FlowTracker preflights the configuration and tier price with NextPlay. If rejected, it shows a validation error **before creating the FlowTracker job**.
5. If accepted, FlowTracker saves the job, marks its NextPlay state `Pending`, and queues customer-order creation. Open the job to see **Synced** and the NextPlay order number. The linked order must appear under the correct NextPlay customer's My Account.
6. Payment status on NextPlay must be `pending`; no paid flags are set. Production progress updates subsequently sync to the linked NextPlay order.
7. If it fails, review the visible error and worker logs; once corrected, use `php artisan nextplay:push-created-order FLOW_JOB_ID`. The `flowtrack:ID` idempotency key prevents duplicate NextPlay orders on retry.

### Verification and diagnostics

```bash
# NextPlay
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan flowtrack:health
php artisan test --filter=FlowTrackCatalogAuthenticationTest
php artisan queue:failed

# FlowTracker
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan nextplay:sync-catalog --customers
php artisan test --filter=CatalogApiTest
php artisan queue:failed
# after creating a test job:
php artisan nextplay:push-created-order FLOW_JOB_ID
# after stage changes:
php artisan nextplay:push-order-progress --order=NEXTPLAY_ORDER_NUMBER

tail -n 80 storage/logs/nextplay-local/steppromo-queue.log
```

**Do not run `queue:retry all` indiscriminately on a production environment.** Reconcile the specific failed job and its idempotency key before a targeted retry.

## Performance characteristics

- Product list: paginated cursor APIs (up to 100 per page) during initial sync, locally indexed lookup thereafter.
- Product detail: one authenticated payload per selected product, cached 60 seconds; no detail request per product list item.
- Sync changes: durable background queue jobs with retries; no API call inside a list-row render.
- Queue transport: the existing `integrations` queue for both applications; avoid sharing secret tokens with frontend JavaScript.
- Version comparison: optimistic checks reject stale updates instead of silent overwrites; manual repair required on conflict.
- Fixed-loop prevention: inbound refresh saves local models quietly, and NextPlay is the canonical product/customer authority.
- No mandatory full catalog duplication of rich option/specification/pricing structures.

## Test status for this packaged release

PHP syntax was checked for both projects, and the new feature tests were added. They still require your local Composer `vendor/`, configured MySQL schemas and actual running servers. Real end-to-end browser, payment, shipping, roster, artwork and multi-admin concurrency tests were **not** run in the packaging container. Enable and validate locally before releasing on the cloud.
