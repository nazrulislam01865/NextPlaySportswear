# Product Jersey Customization Master Assignment Update

## Scope

The Add/Edit Product customization section now consumes reusable values from **Master Data → Jersey Customization Options**.

## Admin flow

1. Select **Add New Feature**.
2. Choose one supported feature name:
   - Neck and Collar
   - Fabric
   - Color
   - Sleeves and Cuffs
   - Jersey Style
3. The feature card is created with a customer input-style selector.
4. Select **Add Item**.
5. The item picker displays only active master-data options belonging to the selected feature type.
6. Select one or more items. The same master option cannot be selected twice in a feature.
7. Configure each selected item:
   - Additional charge
   - Charge basis: included, per piece, or fixed per order
   - Default choice

## Master-data snapshots

Product option values retain a reference to the master option and save a product-level snapshot of its name, description, color, and images. Uploaded master images are copied into the product option media directory so later master-data changes do not unexpectedly change an existing product.

## Backend safeguards

- Feature names are normalized from the allowed Jersey customization types.
- Selected option IDs must exist and be active.
- Selected option types must match their parent feature.
- Duplicate master option IDs are rejected.
- Label, code, description, color, and images are loaded from the database rather than trusted from browser fields.
- Existing product values without a master-data reference remain compatible.

## Database

Migration added:

`2026_06_24_000004_link_product_options_to_jersey_customization_master.php`

It adds:

- `product_option_groups.jersey_customization_type`
- `product_option_values.jersey_customization_option_id`

Explicit short index and foreign-key names are used for MySQL compatibility.

## Deployment

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```
