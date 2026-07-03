# Product Content Split - Local Instructions

This update adds a safe one-time cleanup command for migrated WooCommerce product content.

It moves/cuts sections from `products.description_html` into cleaner columns:

- `detail_information_html`
- `customization_html`
- `artwork_guidelines_html`
- `upgrade_options_html`
- `care_instructions_html`
- `ordering_notes_html`
- `how_to_order_html`

It also creates `product_content_split_backups` so the old values can be restored.

## Local DB name

Use your local imported database name:

```bash
DB_DATABASE=nextplay
```

## Commands

```bash
php artisan optimize:clear
php artisan migrate
php artisan products:split-migrated-content --dry-run --limit=10
php artisan products:split-migrated-content --dry-run --product-id=1
php artisan products:split-migrated-content --limit=10
php artisan products:split-migrated-content
php artisan optimize:clear
```

## Restore if needed

Preview restore:

```bash
php artisan products:split-migrated-content --restore --dry-run --limit=10
```

Restore all changed products from backup:

```bash
php artisan products:split-migrated-content --restore
```

## Useful MySQL checks

```sql
SELECT
    id,
    sku,
    CHAR_LENGTH(short_description) AS short_len,
    CHAR_LENGTH(description_html) AS description_len,
    CHAR_LENGTH(detail_information_html) AS detail_len,
    CHAR_LENGTH(customization_html) AS customization_len,
    CHAR_LENGTH(artwork_guidelines_html) AS artwork_len,
    CHAR_LENGTH(care_instructions_html) AS care_len,
    CHAR_LENGTH(ordering_notes_html) AS notes_len,
    CHAR_LENGTH(how_to_order_html) AS how_to_order_len
FROM products
ORDER BY id
LIMIT 20;
```

```sql
SELECT COUNT(*) AS backup_count FROM product_content_split_backups;
```
