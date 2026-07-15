# Product Production Options Mobile Column Layout Fix

## Request
On the add/edit product page, production option columns should not appear as a separate header list on small devices. Each production option column must become its own complete mobile block below the previous option.

## Implemented
- Kept the desktop production options table for wider screens.
- Added a mobile-only column-by-column editor.
- Each production option now appears as a separate card on small devices.
- Inside each production option card, every quantity range and its matching production offer fields are shown together.
- Newly added production option columns now stack below the first production option instead of spreading to the right.
- Removed the previous mobile layout that separated production option headers from quantity/offer fields.
- Preserved the same Alpine data bindings and form field names, so saving/editing continues to work with the existing backend.

## Files changed
- `resources/views/admin/products/_form.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-production-mobile-cca0a9d2.css`

## Deployment
No database migration is required.

Run after replacing files:

```bash
php artisan optimize:clear
```
