# Product Fulfillment Tab Update

Added a product-level fulfillment rich text field in the admin product form and a new **Fulfillment** tab on the storefront product details card.

## Changed files

- `database/migrations/2026_07_04_000001_add_fulfillment_html_to_products.php`
- `app/Models/Product.php`
- `app/Http/Requests/Admin/ProductFormRequest.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Services/Storefront/ProductCatalogService.php`
- `resources/views/admin/products/_form.blade.php`
- `resources/views/components/storefront/product/details.blade.php`
- `resources/views/admin/products/show.blade.php`

## Deployment

Run:

```bash
php artisan migrate --force
php artisan optimize:clear
```

The frontend product page uses Blade only for this change, so a Vite rebuild is not required unless you also change CSS/JS.
