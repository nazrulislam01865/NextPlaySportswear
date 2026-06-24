# Product Configuration Backend Update

## Scope

This update extends the existing product system instead of replacing it. Existing products continue to use the standard profile and existing catalog, pricing, cart, checkout, order, SEO, and media flows.

## Admin product profile

The Add/Edit Product page now includes a product profile:

- Standard product
- Jersey / team uniform
- T-shirt / apparel
- Other customizable product

The per-piece player roster is available only when the product profile is `Jersey / team uniform`.

## Flexible product features

An administrator can add any product-specific feature, including:

- Fabric
- Static or selectable color
- Imprint
- Collar
- Pocket
- Print position
- Packing
- Surcharge
- Any newly named custom feature

Every feature has an independent storefront mode:

- `Customer customizable`
- `Fixed / static`
- `Hidden`

Supported inputs include image choices, color swatches, buttons, dropdowns, checkboxes, text, long text, number, file, and date.

Feature values support:

- Included / no charge
- Per-piece charge
- Fixed order charge
- Active/inactive state
- Default or fixed choice
- Optional stock override
- Description
- Color value
- Up to 12 images per value

The Add Product page includes shortcuts for Fabric With Images, Imprint Checkboxes, Static Color, and a completely custom feature.

## Product-specific sizes and size charts

Administrators can create any number of size groups, such as Adult, Youth, Women, Kids, or a custom group. Each group controls its own available sizes.

A size chart can be supplied as:

- A table entered by the administrator
- An uploaded image
- A remote image URL
- Both a table and image

Only active size groups and enabled charts appear on the customer product page.

## Jersey roster

For jersey products, the administrator can enable a per-jersey roster and decide whether it is optional or required.

The administrator controls the fields shown for every jersey. Default examples are:

- Player name
- Player number
- Front text or position
- Back text or position

Additional short fields can be added. Each field can be enabled, disabled, required, optional, and assigned a maximum length.

When the customer selects 15 jerseys across the size inputs, the storefront generates exactly 15 roster rows. Each row retains the selected size and accepts only the fields enabled by the administrator. The backend rebuilds the row list from the submitted size quantities, so customer-side manipulation cannot change the trusted sizes.

Per-item roster entry is limited to 250 pieces in a single configured cart line to protect performance and request size.

## Product shipping methods

Each product can enable or hide its own shipping methods. Every method supports:

- Name and code
- Customer description
- Included, per-piece, or fixed-order charge
- Minimum and maximum estimated working days
- Default method
- Active/inactive state

When product-specific shipping is enabled, the generic cart shipping formula is not applied to that item.

## Backend protection

The Laravel backend:

- Recalculates all option and shipping prices from database records
- Ignores customer-submitted prices
- Enforces fixed feature values
- Ignores hidden or unknown features
- Accepts only active values assigned to the product
- Validates required customizable features
- Reconstructs jersey roster rows from valid size quantities
- Validates required roster fields
- Stores complete configuration data in cart and order snapshots
- Restricts uploaded image and artwork MIME types and sizes
- Uses database transactions for product writes
- Keeps admin authorization through the existing product administration routes

## Main files added or updated

- `database/migrations/2026_06_23_000002_extend_product_configuration.php`
- `app/Models/ProductShippingMethod.php`
- `app/Models/Product.php`
- `app/Models/ProductOptionGroup.php`
- `app/Models/ProductOptionValue.php`
- `app/Models/ProductSizeGroup.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Requests/Admin/ProductFormRequest.php`
- `app/Http/Requests/Storefront/AddCartItemRequest.php`
- `app/Services/Storefront/ProductCatalogService.php`
- `app/Services/Cart/CartService.php`
- `resources/views/admin/products/_form.blade.php`
- `resources/views/components/storefront/product/builder.blade.php`
- `resources/views/components/storefront/product/option-group.blade.php`
- `resources/js/app.js`
- `tests/Feature/ProductConfigurationBackendTest.php`

## Local installation

Back up the database, then run:

```bash
php artisan optimize:clear
php artisan migrate
npm install
npm run build
php artisan optimize:clear
```

Do not use `php artisan migrate:fresh` on a database containing existing project data.

## Production deployment

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
