# Payment Information — Editable status table

Admin: **Store → Payment Information Page → Payment status table**.

- The editor displays data in a table, one payment status per row.
- **Add row** creates an empty row; **Add column** creates a new editable column heading and an empty cell in each row. Use **Remove** to delete a row or a column.
- The first cell of every row and every column heading must be filled; other cells can be blank. Maximum 100 rows and 10 columns.
- Save using the existing **Save Payment Information Page** button. The storefront table displays the same column order and values and scrolls horizontally if needed.
- Previously saved two-column status/meaning data is automatically converted when read, preserving all existing text. No database migration is required.
- This informational table does not affect checkout, payment gateway integrations or actual order payment statuses.

Changed files:
- `app/Services/Storefront/PaymentPageService.php`
- `app/Http/Requests/Admin/PaymentPageRequest.php`
- `resources/views/admin/payment-page/edit.blade.php`
- `resources/views/storefront/content/payment.blade.php`
- `public/js/nextplay-payment-table-editor.js`
- `public/css/nextplay-payment-editor.css`
- `public/css/nextplay-payment-page.css`
- `tests/Feature/Storefront/PaymentInformationPageTest.php`

After deploying files: `php artisan optimize:clear`. No migration.
