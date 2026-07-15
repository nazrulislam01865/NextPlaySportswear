# Product Pricing, Size and Artwork Flow Update

## Scope

This update keeps the existing NextPlay storefront and admin design, but changes the product configuration flow so pricing, sizes, artwork and product options follow one consistent source of truth.

## Customer product page

### Product header

The former **Starting At / Start Customizing / Request Bulk Quote** price card was removed.

The same position now displays the actual product **Quantity Price Table**, followed by the existing:

- Start Customizing button
- Request Bulk Quote button

The unit price shown during configuration is selected from the price tier that matches the customer’s total quantity across all size groups.

### Sizes and quantities

The size selector now displays only:

- Size
- Quantity controls
- Total selected pieces

Removed from the size rows:

- Unit estimate
- Line estimate
- Per-size price adjustments

Sizes come only from the size groups configured by the administrator. A size does not carry its own selling price. The total quantity selects the applicable quantity tier.

### Product features

All selectable customer features are displayed together in one **Choose Product Features** section.

The separate **Decoration & Print Details** customer section was removed. Print locations, imprint choices, collars, fabrics, colors, surcharges and similar options are represented by the existing product feature groups when the administrator configures them.

### Artwork upload

The legacy artwork-method cards were removed from the customer product page.

The customer now sees one clean artwork-upload section when enabled by the administrator. It supports:

- One or multiple artwork files
- Configurable title and description
- Configurable required/optional behavior
- Configurable maximum file count
- Configurable maximum size per file
- Configurable accepted file extensions
- Selected-file preview list

Artwork upload does not add a price. Product price is determined by the quantity price tier, together with any explicitly configured product-option, production or shipping adjustments.

Files are stored privately through Laravel. Direct public storage paths are not exposed in the cart or order pages.

## Admin Add/Edit Product page

The original design, Tailwind components and field styling were preserved.

The top-level sections were rearranged to match the customer journey:

1. Basic
2. Media
3. Pricing
4. Product Features
5. Sizes
6. Artwork
7. Production & Shipping
8. Details
9. Filters
10. SEO

### Product Features

The option groups for colors, fabrics, collars, print locations, imprint choices and other customer selections are managed together. The unnecessary option-group page-section selector was removed from the visible form; groups are saved to the unified product-feature section.

### Sizes

The admin still controls all size groups, labels, rows and size charts. The section now clearly states that size quantities determine total order quantity and that the quantity tier determines the unit price.

### Artwork

A dedicated **Custom Artwork Upload** section was added with controls for:

- Show/hide artwork upload
- Required/optional upload
- Customer-facing title
- Customer-facing instructions
- Maximum files
- Maximum size per file
- Accepted extensions
- Live admin preview

Legacy artwork methods are removed when the product is saved so they cannot conflict with the new upload-only flow.

## Pricing behavior

The trusted backend calculation now follows this order:

1. Sum quantities across every selected size.
2. Select the matching quantity price tier.
3. Use that tier’s unit price.
4. Add trusted product-option adjustments.
5. Add trusted production adjustments.
6. Add trusted product-specific shipping adjustments.

Client-submitted prices, size price deltas and artwork prices are not trusted.

## Cart and order behavior

- Multiple artwork files can be submitted.
- File count, size and extension are validated against the product settings.
- Uploaded files are stored privately.
- Cart items preserve safe artwork metadata.
- Order-item snapshots preserve the original file names, MIME types and sizes.
- Customer cart and order pages list artwork file names without exposing private filesystem paths.
- Legacy single-artwork metadata remains readable for compatibility with older cart data.

## Database migration

Added:

`database/migrations/2026_06_23_000004_add_artwork_upload_settings_to_products.php`

New product fields:

- `artwork_upload_enabled`
- `artwork_upload_required`
- `artwork_upload_title`
- `artwork_upload_description`
- `artwork_upload_max_files`
- `artwork_upload_max_file_size_mb`
- `artwork_upload_accepted_types`

The migration enables the new upload section for products that already had active legacy artwork methods, preserving their apparent storefront intent.

## Deployment

```bash
cd /var/www/nextplay

git pull origin main

composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force

npm install
npm run build

sudo chown -R www-data:www-data /var/www/nextplay
sudo chmod -R 775 storage bootstrap/cache

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `php artisan migrate:fresh` on an existing installation.

## Validation performed

- PHP syntax validation passed for 194 PHP files.
- 165 Blade templates compiled and generated PHP passed syntax validation.
- JavaScript syntax validation passed.
- 173 application routes loaded successfully.
- Vite production build completed successfully.
- A functional cart test confirmed quantity-tier pricing, ignored size price deltas and multiple-artwork metadata preservation.

The complete PHPUnit suite could not run in the packaging environment because its PHP installation lacks the DOM/XML and PDO database extensions.
