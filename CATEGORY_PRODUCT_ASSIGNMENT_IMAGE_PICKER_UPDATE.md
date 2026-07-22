# Category Product Assignment Image Picker Update

Implemented for the admin category product assignment page.

## What changed

- Added product thumbnails to the **Add existing products to this category** list.
- The picker now loads product images with the assignable product query to avoid extra per-row image queries.
- Kept the product name, SKU/slug, current category label, and status beside the thumbnail.
- Updated desktop and mobile responsive styling so rows remain clean with images.
- Aligned the assignable list limit with the form validation limit of 100 products.

## Files changed

- `app/Http/Controllers/Admin/CategoryProductController.php`
- `resources/views/admin/categories/products.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-CategoryProductManagement.css`
