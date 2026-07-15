# Product frontend content + size master update

This update changes the product frontend display and adds two local-safe artisan commands.

## What changed

1. `Customization Options` now shows inside the product Description tab.
2. `Artwork & Design Guidelines` now shows inside the product Description tab.
3. `Production & Shipping` now renders as a visible shipping/production table from `products.production_table_headers` and `products.production_table_rows`.
4. Empty FAQ tab is hidden.
5. Short descriptions are cleaned on display, and a command is included to clean the DB value safely.
6. Product size groups can now inherit size labels/chart content from the master `size_option_groups` / `size_options` menu when linked.
7. Added `Toddler` to `SizeAudience`.
8. Added master size option command for `Men`, `Women`, `Youth`, `Toddler`, `Kids`, and `Unisex`.

## Updated files

Copy these files into your project:

```text
app/Services/Storefront/ProductCatalogService.php
app/Console/Commands/EnsureProductSizeMasterOptions.php
app/Console/Commands/CleanProductShortDescriptions.php
app/Enums/SizeAudience.php
resources/views/components/storefront/product/details.blade.php
FRONTEND_PRODUCT_CONTENT_SIZE_UPDATE_INSTRUCTIONS.md
```

## Local commands

From your local project root:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/sportswear-ecommerce
php artisan optimize:clear
composer dump-autoload
```

Confirm the commands are available:

```bash
php artisan list | grep -E "catalog:ensure-size|products:clean-short"
```

## 1. Confirm DB has the needed columns

```bash
mysql -u root -p nextplay -e "SHOW COLUMNS FROM products LIKE 'customization_html'; SHOW COLUMNS FROM products LIKE 'artwork_guidelines_html'; SHOW COLUMNS FROM products LIKE 'production_table_rows';"
```

If `customization_html` / `artwork_guidelines_html` do not exist, run:

```bash
php artisan migrate
```

## 2. Create master size options

Preview first:

```bash
php artisan catalog:ensure-size-master-options --dry-run
```

Create the master size groups and options:

```bash
php artisan catalog:ensure-size-master-options
```

This creates/updates:

```text
Men: XS, S, M, L, XL, 2XL, 3XL, 4XL, 5XL
Women: XS, S, M, L, XL, 2XL, 3XL, 4XL
Youth: YXS, YS, YM, YL, YXL
Toddler: 2T, 3T, 4T, 5T
Kids: 4, 5, 6, 7, 8, 10, 12, 14, 16
Unisex: XS, S, M, L, XL, 2XL, 3XL, 4XL, 5XL
```

## 3. Link existing product size groups to master groups

Preview first:

```bash
php artisan catalog:ensure-size-master-options --link-products --dry-run
```

Then apply:

```bash
php artisan catalog:ensure-size-master-options --link-products
```

This does not delete existing product charts. The storefront uses the master chart if available; otherwise it falls back to the existing product chart.

## 4. Optional: attach missing size groups

Only run this if you want products with no size group to receive an inferred master group.

Preview:

```bash
php artisan catalog:ensure-size-master-options --attach-missing --dry-run
```

Apply:

```bash
php artisan catalog:ensure-size-master-options --attach-missing
```

## 5. Clean short descriptions

Preview first:

```bash
php artisan products:clean-short-descriptions --dry-run --limit=20
```

Apply to all products:

```bash
php artisan products:clean-short-descriptions
```

This creates a backup table before changing DB values:

```text
product_short_description_cleanup_backups
```

## 6. If customization/artwork columns are still empty

If you have not split the migrated mixed `description_html` content yet, preview first:

```bash
php artisan products:split-migrated-content --dry-run --limit=10
```

Then run only after the preview is correct:

```bash
php artisan products:split-migrated-content
```

The product page will show `customization_html` and `artwork_guidelines_html` inside the Description tab.

## 7. Clear cache and test

```bash
php artisan optimize:clear
php artisan view:clear
php artisan serve
```

Open a product page and check:

```text
1. Product description is clean.
2. Customization Options appears under Description.
3. Artwork & Design Guidelines appears under Description.
4. Production & Shipping table appears when production_table_rows exists.
5. FAQ tab is hidden if product_faqs is empty.
6. Size groups show Men/Women/Youth/Toddler/Kids/Unisex options from master data when linked.
```
