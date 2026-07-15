# Product Content Accordion Editor Update

## Summary
The product add/edit content section now uses one clean admin card named **Product Content** with three internal accordion-style editor sections:

1. Description
2. Customization & Artwork
3. Fulfillment

Each section has its own toolbar and content area inside the same Product Content card, similar to a WordPress accordion/metabox layout.

## Backend compatibility
The update does not change the database structure and does not merge stored data into one column. Each section still saves to the existing fields:

- `description_html`
- `customization_artwork_html`
- `fulfillment_html`

This keeps the existing storefront product tabs and old products working without migration.

## Files changed
- `resources/views/admin/products/_form.blade.php`
- `resources/views/components/admin/tabbed-rich-editor.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-CKTWdRZv.css`

## Deployment
No database migration is required. Clear Laravel caches after replacing files:

```bash
php artisan optimize:clear
```
