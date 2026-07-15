# Order management migration recovery

## Fixed issue

MySQL limits database identifiers (including index names) to 64 characters. Laravel's generated index name for `order_return_attachments(order_return_request_id, created_at)` was 65 characters.

The migration now uses this explicit index name:

```php
$table->index(
    ['order_return_request_id', 'created_at'],
    'return_attachment_request_created_idx'
);
```

## Important: clean the partial migration before retrying

The failed migration was not written to Laravel's `migrations` table, but MySQL may already have created some order-management tables. `migrate:rollback` cannot remove those partial tables.

Back up the database, then drop only the new order-management tables in reverse dependency order. Do not run `migrate:fresh` on an existing project because it removes all application tables and data.

Run this from the project root:

```bash
php artisan tinker --execute="
use Illuminate\\Support\\Facades\\Schema;
Schema::disableForeignKeyConstraints();
foreach ([
    'order_downloads',
    'order_credit_notes',
    'order_refunds',
    'order_return_attachments',
    'order_return_items',
    'order_return_requests',
    'order_change_requests',
    'order_shipment_items',
    'order_shipments',
    'order_status_histories',
    'order_payments',
    'order_items',
    'orders',
] as \$table) {
    Schema::dropIfExists(\$table);
}
Schema::enableForeignKeyConstraints();
"
```

Then run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan optimize:clear
php artisan migrate:status
```

The order-management migration should show as `Ran`.
