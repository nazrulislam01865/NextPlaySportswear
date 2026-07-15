# Dynamic Product Feature → Storefront Filter Update

## What changed

The **Catalog Attributes & Storefront Filters** section on the Add/Edit Product page is now connected to product-specific choice features.

When an administrator creates a choice-based product feature such as:

- Color swatches
- Fabric image choices
- Collar buttons
- Print or imprint checkboxes
- Dropdown choices
- Any other image, swatch, button, dropdown, or checkbox feature

and enables **Use as storefront filter**, the feature and its active values appear immediately in the Catalog Attributes section as an auto-synced preview.

After the product is saved, the backend:

1. Creates or reuses a catalog attribute using the feature code.
2. Creates or updates reusable attribute values from the feature values.
3. Copies color HEX codes and the primary option image into the filter value.
4. Assigns the generated values to the product.
5. Adds the attribute to every category containing the product.
6. Clears the category facet cache through the existing product save flow.

## Assignment rules

- **Customer customizable:** every active choice is assigned to the product filter.
- **Fixed/static:** only the fixed/default active value is assigned.
- **Hidden:** never synchronized as a filter.
- **Text, textarea, number, date, and file inputs:** not eligible because they do not produce stable storefront facet choices.
- **Inactive features or values:** not synchronized.

## Admin interface

The Product Features section now includes:

- `Use as storefront filter`
- `+ Customer Color Choices` preset
- Immediate filter preview in the Catalog Attributes section
- Automatic hiding of the duplicate reusable attribute card while its product feature is controlling it

Administrators may still select unrelated reusable attributes manually.

## Database change

Migration added:

```text
2026_06_23_000003_link_product_options_to_catalog_filters.php
```

New `product_option_groups` columns:

- `use_as_filter`
- `catalog_attribute_id`

## Deployment

```bash
php artisan optimize:clear
php artisan migrate
npm install
npm run build
php artisan optimize:clear
```

Do not run `php artisan migrate:fresh` on an existing database.

## Existing products

Existing product features remain unchanged and are not automatically made filterable. Edit a product, enable **Use as storefront filter** for the required features, and save it. New choice-based features enable this option by default.
