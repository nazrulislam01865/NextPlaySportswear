# NextPlay Sportswear — Artwork Approval Implementation

## Customer experience

All four prototype body screens are implemented under the existing My Account header, theme, typography and sidebar (not a second sidebar):

- `/account/artwork-approvals`: current proof for each order item, counts, status filter and pagination.
- `/account/artwork-approvals/{proof}`: high-resolution image/PDF review, version history, checklist, designer notes, approval/change/rejection actions, and optional designer messaging.
- `/account/artwork-approvals/{proof}/request-changes`: revision notes, change type, optional private annotated PDF/image, priority and remaining revisions.
- `/account/artwork-approvals/{proof}/approved`: immutable confirmation of an approved version, with order link, download and order-stage display.

No sample proof/order data is inserted by this change. Only administrator-uploaded proofs belonging to an authenticated customer are shown.

## Admin workflow

1. Visit **Admin > Orders > View Order**, scroll to **Customer Artwork Approvals**.
2. For each order item, select the proof image or PDF, optionally provide a design name and designer notes, and select **Publish First Proof**.
3. The customer will see the proof in **My Account > Artwork Approvals** and receive a transactional email when the configured email service is enabled.
4. The customer can approve it, request changes (with optional reference file), reject it, or send a designer message. The admin order page shows all feedback, reference files, conversation history and statuses. The configured Orders email recipient receives customer decisions/messages.
5. To issue a revision, upload a new proof for that order item. The version number is allocated atomically and prior versions remain available.

The approved proof is recorded **independently** of the order's production workflow. The approval action does not force payment to `paid` or change the `orders.status` field to `in_production`/override FlowTrack. The confirmation page shows "In Progress" only if the existing order workflow has actually entered that stage. Order status transitions must remain controlled by the existing order management/FlowTrack integration.

## Security and performance

- Proof and reference files use Laravel's `local` **private** storage disk with authenticated, authorized streaming endpoints and `no-store` response headers.
- Customer endpoints use the existing `auth:web`, `customer`, `verified` middleware and `OrderPolicy::view` ownership checks; admin actions require `orders.manage`.
- Uploads are type- and size-limited (admin proof: 20 MB, revision reference: 10 MB). Names are sanitized for download headers; forms use CSRF protection and rate limits.
- Approval/revision/rejection are one-time transitions of the latest pending proof only. Transactions and per-order-item row locks prevent racing revisions or stale approvals. Files are removed if database writes fail.
- Counts/list are based on latest version per order item using an indexed grouped subquery; order/item relations are eager-loaded, with pagination.
- Non-critical notifications use the existing `TransactionalEmailManager` / `EmailService` safely queued path.

## Deployment and checks

From your project root, **back up the live code and database**, then apply the code, run:

```bash
php artisan migrate --force
php artisan optimize:clear
npm ci
npm run build
php artisan test --filter=ArtworkApprovalWorkflowTest
```

Do not run `migrate:fresh` or seed/replace the existing orders. Restart your application/queue process only if your existing deployment routine requires it. The latest uploaded archive is source-only; to execute Artisan tests locally you must have Composer dependencies installed (`composer install`) and a configured test database/environment.

Optional `.env`: `STOREFRONT_ARTWORK_MAX_REVISIONS=2` (default 2; set 0 to disable revision limits). Email notifications require your existing transactional email settings and `EMAIL_ORDERS_ADDRESS` or the configured fallback.

## Supported proof formats

The admin can publish one original PNG, JPG, WebP or PDF per version. The actual uploaded format is shown/downloaded without lossy conversion. If a PDF is uploaded, it displays in the browser's PDF preview; if a PNG/JPG/WebP is uploaded, it displays as a responsive image. The original file is always downloadable. Auto-conversion between image and PDF was deliberately not added because it would require additional image/PDF processing and storage.
