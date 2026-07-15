# Product Gallery and Quantity-Based Production Update

## Product Header & Gallery

- The product image input accepts multiple files in one selection.
- Supported formats remain JPG, JPEG, PNG, WebP, and AVIF.
- Maximum size remains 5 MB per image.
- Every newly selected image is previewed before the product is saved.
- Each selected image preview shows its filename and size.
- A selected image can be removed before form submission; the underlying file input is updated accordingly.
- Existing saved or remote product images now show a thumbnail beside their URL, alt text, primary-image, and remove controls.
- Existing backend storage behavior remains unchanged: all submitted files are stored as separate product gallery images.

## Production Speed by Quantity

- Production-speed rows are generated one-for-one from the Visible Storefront Pricing Table quantity ranges.
- Minimum and maximum quantity are displayed read-only in the Production & Shipping section.
- Quantity values cannot be edited in the production section.
- Adding, removing, or changing a visible pricing row synchronizes the matching production row.
- Each production row keeps only the existing useful controls:
  - Production speed name
  - Separate per-piece charge
  - Minimum production days
  - Maximum production days
  - Description
- Production rows can no longer be added or removed independently from the quantity pricing table.

## Storefront Behavior

- Production speed is no longer a customer-selectable dropdown.
- The applicable production row is selected automatically from the customer's total quantity.
- The product page shows:
  - Production speed name
  - Matching quantity range
  - Separate per-piece charge
  - Estimated production days
  - Description
- Changing size quantities immediately changes the applicable production schedule and recalculates the live price.
- Product-specific shipping methods remain unchanged.

## Pricing and Security

- The production charge remains separate from the quantity-tier base price.
- The matched production charge is added per piece to the live estimate and cart calculation.
- Laravel recalculates the applicable production row from the submitted total quantity.
- A crafted request cannot force a production row belonging to another quantity range.

## Database

Migration added:

`database/migrations/2026_06_24_000001_add_quantity_ranges_to_product_production_speeds.php`

It adds `minimum_quantity` and `maximum_quantity` to `product_production_speeds` and aligns existing production rows with existing price tiers where possible.

## Deployment

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```

The production Vite assets are already rebuilt in `public/build`.
