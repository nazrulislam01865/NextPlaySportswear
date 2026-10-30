# NextPlay: automatic confirmation forwarding (no database changes)

## What changed

In **Admin → Email Customization → Forwarding Settings**, save one to five recipients and a default message. New storefront **orders** and **bulk quote requests** now send a **separate, automatic confirmation-forward email** to those saved addresses. Orders created by accepting an existing bulk quotation are covered as well. The existing customer and internal emails are unchanged, and staff may still forward any existing request manually.

Saving a blank recipients field disables automatic forwarding. Existing saved recipients in `storage/app/private/email/forwarding-settings.json` remain valid; there is no need to save again if the file is already present on the server.

Each automatic email uses the existing forwarding logic and includes order/quote details and available original attachments, subject to the mail provider's size limits. E2A allows up to 10 attachments and approximately 9 MB source files per confirmation email. Oversized/missing attachments cause forwarding to fail without sending an incomplete message; errors are logged as `Automatic confirmation forwarding failed.` in `storage/logs/laravel.log`.

## When it sends

The new trigger is connected to `TransactionalEmailManager::orderPlaced()`, `bulkQuoteReceived()`, and `bulkQuoteConverted()`, all invoked **after the new record is saved**. For HTTP requests, forwarding occurs *after the response* in the current PHP process. This avoids a database queue job and does not require a running queue worker. Artisan/CLI calls send directly. An email provider failure never rolls back an already-saved order or quote. Duplicate checkout attempts still follow the existing order idempotency rules.

Only newly created orders/quotes are forwarded automatically. Existing orders and quotes can be sent from their details screens; this patch does not send a bulk of old emails.

## Install locally

Overwrite the modified files in the project, preserving the existing `.env`, storage directory, and database. Run:

```bash
php artisan config:clear
php artisan view:clear
php artisan test --filter=AutomaticConfirmationForwardingTest
```

Then save recipients in Email Customization → Forwarding Settings and submit a **new** test quote and order. Check both incoming mail and `storage/logs/laravel.log` for `admin.confirmation-auto-forward` or `Automatic confirmation forwarding failed.`. Do not reprocess the existing `jobs` table.

## Files changed

- New `app/Services/Email/AutomaticConfirmationForwarder.php`
- Updated `app/Services/Email/TransactionalEmailManager.php`
- Updated `app/Services/Email/ConfirmationForwarder.php`
- Updated `app/Services/Email/ForwardingSettings.php` (comment only)
- Updated `app/Http/Controllers/Admin/ConfirmationForwardController.php`
- Updated `resources/views/admin/email-customization/forwarding.blade.php`
- New `tests/Feature/AutomaticConfirmationForwardingTest.php`

No migrations, tables, new queue jobs, or additional Composer or npm dependencies.
