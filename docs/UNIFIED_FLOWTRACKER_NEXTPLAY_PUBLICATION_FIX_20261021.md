# FlowTracker / NextPlay — one quotation publication action

## What was wrong

NextPlay had a valid offer manifest and even a `flowtrack_offer` database row, but its private JPG was missing from the local disk. That left the quote at `quotation_version = 0`, so the customer could see an 'Offer File Available' timeline but not the formal quotation review/decision flow. A job could previously finish after staging without reporting that publication was still missing.

## New behavior

- Completing **Send Offer to Client** in FlowTracker sends the completed task's private file manifest to NextPlay, where an event-driven queue job transfers and verifies the files and **publishes the formal quotation in the same process**.
- **Publish Quotation** from NextPlay Admin remains available and uses the same publication service; its published files sync back to the linked FlowTracker task.
- A repeated completed-task event cannot create a duplicate quotation version for the same manifest. Customer review/feedback/confirmation use the existing routes.
- On receiving the *same* manifest again, NextPlay checks the referenced private files. Missing ones invalidate the stale ready flag, and the queue job securely re-downloads them from the completed FlowTracker task, verifies checksum and size, then publishes.
- The job logs `flowtrack.quotation_publication_processed` with `formally_published: true`. It now fails explicitly when files are staged but publication did not occur. No polling, new migration, or automatic guesswork for quotation prices is added.
- A document-only formal quotation enables customer review, change requests, and confirmation. **Creating an order still requires actual structured quantity and price**; missing prices are never fabricated.

## Deploy safely on macOS

Back up both databases and project directories. **Do not replace either application's `storage/` folder, `.env`, or uploaded files.** The ZIPs intentionally omit runtime storage/cache files.

Apply the matching small patch from each project root using `unzip -o /path/to/PATCH.zip -d .`. Only application source files should be overwritten.

FlowTracker:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
php artisan optimize:clear
php artisan queue:restart
```

NextPlay:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
php artisan optimize:clear
php artisan queue:restart
```

Restart `scripts/run-nextplay-local.sh` after stopping old web/worker processes. Both integration workers must consume the `integrations` queue.

## Repair an older offer already stuck at file staging

This is necessary **once** for old offers from before the updated transfer job was installed; new task completions publish automatically.

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan nextplay:retry-quotation-offer 159 --check
php artisan nextplay:retry-quotation-offer 159
```

FlowTracker inquiry 159 is linked to NextPlay quote 7 (`BQ-20261021-ALWC60`) in the provided logs. The source task must still be completed and contain the original physical quotation file.

Check the NextPlay queue worker log from the launcher and NextPlay Laravel logs. Look for `flowtrack.quotation_publication_processed`, `formally_published: true`. If the job fails, inspect `php artisan queue:failed` in NextPlay and *do not* forcibly update `quotation_version` or `quotation_sent_at` by hand.

Read-only verification:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan tinker --execute='$q = \App\Models\BulkQuoteRequest::findOrFail(7); dump(["version" => $q->quotation_version, "sent_at" => $q->quotation_sent_at, "published_files" => $q->quoteFiles()->where("category", "quotation")->where("version", $q->quotation_version)->count(), "ready" => \App\Support\FlowTrackQuoteCustomerProgress::published($q)]);'
```

Expected: `version >= 1`, non-null `sent_at`, `published_files >= 1`, `ready = true`. Then the **Review Quotation**, **Send Feedback**, **Request Changes** and **Confirm Quotation** actions appear on the matching customer reference, without an additional admin publication step. Confirm & Create Order requires real numeric quantity and price.

## Focused regression tests

NextPlay:

```bash
php artisan test --filter=FlowTrackQuotationOfferSyncTest
php artisan test --filter=UnifiedQuotePublicationTest
php artisan test --filter=VerifiedFlowTrackOfferRecoveryTest
```

FlowTracker existing integration tests:

```bash
php artisan test --filter=NextPlayPublishedQuotationEndpointTest
```

Tests require installed Composer dependencies and a configured isolated test database. The generated archives have been checked for PHP syntax and ZIP integrity, **not** live database-to-database transfer.
