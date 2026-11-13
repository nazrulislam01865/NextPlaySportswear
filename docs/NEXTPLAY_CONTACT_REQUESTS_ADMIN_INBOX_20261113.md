# NextPlay Admin — Contact Requests Inbox

## Scope

Adds a permission-controlled **Contact Requests** section under Commerce, alongside Orders and Bulk Quote Requests. Reuses the site's shared admin buttons, fields, filter bar, cards, table, pagination, and layout. The storefront Contact Us form, stored original messages, email sending, confirmation forwarding, and existing Contact Us page editor are unchanged.

## Features

- Filter/search by name, email, phone, order number, status, and topic; show the newest requests first.
- Four compact stats: total, new, in review, resolved/closed.
- View a full contact request: identity, contact details, submitted topic/order reference, decrypted message, upload metadata, and private attachment download.
- Support team can move a request among New / In Review / Resolved / Closed and save an encrypted internal note. No-op submissions skip unnecessary updates.
- "Reply by email" opens the configured desktop email application. This does **not** send from NextPlay or auto-update status; it is intentionally not presented as an in-app email service.
- Private attachment endpoints require `contact_requests.view`; the previous `/contact-page/messages/.../attachment` address remains functional for permitted support staff and forwarded email links.

## Performance

- The inbox lists only needed columns, omitting encrypted `message` and `internal_note`; those are loaded only on the detail page.
- Paginated listing, default 25 per page, newest ID first; composite `(status, id)` / `(topic, id)` indexes help filtered lookups.
- One grouped aggregate query computes the four counters; pagination performs its standard result and count queries.
- Searching substring matches over email/name/phone/order number may scan records under large datasets; switch to an appropriate full-text/search index after measuring production query plans. Do not attempt substring LIKE on the encrypted message column.
- No polling or extra frontend JavaScript. Unrelated routes do not query contact records; the sidebar uses its existing permission-check mechanism.

## Roles and permissions

- Separate `contact_requests.view` and `contact_requests.manage` permissions; the migration syncs grants without overriding existing role matrix choices.
- Default access: Super Admin and Admin; Order Manager and Support Agent (view/manage). Content Manager is not granted access just because it can edit the public Contact Us page.
- Files remain under the existing private local disk; never served as public media.

## Installation

Apply the changed files to the existing project and run:

```sh
php artisan migrate
php artisan optimize:clear
php artisan test --filter=ContactRequestsInboxTest
php artisan test --filter=StorefrontContentPagesTest
php artisan test --filter=ContactAttachmentAndWhatsappVisibilityTest
php artisan test --filter=AutomaticConfirmationForwardingTest
php artisan test --filter=ForwardedEmailLayoutTest
php artisan view:cache
```

When deploying with Tailwind/Vite assets and compiling from source, also run `npm ci && npm run build` according to your existing release process. Access: `/admin/contact-requests`.

## Verification

1. Submit a contact form and confirm it appears at the top of the inbox.
2. Open it; check submitted values, original message and private file download.
3. Change status and save an internal note. Reload and ensure it persists.
4. Test the email link with a configured mail app (no auto-send).
5. Verify authorized support staff can see the page, while Content Manager and anonymous visitors cannot access the inbox or attachments.
6. Verify the storefront contact form and existing forwarding emails still behave as before.

### Deployment note

`vendor/` is absent from the uploaded archive, so full PHPUnit/Laravel request tests must run in the user's Composer-installed environment. Static PHP checks are run on changed source files before packaging.
