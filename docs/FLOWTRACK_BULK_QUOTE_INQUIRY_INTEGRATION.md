# NextPlay Bulk Quote -> FlowTrack Inquiry integration

A customer bulk quote is always committed to `bulk_quote_requests` first. FlowTrack delivery happens only after that local record exists, so a FlowTrack outage cannot lose or roll back a valid NextPlay request.

## Stored locally in NextPlay

The existing form fields remain first-class columns. The integration migration additionally stores:

- `user_id` when the requester is signed in;
- `customer_account` as an allow-listed account snapshot;
- `flowtrack_sync_status` and attempt count;
- FlowTrack Inquiry ID and Inquiry number after acceptance;
- last attempt and successful-sync timestamps;
- the latest integration error and accepted response.

Passwords, auth/session state, roles and other sensitive user fields are never copied into `customer_account`.

## Receiver endpoints

NextPlay sends JSON to:

`POST /api/integrations/nextplay/inquiries`

Health check:

`GET /api/integrations/nextplay/inquiries/health`

Both use the same server-side Bearer token already used by order integration.

## Attachment transfer

If the quote has an attachment, the outgoing JSON contains metadata, a SHA-256 checksum and this constrained callback path:

`GET /api/integrations/flowtrack/bulk-quotes/{bulkQuote}/attachment`

The callback is protected by `VerifyFlowTrackIntegration`. FlowTrack copies the file into its own private Inquiry storage and never follows an arbitrary URL from the payload.

## Local testing

```bash
php artisan migrate
php artisan optimize:clear
php artisan flowtrack:inquiry-health
```

Submit the storefront bulk quote form, or resend an existing request:

```bash
php artisan flowtrack:sync-inquiry BQ-20260909-ABC123
```

For immediate local visibility use `FLOWTRACK_QUEUE_ENABLED=false`. For production, enable the integration queue and keep a worker running.
