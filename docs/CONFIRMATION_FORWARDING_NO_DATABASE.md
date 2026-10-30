# Admin confirmation forwarding (no database changes)

## Where to use it

1. Admin > Email Customization > Forwarding Settings: save optional default recipients (up to five) and a default message.
2. Admin > Orders > open an order > Forward for confirmation: enter addresses, review the note, and send.
3. Admin > Bulk Quote Requests > open a request > Forward for confirmation: same form.

Only admins with `orders.manage` can send. Only admins with `email_customization.manage` can modify defaults.

This is **manual**, never automatic. It sends the order or bulk quote data and original stored files to the specified recipient(s), not to the customer. This action does not change the order/quote status.

## What is attached

- Complete `.txt` details summary for each order or bulk quote; details also appear in the email body.
- Order: existing invoice PDF, original product artwork, personalization uploads, artwork proofs, revision reference uploads, and saved order downloads.
- Quote: original request upload and quote/message/quotation files saved with the request.

The message includes a link to the matching admin details screen (requires admin authentication).

## Safe limits and failure handling

A single forward is limited to 30 attachments and 14 MB original payload (or up to 9 MB and 10 attachments when E2A is configured). If a file is missing or exceeds the provider-specific limit, **nothing is sent** and an inline error is displayed. To avoid a false 'sent' confirmation in development, `log` and `array` mail transports are rejected. Real delivery requires the existing `TRANSACTIONAL_EMAIL_MAILER` or `MAIL_MAILER` configuration (SMTP, E2A, etc.). Provider errors are reported to the admin.

## Persistence / deployment

Defaults are saved to Laravel's private local storage disk as `email/forwarding-settings.json` (usually `storage/app/private/email/forwarding-settings.json`). This introduces **no migration, new database table, or DB write**. The application web user must be able to write to storage. In multi-server deployments, share this private storage file between nodes if you want the defaults on every node.

No new `.env` variables, Composer package, or frontend build are required. After updating files, run `php artisan view:clear` and `php artisan optimize:clear`.

### Testing checklist

- Save defaults, reopen Forwarding Settings, and confirm persistence.
- Try forwarding one order and one bulk quote to a controlled mailbox using your configured real mailer.
- Confirm summary files, invoice PDF (orders), and uploaded documents appear.
- Try an invalid email and confirm no message is sent.
- Try an intentionally oversized attachment and confirm the send is blocked with an error.
- Confirm a read-only admin cannot send.

## E2A compatibility correction (2026-10-30)

E2A expects `to`, `cc` and `bcc` to contain recipient objects (`[{"email":"..."}]`), not RFC 5322 address strings. Older versions of the NextPlay E2A transport sent string arrays, which produced `HTTP 400, invalid_request` even after an API key was fixed. The transport now constructs these objects and sends a plain email for `reply_to`. Its Laravel HTTP-fake regression test covers two recipients and a file attachment.

The forwarding-settings page **only saves defaults**; it is not an automatic-forwarding rule. To send, open Orders or Bulk Quote Requests, choose one request, and use **Forward for confirmation**. The section opens automatically on validation or provider failure so the inline error is visible.

Provider-level `HTTP 401, unauthorized` is independent of forwarding code. Reissue an invalid E2A key in the E2A dashboard and configure `E2A_API_KEY` in the local `.env` (never in source files). Check that the configured key has no stray `>` character. Real delivery requires a valid account and provider permissions.

No migrations, tables, model fields, queue jobs, or scheduled tasks were added. Existing settings are retained in `storage/app/private/email/forwarding-settings.json`.
