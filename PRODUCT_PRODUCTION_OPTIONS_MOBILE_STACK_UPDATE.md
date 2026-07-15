# Product Production Options Mobile Stack Update

Implemented for the admin Add/Edit Product page.

## Changes

- Added a dedicated `np-production-table-wrap` class to the production options table wrapper.
- On small screens, production option columns now stack vertically instead of expanding horizontally.
- Added production option header fields as full-width mobile cards.
- Each quantity range row becomes a mobile-friendly card.
- Each production offer cell stacks below the previous offer cell on mobile.
- Prevented horizontal overflow for production option inputs and description textareas.
- Updated the built admin CSS asset and Vite manifest for cache-busted deployment.

## Files touched

- `resources/views/admin/products/_form.blade.php`
- `resources/css/admin.css`
- `public/build/manifest.json`
- `public/build/assets/admin-production-mobile-cca0a9d2.css`
