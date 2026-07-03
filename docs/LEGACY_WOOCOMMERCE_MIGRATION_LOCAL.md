# Legacy WooCommerce to Laravel Catalog Migration - Local XAMPP Guide

This project now includes a staged importer for moving the old WordPress/WooCommerce catalog into the new Laravel catalog structure without directly merging the old SQL tables into the new database.

## What was compared

### Old database backup

Old WooCommerce products are spread across WordPress tables:

- `wp_posts` where `post_type = product`
- `wp_postmeta`
- `wp_terms`
- `wp_term_taxonomy`
- `wp_term_relationships`
- `wp_woocommerce_attribute_taxonomies`
- `wp_wc_product_meta_lookup`
- attachment rows in `wp_posts` where `post_type = attachment`

The provided old backup contains:

- 838 published WooCommerce products
- 4,462 media attachments
- 129 product categories
- 10,652 product tags
- 13 WooCommerce product attribute taxonomies

### New Laravel database/project structure

The new project uses normalized catalog tables:

- `products`
- `product_images`
- `categories`
- `category_product`
- `attributes`
- `attribute_values`
- `attribute_value_product`
- `category_filters`
- `product_price_tiers`
- `product_option_groups`
- `product_option_values`
- `size_option_groups`
- `size_options`
- `product_size_groups`
- `product_sizes`
- `jersey_customization_options`
- `product_shipping_methods`

## Added migration support

A new migration adds safe import support tables:

- `legacy_migration_maps` keeps old ID to new ID mappings.
- `legacy_migration_notes` records skipped or review-needed items.

It also adds product columns needed for your migration instructions:

- `products.short_specifications` stores the short visible specification set.
- `products.customization_html` stores reviewed customization/artwork content separately.
- `products.legacy_source_id` stores the old WooCommerce product ID.

## Mapping implemented

| Old WooCommerce source | New Laravel target |
|---|---|
| `wp_posts.post_title` | `products.name` |
| `wp_posts.post_name` | `products.slug` |
| `wp_posts.post_excerpt` | `products.short_description` |
| `wp_posts.post_content` | `products.description_html` |
| `_sku` | `products.sku` |
| price meta / lookup table | `products.base_price`, `product_price_tiers` |
| product category taxonomy | `categories`, `category_product` |
| product tag taxonomy | `products.tags` |
| `_thumbnail_id` | primary `product_images` row |
| `_product_image_gallery` | gallery `product_images` rows |
| WooCommerce attributes | `attributes`, `attribute_values`, `attribute_value_product`, `product_option_groups`, `product_option_values` |
| size attributes | master `size_option_groups`, `size_options`, and product `product_size_groups`, `product_sizes` |
| jersey fabric/neck/button/print attributes | `jersey_customization_options` and linked product option values |
| shipping attributes/specs | `product_shipping_methods` |

## Short specification behavior

Only these fields are used for the visible short product specification table when available:

- SKU
- Product Type
- Fabric
- Fit
- Customization
- Size Range
- MOQ
- Lead Time

Full specification rows are still stored in `products.specifications`.

## Local XAMPP database setup

Create two local databases:

```bash
mysql -u root -e "CREATE DATABASE sportswear_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE sportswear_old_wp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Import the new Laravel backup or run fresh migrations. Then import the old WordPress backup into the old database:

```bash
mysql -u root sportswear_old_wp < wordpress-database.sql
```

If you use the provided new SQL backup first:

```bash
mysql -u root sportswear_new < sportswear_ecommerce.sql
php artisan migrate
```

If you want a fresh Laravel schema instead:

```bash
php artisan migrate:fresh --seed
```

## `.env` settings for local import

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sportswear_new
DB_USERNAME=root
DB_PASSWORD=

OLD_DB_CONNECTION=legacy_wordpress
OLD_DB_HOST=127.0.0.1
OLD_DB_PORT=3306
OLD_DB_DATABASE=sportswear_old_wp
OLD_DB_USERNAME=root
OLD_DB_PASSWORD=
OLD_DB_PREFIX=wp_

LEGACY_IMAGE_MODE=link
LEGACY_LINKED_UPLOAD_PREFIX=wp-content/uploads
LEGACY_UPLOADS_PATH=/Applications/XAMPP/xamppfiles/htdocs/your-old-site/wp-content/uploads
LEGACY_IMPORT_CURRENCY=USD
```

For image linking, copy the old WordPress uploads into:

```text
storage/app/public/wp-content/uploads
```

Then run:

```bash
php artisan storage:link
```

## Safe staged import commands

Always run the dry run first:

```bash
php artisan legacy:import-woocommerce categories --dry-run
php artisan legacy:import-woocommerce products --dry-run --limit=10
php artisan legacy:import-woocommerce product-categories --dry-run --limit=10
php artisan legacy:import-woocommerce meta --dry-run --limit=10
php artisan legacy:import-woocommerce images --dry-run --limit=10
php artisan legacy:import-woocommerce attributes --dry-run --limit=10
php artisan legacy:import-woocommerce sizes --dry-run --limit=10
php artisan legacy:import-woocommerce options --dry-run --limit=10
php artisan legacy:import-woocommerce shipping --dry-run --limit=10
```

Then run staged writes:

```bash
php artisan legacy:import-woocommerce categories
php artisan legacy:import-woocommerce products --limit=20
php artisan legacy:import-woocommerce product-categories --limit=20
php artisan legacy:import-woocommerce meta --limit=20
php artisan legacy:import-woocommerce images --limit=20
php artisan legacy:import-woocommerce attributes --limit=20
php artisan legacy:import-woocommerce sizes --limit=20
php artisan legacy:import-woocommerce options --limit=20
php artisan legacy:import-woocommerce shipping --limit=20
```

After checking the frontend, import all products:

```bash
php artisan legacy:import-woocommerce all
php artisan catalog:sync-category-products
php artisan products:process-images --sync --limit=100
php artisan optimize:clear
```

## Frontend check after each stage

After categories/products/category assignment:

- Open category page.
- Confirm products appear under the right category/subcategory.
- Open product detail page.

After images:

- Confirm primary image and gallery show locally.
- If image variants are blank, run `php artisan products:process-images --sync --limit=100`.

After attributes/options/sizes:

- Check filter bar.
- Check product customization options.
- Check size option groups.
- Check jersey roster is enabled for jersey products.

## Review imported warnings

```sql
SELECT * FROM legacy_migration_notes ORDER BY id DESC LIMIT 100;
```

Use this table to review missing images, unsupported fields, or any item that needs manual approval.
