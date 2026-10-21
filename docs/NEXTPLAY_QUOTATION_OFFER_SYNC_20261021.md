# FlowTracker → NextPlay: quotation offer-file synchronization

## Scope

Only the **Send Offer to Client** inquiry task (exact title, case-insensitive) triggers this integration. When that task is Completed, FlowTracker sends a small offer manifest over the existing authenticated NextPlay API; a NextPlay queue job downloads the task's eligible documents from a private FlowTracker endpoint, verifies SHA-256, and stages the files privately in the matching Bulk Quote. No schedules or polling are added.

When an offer task is reopened, or its task documents are removed, FlowTracker queues a manifest refresh that clears **unpublished** staged offer files. Quotation files **already published** on NextPlay are copied into immutable quotation-version records and are never removed by staging reconciliation.

Only NextPlay Admin may publish a quotation. It still sets quantity, subtotal, customization, shipping, terms and validity, and clicks **Publish Quotation & Notify Customer**. The transferred files are selected by a default-on checkbox, or the admin can use their own uploads. No status, pricing, payment, or email is automatically changed just because the FlowTracker task completed.

## Deployment

Back up **both databases and projects** first. Apply both updated archives to their matching Laravel projects, preserving `.env`, `vendor/`, writable `storage/`, and user-uploaded files. Do **not** merge project directories or databases. Both sides must be deployed before testing.

On **NextPlay**:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan migrate
php artisan optimize:clear
php artisan queue:restart
php artisan test --filter=FlowTrackQuotationOfferSyncTest
```

NextPlay has **one new migration** (`2026_10_21_180000_add_flowtrack_offer_manifest_hash_to_bulk_quotes.php`). It adds `flowtrack_offer_manifest_hash` and `flowtrack_offer_ready_hash`, which prevent incomplete transfers from appearing as ready-to-publish.

On **FlowTracker**:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan optimize:clear
php artisan queue:restart
php artisan test --filter=NextPlayOfferDispatchRegressionTest
```

FlowTracker has **no new migration** for this change.

### Configuration

Reuse the **same private bearer token** already configured for order and bulk-quote synchronization. In FlowTracker `.env`:

```dotenv
NEXTPLAY_REVERSE_BULK_SYNC_ENABLED=true
NEXTPLAY_SOURCE_BASE_URL=http://127.0.0.1:8000
NEXTPLAY_INTEGRATION_TOKEN=<matching-secret>
NEXTPLAY_BULK_QUEUE_CONNECTION=database
NEXTPLAY_BULK_QUEUE_NAME=integrations
```

In NextPlay `.env`:

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=http://127.0.0.1:8001
FLOWTRACK_INTEGRATION_TOKEN=<matching-secret>
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
```

Production base URLs **must use HTTPS**. Restart queue workers after deploying. Your existing launcher for the two local Laravel projects may start both `integrations` queues. If starting manually in separate terminals (with working web servers already started), use the project's configured queue connection and `--queue=integrations --sleep=1`. Do not run any every-second HTTP polling scripts.

## End-to-end test

1. Submit a Bulk Quote from NextPlay (guest or registered), and verify it has a linked FlowTracker Bulk Order / inquiry record.
2. In FlowTracker open **Inquiry Taskflow → Send Offer to Client**. Upload a **PDF, PNG, JPG, XLS or XLSX** of **10 MB or less**, then set the task status to **Completed** (a configured required-file task may complete automatically when the document is uploaded).
3. Let the FlowTracker `integrations` worker send its manifest. Let the NextPlay `integrations` worker download and validate the file. In NextPlay Admin open **Bulk Quote Requests → the matching quote → Prepare & Send Quotation**.
4. Confirm **Use offer files received from FlowTracker** lists the actual original filename and is checked. Open the file from the Admin document list. Before publishing, check the customer's quote review: the newly transferred staged file must **not yet be visible**.
5. Enter the quotation quantity, prices, payment terms and validity, then click **Publish Quotation & Notify Customer**. The quotation must show the transferred file without any second upload.
6. Check a duplicate completion/retry does not duplicate staged files; check editing/reopening the task removes only unpublished staged files, **not previously published quotation versions**. Try to send the same file to a different NextPlay quote ID: the API must refuse mismatched FlowTracker inquiry links.

If a task had already been Completed **before** this update, there is intentionally no background scan. On FlowTracker, one-off manual dispatch from `php artisan tinker` can enqueue that existing task:

```php
\App\Jobs\PushNextPlayQuotationOffer::dispatch(ACTUAL_TASK_ID)
    ->onConnection(config('nextplay.reverse_bulk_orders.queue_connection', 'database'))
    ->onQueue(config('nextplay.reverse_bulk_orders.queue_name', 'integrations'));
```

Replace `ACTUAL_TASK_ID` with the actual **Inquiry Task** database ID, not an order ID. The job itself verifies task title, source record and NextPlay quote linkage.

## Troubleshooting

Check `php artisan queue:failed` and `storage/logs/laravel.log` on **both** applications. If the file does not appear in NextPlay Admin, first confirm the task is Completed and the FlowTracker documents are actually attached **to that task** (not just to the inquiry), that there is a valid linked NextPlay quote ID, and that both `integrations` workers are running. The supported offer formats are the same as the existing NextPlay quotation publisher (PDF, XLS, XLSX, PNG, JPG, JPEG; up to 8 files and 10 MB each). Other documents must be converted in FlowTracker or manually uploaded in NextPlay Admin.

A successful queue operation requires more than an accepted manifest. The NextPlay admin form only enables staged files after the **entire** file transfer has completed. No custom scheduler, public file URL, cross-application database access, or new frontend package is introduced.

## Verification limitation

Changed PHP files have been syntax-checked and the archives integrity-checked. The uploaded source archives do not include Composer `vendor/` directories or running databases, so Laravel's feature suite and live two-server file transfer **have not been executed here**. Run the focused tests above and the end-to-end test on your local installation before production deployment.
