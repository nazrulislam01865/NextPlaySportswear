# NextPlay ↔ FlowTracker bulk-order integration (2026-10-21)

## What is implemented

- **NextPlay → FlowTracker:** Original bulk-quote submission continues to create one FlowTracker bulk-order/inquiry record. Subsequent material field changes and customer messages dispatch integration jobs **after database commit**. The FlowTracker receiver updates the existing source snapshot instead of creating a second inquiry. Registered customers are mapped by their stable NextPlay user IDs, not by company-name coincidence.
- **FlowTracker → NextPlay:** Creating a published FlowTracker bulk order dispatches a queued server-to-server upsert. The NextPlay receiver verifies the existing, active **NextPlay customer account** (`nextplay_user_id` of the linked customer, or an exact registered account email if no link exists), creates a customer-visible bulk quote once, and links it to the FlowTracker inquiry ID. Later corrections in FlowTracker go to that same quote.
- **NextPlay edits to a FlowTracker-created bulk order:** Changed NextPlay customer/quote fields are sent back into the existing FlowTracker source snapshot, without creating a duplicate or overwriting FlowTracker's local corrections.
- **Files:** NextPlay's initial uploaded attachment and subsequent customer feedback files are transferred from a **bearer-token-protected** NextPlay endpoint into FlowTracker private document storage. FlowTracker-uploaded bulk-order documents transfer from a **bearer-token-protected** FlowTracker endpoint into NextPlay private quote storage (category `client_attachment`, not published quotation). File IDs and SHA-256 checksums prevent accidental mismatches and repeats. New NextPlay initial uploads are private; legacy public uploads are still readable through the admin/integration controller. The sender never supplies an arbitrary absolute URL to be fetched.
- **No polling loop:** Events queue jobs after commits, with per-record overlap protection, backoff and Laravel's existing failed-jobs support. Remote systems fetch files only for changed / missing file IDs. A new file manifest supersedes an older queued one. Nothing scans both databases every second.

## Operational boundaries

- NextPlay handles **customer-facing quote status, quotation pricing, publication, payment and order conversion**. FlowTracker retains its **operational workflow, tasks, assignment, and source-specific local edits**. This intentionally avoids syncing unrelated workflow/quotation states as the same status.
- FlowTracker-created requests **must map to an active NextPlay customer** to appear in that customer's account. An unmatched contact is saved safely in FlowTracker but the outbound job fails with a clear validation error. Do not auto-create a NextPlay login or assign an unknown person's quote to somebody else's account.
- Original NextPlay **guest** submissions still enter FlowTracker. They remain guest quotes in NextPlay, and FlowTracker can edit their existing request record without silently linking an unrelated user.
- Existing NextPlay customer feedback/attachment metadata is retained in the FlowTracker snapshot's `raw_payload` (allow-listed fields). Uploaded bytes become `inquiry_documents`. NextPlay customer-facing access to quotations and feedback retains its original authorization rules; imported FlowTracker documents are not published quotations.
- File copying happens asynchronously: an accepted JSON payload is not proof that bytes have already arrived. Check both queue/failed-job statuses and the actual stored documents before declaring end-to-end sync complete.

## Step 1 — back up and deploy

Back up each database, both projects' `.env` files, and uploaded storage. Deploy **each full project to its matching server**, preserving its original `.env`, `storage`, user uploads and database. Do not copy one project's files into the other. These packages contain source code, not Composer's `vendor` dependencies. In each application root:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

### NextPlay `.env`

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=https://YOUR-FLOWTRACK-DOMAIN
FLOWTRACK_INTEGRATION_TOKEN=THE-SAME-STRONG-PRIVATE_TOKEN_ON_BOTH_SYSTEMS
FLOWTRACK_QUEUE_ENABLED=true
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
```

### FlowTracker `.env`

```dotenv
NEXTPLAY_INTEGRATION_TOKEN=THE-SAME-STRONG-PRIVATE_TOKEN_ON_BOTH_SYSTEMS
NEXTPLAY_SOURCE_BASE_URL=https://YOUR-NEXTPLAY-DOMAIN
NEXTPLAY_REVERSE_BULK_SYNC_ENABLED=true
NEXTPLAY_BULK_QUEUE_CONNECTION=database
NEXTPLAY_BULK_QUEUE_NAME=integrations
NEXTPLAY_INQUIRY_ATTACHMENT_TRANSFER_ENABLED=true
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
```

On FlowTracker, configure a receiving account (`NEXTPLAY_RECEIVER_USER_ID` or `NEXTPLAY_RECEIVER_USER_EMAIL`) that has permission to create bulk-order inquiries, and ensure the NextPlay inquiry workflow and its active Task Packs are provisioned. Enable the existing NextPlay workflow and provisioning settings used by your FlowTracker environment. Both servers must be able to access each other's HTTPS API URLs (not `localhost` if in different machines).

## Step 2 — queue workers (event-driven, not cron polling)

Use Supervisor (or an equivalent service manager) with a **persistent** worker on each app. Include `integrations` plus other queues already required by the project; do not replace existing workers. Example worker process command in each application root:

```sh
php artisan queue:work database --queue=integrations --sleep=1 --tries=6 --timeout=120 --max-time=3600
```

`--sleep=1` is merely the worker's idle queue wait; it does **not** query NextPlay/FlowTracker each second and does not perform a remote synchronization loop. Keep `DB_QUEUE_RETRY_AFTER` longer than `--timeout` (180 seconds versus 120 seconds in this example), to avoid duplicate processing during slow transfers. Use `php artisan queue:restart` and restart Supervisor after deployment. Check `php artisan queue:failed` for exhausted attempts.

## Step 3 — smoke tests (both directions)

1. **NextPlay registered customer:** Submit a new bulk quote with an attachment. It should appear once in FlowTracker, linked to the matching customer. Confirm the attachment is downloadable privately as an inquiry document.
2. **NextPlay guest:** Submit another bulk quote with an attachment. It should appear in FlowTracker once and not be linked to an unrelated NextPlay account.
3. **NextPlay change:** Update quote details; send feedback and upload a file where the customer flow supports it. Verify the existing FlowTracker inquiry snapshot updates, a *new* client document appears exactly once, and no new inquiry is created.
4. **FlowTracker new bulk order:** Select a linked NextPlay customer, workflow, requested quantities/size/customization/shipping fields and upload 2 files; create the request (not a draft). It should appear once in that customer's NextPlay **My Account → Bulk Quotes** with the same contact/request data. Verify the imported file records are privately stored on NextPlay.
5. **FlowTracker correction:** Edit contact, product/quantity, date, shipping and customization sections. Confirm the *same* NextPlay quote is updated, without changing its customer ownership, pricing or published quotation.
6. **NextPlay update to that linked quote:** Change a customer/quote field in NextPlay and verify FlowTracker's original local snapshot updates. Workflow tasks remain untouched.
7. **Idempotency:** Resend a quote twice and repeat the file job; confirm one inquiry, one quote, and one imported copy per source file ID.
8. **Outage:** Briefly stop the destination worker or HTTP server. Create a request, restore service and confirm job retries, plus check failed jobs and logs. Test invalid bearer token, mismatched customer ID and mismatched checksums are rejected.

## Diagnostics

```sh
php artisan optimize:clear
php artisan queue:failed
php artisan queue:work database --queue=integrations --sleep=1 --tries=6 --timeout=120
```

Original NextPlay source metadata and customer feedback are retained in FlowTracker's `ecommerce_inquiry_snapshots`, attached bytes in `inquiry_documents`, and new remote bulk quotes in NextPlay's `bulk_quote_requests` and `bulk_quote_files`. Use source IDs to inspect one record. **Do not run a repeated scheduler that resends every quote**, and do not enable unauthenticated document URLs.

## Testing limitations

The project ZIPs in this session have no Composer `vendor` directory or running databases. Syntax and static validation are possible, but **PHPUnit/Laravel integration tests, migrations against the live schema, queue delivery, file transfers, server connectivity, and a real two-way smoke test have NOT been executed** here. Run the numbered smoke tests in staging before production release. Keep database backups and a rollback copy of both deployed code directories.
