# NextPlay My Account + Admin Artwork Review Update

## Changes

- Shared My Account sidebar is more compact: shorter rows, smaller icons, tighter profile card, less prominent active-state weight, and less vertical whitespace.
- General My Account page headings and shared account cards use a smaller, lighter typography scale. Scope excludes the four customer artwork prototype bodies, which retain their original designs.
- Admin **Commerce > Artwork Approvals** shows the newest proof per order item, with status and keyword filters, pagination, proof view, order/customer/product details and feedback preview.
- Admin **Commerce > Orders > View Order > Customer Artwork Approvals** remains the primary management screen. Admins with `orders.manage` may upload initial/revised image or PDF proofs, view older versions, read customer feedback/references/messages and reply to the customer. Orders with a closed state cannot be published to.
- Admin users with only `orders.view` may inspect the review queue and view proofs, but cannot upload or send designer replies.
- No new database tables/columns or separate auth scheme. Uses existing private artwork proof storage and transactional email methods.

## Installation (existing Laravel project)

1. Back up the project and database. Extract the **changed-files patch** over the matching project root, keeping existing files not in the patch untouched.
2. From project root:

   ```bash
   php artisan migrate --force
   php artisan optimize:clear
   npm ci
   npm run build
   php artisan test --filter=ArtworkApprovalWorkflowTest
   ```

   `migrate` ensures the artwork proof tables from the previous release are installed; this update adds **no** new migrations. On local development, `php artisan migrate` without `--force` is fine.
3. In Admin, open **Commerce > Artwork Approvals** to find incoming reviews or revisions. For first-time proofs, open **Orders > View Order > Customer Artwork Approvals** to upload the initial file.
4. Test as both an order manager (upload/reply) and a support agent (view only), then validate the My Account pages at desktop and mobile widths.

## Technical limits and security

- The four customer-facing artwork screens are not redesigned in this update: only the global account sidebar changes on those routes.
- New admin queue uses one latest-version subquery, eager loads order/item records and uses pagination to avoid N+1 queries.
- Order and file ownership, private-storage streaming, upload MIME/size validation, one-time customer decisions and emailing remain in existing services/controllers.
- There is no automatic payment/order-production status change caused by this update.
- PHP syntax checks pass. The uploaded archive does not include `vendor/` so full Laravel integration tests could not be executed in this environment. Run the test command above before deploying.
