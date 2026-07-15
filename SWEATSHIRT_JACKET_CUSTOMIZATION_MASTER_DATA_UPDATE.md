# Sweatshirt and Jacket Customization Master Data Update

## Added master-data menus

### 1.12 Sweatshirt Customization
- Sweatshirt Color
- Sweatshirt Fabric
- Sweatshirt Neck
- Sweatshirt Sleeve
- Sweatshirt Cuff
- Sweatshirt Pocket
- Sweatshirt Hem
- Sweatshirt Style / Fit
- Size Options

### 1.13 Jacket Customization
- Jacket Color
- Jacket Outer Fabric
- Jacket Inner Fabric / Lining
- Jacket Type
- Jacket Closure
- Jacket Collar / Hood
- Jacket Sleeve
- Jacket Pocket
- Jacket Cuff
- Jacket Hem
- Size Options

Shipping Methods is now numbered **1.14** in the Master Data sidebar.

## Data isolation

Customization items remain isolated by their exact master-data type. For example:

- `sweatshirt_color` and `jacket_color` are independent types.
- The same name/slug, such as `Black`, can exist once for Sweatshirt and once for Jacket.
- Product configuration only loads options whose exact type matches the selected feature.
- Sweatshirt and Jacket size groups are stored with different `customization_group` values.
- The same size-group slug can exist separately under Sweatshirt and Jacket.
- Newly uploaded master-data images are stored under a family-specific path such as:
  - `catalog/customization-options/sweatshirt/{option-id}`
  - `catalog/customization-options/jacket/{option-id}`

Existing images continue working from their current paths.

## Product editor update

The reusable Size Option picker now includes a clothing-section filter. When the product has one clear customization family selected, the picker automatically opens on that family so unrelated size groups are not mixed into the initial list.

## Database

No new migration is required. The existing customization option table already separates records by `type`, and the existing size option table already separates records by `customization_group` with group-and-slug uniqueness.

## Validation performed

- PHP syntax check completed for 262 PHP files.
- Custom enum integrity check completed for all customization cases and groups.
- Vite production asset build completed successfully.
- PHPUnit could not run in the packaging environment because its PHP installation does not include DOM, mbstring, XML, and XMLWriter extensions. Additional automated tests were added for Sweatshirt/Jacket option and size isolation and will run in a normal project PHP environment.
