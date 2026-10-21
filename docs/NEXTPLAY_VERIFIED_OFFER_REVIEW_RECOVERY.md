# NextPlay: restore full customer quotation review for an already verified FlowTracker offer

## Cause

`Send Offer to Client` previously transferred one file as `flowtrack_offer`, but a staged file does not set `quotation_version` or `quotation_sent_at`. The customer tracking view deliberately hides Review Quotation, Request Changes and the formal decision pages until these publication fields are present.

The unified publication integration now handles **new** completed task transfers automatically, but a file verified **before** the unified publication deployment may need a one-time publication action. This update adds a targeted recovery command which calls the **same UnifiedQuotePublisher** used by new FlowTracker completions and NextPlay Admin. It does not create a separate publication workflow.

## Install

Back up NextPlay project, database and uploads before applying this update. Apply the PATCH to the **NextPlay** project directory, keeping existing `.env`, `storage`, and `vendor` unchanged. No FlowTracker source changes or database migrations are required for this targeted repair if earlier migrations are already applied.

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan optimize:clear
php artisan queue:restart
```

## Recovery for the example request

FlowTracker inquiry 159 is linked to **NextPlay quote ID 7**, reference `BQ-20261021-ALWC60`.

Read-only check:

```bash
php artisan flowtrack:publish-verified-offer 7 --check
```

If it reports a ready, verified private offer (or already published), run:

```bash
php artisan flowtrack:publish-verified-offer 7
```

The operation is safe to retry. If a formal quotation already exists it makes no changes; if the offer isn't fully verified it refuses to publish. It does not invent subtotal, total, prices or quantities from file content.

Verify stored formal publication:

```bash
php artisan tinker --execute='
$q = \App\Models\BulkQuoteRequest::findOrFail(7);
dump([
    "reference" => $q->reference,
    "quotation_version" => $q->quotation_version,
    "quotation_sent_at" => $q->quotation_sent_at,
    "published_files" => $q->quoteFiles()->where("category", "quotation")->where("version", $q->quotation_version)->count(),
]);
'
```

Expected for first successful publication: `quotation_version = 1`, a non-null `quotation_sent_at`, and one `quotation` file. Subsequent runs must not create another version.

Open the matching quote tracking page after customer authentication:

`http://127.0.0.1:8000/bulk-quote/track/BQ-20261021-ALWC60`

The **Review Quotation** and **Send Feedback** links should be visible and the review page should show **Confirm Quotation Only** and **Request Changes**. An unpriced, file-only quotation cannot create an order: **Confirm & Create Order** requires a real quantity and positive, structured quotation price. Prices can be entered later using the existing NextPlay Admin form, without duplicating the workflow.

## New offers and alternative entry point

New FlowTracker completed tasks continue through the normal event-driven, checksum-verified transfer job, which calls the same `UnifiedQuotePublisher` automatically. NextPlay Admin's Publish Quotation remains the alternative trigger for the same publication workflow. No polling was added.

## Focused tests

```bash
php artisan test --filter=VerifiedFlowTrackOfferRecoveryTest
php artisan test --filter=UnifiedQuotePublicationTest
```

Full Laravel tests and live browser behavior require the app dependencies and running local database/servers; they have not been executed in this packaging environment.
