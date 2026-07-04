# Product Content Tabbed Editor Update

Implemented a single visible rich text editor for the add/edit product Product Detail section while keeping the existing database fields separate.

## What changed

- Replaced the three separate editors for:
  - `description_html`
  - `customization_artwork_html`
  - `fulfillment_html`
- Added one combined tabbed editor UI:
  - Description
  - Customization & Artwork
  - Fulfillment
- Each tab loads into the same rich editor surface.
- Switching tabs saves the current tab content into its own hidden field before loading the next tab.
- Form submit also syncs the active editor tab into the correct hidden field.
- Existing storefront/product tab logic remains unchanged.

## Files changed

- `resources/views/admin/products/_form.blade.php`
- `resources/views/components/admin/tabbed-rich-editor.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-CKTWdRZv.css`

## Deployment

No database migration is needed. No npm build is required because the built admin CSS was updated.

Run after replacing files:

```bash
php artisan optimize:clear
```
