# NextPlay Order Management Implementation

## Scope

The customer order templates are now implemented as Laravel pages backed by durable database records. The implementation covers:

1. Customer Orders dashboard
2. My Orders / Order History
3. Authenticated Order Details
4. Pay for Order
5. Retry Failed Payment
6. Order Again / Reorder
7. Cancel Order Request
8. Change Order Request
9. Shipment Details
10. Partial / Split Shipments
11. Return Request
12. Exchange Request
13. Return History
14. Return Details and Status
15. Refund Status
16. Credit Note preview and PDF download
17. Secure Invoice preview and PDF download
18. Order Downloads for private digital files

The pages reuse the existing NextPlay customer-account shell and navy/red storefront design. The selection-card CSS issue from the review templates is fixed through the shared `.order-choice` styles.

## Backend architecture

The migration `2026_06_23_000001_create_order_management_tables.php` creates a compact order domain:

- `orders` and immutable `order_items` snapshots
- `order_payments`
- `order_status_histories`
- `order_shipments` and `order_shipment_items`
- `order_change_requests`
- `order_return_requests`, `order_return_items`, and private `order_return_attachments`
- `order_refunds`
- `order_credit_notes`
- `order_downloads`

Checkout now persists an order and its line-item snapshots inside a database transaction. Session checkout data remains only for the existing confirmation experience; the customer account reads durable order records.

Business operations are kept in `App\Services\Order\OrderWorkflowService`. PDF generation is isolated in `App\Services\Order\OrderPdfService`. Controllers remain thin and use Form Requests for validation and authorization.

## Customer workflow behavior

- Customers can access only orders linked to their authenticated customer account.
- Payment attempts are idempotent and provider-neutral. No raw card number, CVV, or unencrypted payment credential is accepted or stored.
- Reorder rebuilds the cart from the historical order while forcing current catalog pricing and availability to be rechecked.
- Cancellation and change requests never alter production automatically; authorized staff must review them.
- Shipment allocations prevent the same quantity from being allocated twice.
- Shipment state drives order fulfillment and delivered quantities.
- Return and exchange windows default to 30 days after delivery.
- Active return/exchange requests reserve their quantities so the same units cannot be requested more than once.
- Return and exchange evidence supports up to five privately stored images, 5 MB each.
- Return, shipment, and request state transitions are validated server-side.
- Refund totals cannot exceed confirmed paid payments.
- Issued refunds generate a credit note and control the order payment status.
- Digital downloads support activation, expiry, limits, locking, and atomic download counting.

## Admin controls

Only active `super_admin` and `admin` users can access order operations. Catalog managers remain limited to catalog work and cannot access customer order, address, payment, return, or evidence data.

The admin panel now includes:

- Order search, status filters, and order details
- Payment, order, and fulfillment status management
- Shipment creation and shipment status updates
- Cancellation and change-request review
- Return and exchange review
- Refund status and provider-reference management
- Automatic credit-note creation for issued refunds
- Private customer evidence downloads
- Private order-download upload, limits, expiry, and removal
- Order status history and customer-visible support notes
- Order and return dashboard metrics

## Security controls

- Customer ownership policies are applied to orders, shipments, returns, refunds, credit notes, evidence, and downloads.
- `order.manager` middleware enforces least-privilege admin access.
- All write actions use CSRF protection, Form Requests, validation, and throttling on sensitive endpoints.
- Invoice, credit-note, customer-evidence, and digital-download links use authenticated authorization; customer document links are short-lived signed URLs.
- Private files use the `local` disk rather than `public/storage`.
- File type, file count, and file size are validated. Stored names are random; original display names are sanitized.
- Failed database writes clean up newly stored files to avoid orphaned private data.
- Checkout, payment attempts, shipments, returns, refunds, and download counts use transactions and row locks where concurrency matters.
- Order line items preserve the purchased product name, SKU, prices, image, and customization as historical snapshots.
- Account and document pages are marked `noindex, nofollow`.
- Raw card data is never stored in this application.

## Performance controls

- Operational columns and common filters are indexed in the migration.
- Account and admin lists are paginated.
- Relationships are eager loaded on detail pages to avoid avoidable N+1 queries.
- Shared Blade components reduce repeated markup.
- Vite production assets are included in `public/build`.
- The implementation uses one order workflow service rather than duplicating business rules across controllers.

## Installation or update

From the project root:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env                 # only for a new installation
php artisan key:generate             # only when APP_KEY is empty
php artisan migrate --force
npm install
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Writable directories:

```bash
chmod -R 775 storage bootstrap/cache
```

Optional commerce settings:

```env
COMMERCE_RETURN_WINDOW_DAYS=30
COMMERCE_EXCHANGE_WINDOW_DAYS=30
```

## Payment-provider boundary

The project did not contain configured Stripe, PayPal, or another payment-provider SDK/credentials. The implementation therefore records secure, idempotent payment attempts and keeps an order unpaid until an authorized provider confirmation is received.

Before accepting live card or PayPal payments, add a provider adapter and verified webhook handler that:

- redirects or creates a provider-hosted checkout session;
- validates webhook signatures;
- maps provider event IDs idempotently;
- marks the matching `order_payments` record paid or failed;
- updates the order only after verified provider confirmation;
- records safe failure codes/messages without storing sensitive payment data.

The admin payment update exists for reconciled offline, invoice, or manually verified payments; it is not a replacement for live webhook verification.

## Production recommendations

- Queue customer emails for payment, proof, shipment, return, refund, and download events.
- Use S3-compatible private object storage for larger files and add malware scanning.
- Define evidence/download retention and deletion policies.
- Add provider webhook tests once a payment provider is selected.
- Run database backups before the migration on an existing production site.

## Verification performed

- All project PHP files passed syntax linting.
- All 164 Blade views compiled successfully.
- The complete route table registered successfully, including the customer and admin order routes.
- The Vite production build completed successfully.
- `tests/Feature/OrderManagementTest.php` covers ownership, signed invoices, admin permissions, shipment state behavior, returns/evidence, and limited private downloads.

The feature suite could not be executed in the packaging container because its PHP CLI lacks `dom`, `mbstring`, `xml`, `xmlwriter`, and a PDO database driver. Run the tests on the target development/server environment after installing Laravel's required PHP extensions:

```bash
php artisan test --filter=OrderManagementTest
```
