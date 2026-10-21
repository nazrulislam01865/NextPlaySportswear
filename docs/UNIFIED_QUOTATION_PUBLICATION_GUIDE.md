# Unified quotation publication — FlowTracker / NextPlay

## What changed

This update merges two **publication triggers** into the same formal NextPlay quotation workflow; it does not delete the NextPlay Admin quotation form.

- **FlowTracker → NextPlay:** Attach an approved PDF/image/spreadsheet to the `Send Offer to Client` inquiry task and complete it. The existing integration transfers the file privately, verifies its SHA-256 checksum, and now automatically creates the customer-visible formal quotation version in NextPlay. It emails the buyer using the existing centralized email service. Re-running the same manifest is idempotent.
- **NextPlay Admin → FlowTracker:** Publish from the existing `Prepare & Send Quotation` form. NextPlay creates the formal version with the entered price/quantity/terms. A queued, bearer-authenticated callback sends the published files to the linked FlowTracker `Send Offer to Client` task, then completes that task using the current taskflow service. If already completed, the task is not completed again.
- **Customer:** The existing Track Quote, review, request changes, confirm quotation, and order-conversion processes remain. File-only quotations can be reviewed, returned for changes, and confirmed immediately.

### Important: numerical pricing

The FlowTracker task stores a **quotation file**, but currently does **not** store the structured final quantity, subtotal, customization charge, and shipping charge needed by NextPlay's order-creation service. It would be unsafe to invent numbers or automatically parse pricing from PDF artwork.

For a FlowTracker-origin publication, the file is the formal offer and is published in a **file-only** mode. The customer can download/review, request changes or confirm the quotation. The registered customer's **Confirm & Create Order** action stays unavailable until real structured pricing and quantity have been entered in NextPlay Admin's existing form. Publishing a priced revision is allowed and resets the customer's decision for re-review. NextPlay Admin can alternatively start with those values and publish in a **single click**, which completes FlowTracker's task as well. No fake $0 quotes/orders are created.

## Prerequisites

Deploy **both** updated applications together, back up databases and storage, and preserve `.env` and uploaded files. No new database migration is needed beyond the prior offer/file-transfer migrations. Confirm both apps have run their pending migrations.

FlowTracker `.env`:

```dotenv
NEXTPLAY_OFFER_SYNC_ENABLED=true
NEXTPLAY_SOURCE_BASE_URL=http://127.0.0.1:8000
NEXTPLAY_INTEGRATION_TOKEN=the_same_private_token_as_nextplay
NEXTPLAY_RECEIVER_USER_ID=existing_authorized_flowtracker_user_id
NEXTPLAY_BULK_QUEUE_CONNECTION=database
NEXTPLAY_BULK_QUEUE_NAME=integrations
```

NextPlay `.env`:

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=http://127.0.0.1:8001
FLOWTRACK_INTEGRATION_TOKEN=the_same_private_token_as_flowtracker
FLOWTRACK_QUEUE_ENABLED=true
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
```

Use HTTPS URLs in production. Never disclose integration tokens in screenshots or client-side code. The FlowTracker receiver user must have permission to complete Inquiry tasks.

## Deploy locally

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
php artisan migrate
php artisan optimize:clear
php artisan queue:restart
php artisan test --filter=UnifiedQuotePublicationTest

cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
php artisan migrate
php artisan optimize:clear
php artisan queue:restart
php artisan test --filter=NextPlayPublishedQuotationEndpointTest
```

If your XAMPP project has permission problems, fix ownership of `bootstrap/cache` and `storage` first, **without using chmod 777**. Start both web servers and integration queue workers using your existing `scripts/run-nextplay-local.sh` launcher.

## Verify the whole flow

1. Submit a NextPlay Bulk Quote. Open its linked FlowTracker inquiry.
2. Upload a supported offer to the `Send Offer to Client` task and complete it. Let the `integrations` queues process the event.
3. Verify the matched NextPlay Bulk Quote has `quotation_version > 0`, `quotation_sent_at` set, an active `quotation` file, and customer-visible Review / Request Changes / Confirm Quotation actions.
4. Retry the same FlowTracker event. Confirm **no duplicate quotation version or file** was added. An already-confirmed version must not be republished.
5. On a different linked request, publish a priced quote **directly from NextPlay Admin**. Confirm the existing FlowTracker task becomes Completed and the published file appears in its task attachments. Retry the callback and verify no duplicate task documents.
6. Verify a priced quote can still be confirmed and converted to an order with an authenticated customer account. Verify file-only quotes do not allow unpriced orders.

For a previously completed FlowTracker task (e.g. inquiry ID 159), use the existing one-time retry command, **not** recurring polling:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan nextplay:retry-quotation-offer 159
```

## Operations and troubleshooting

- FlowTracker queue: `storage/logs/nextplay-local/steppromo-queue.log`.
- NextPlay queue: `storage/logs/nextplay-local/nextplay-queue.log`.
- Laravel logs: `storage/logs/laravel.log` in each project.
- Failed jobs: `php artisan queue:failed` in each project.
- Integration delivery relies on the existing private bearer-token endpoints, checksum verification and queued retry/backoff. No per-second sync polling was added.
- If FlowTracker task completion fails after a NextPlay Admin publication, check the configured receiver user's task-edit permission and the private file scanner/permissions; the publication and files remain safely in NextPlay, and the failed queued callback can be retried.

## Rollback

Restore the pre-update project files for both applications and restart queue workers. Existing quotation versions and private files remain in storage/database. Do not run destructive database resets.
