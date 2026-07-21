# Product Information, Size Master Data, and Production Range Update

## Product Information

- Replaced the plain Detail / Information inputs with a reusable formatted editor-style component.
- Detail and Information remain repeatable key/value rows, preserving the storefront specifications structure.
- Category and Tags remain beneath the information editor.

## Master Data: Size Options

Added a dedicated Laravel module under **Master Data → Size Options**.

### Architecture

- `App\Enums\SizeAudience`
- `App\Models\SizeOptionGroup`
- `App\Models\SizeOption`
- `App\Http\Requests\Admin\SizeOptionGroupRequest`
- `App\Http\Controllers\Admin\SizeOptionGroupController`
- `App\Services\Catalog\SizeOptionGroupService`
- Separate index/create/edit/form Blade views
- Reusable `x-admin.size-option-rows` component
- Feature tests

### Master-data fields

- Name
- Type: Male, Female, Young / Youth, Kids, Unisex, Custom
- Formatted size description
- Repeatable available sizes
- Optional measurement table
- Optional chart title and note
- Optional chart image upload or image link

## Add/Edit Product: Sizes & Quantities

- Removed the large manual size-group editor from the product form.
- Added an **Add Size Option** picker using active Size Options master data.
- The modal filters by group name, type, or size.
- A master group cannot be selected twice.
- Selected groups show their name, type, formatted description, size chips, chart status, and optional thumbnail.
- Product saves a secure snapshot of the master sizes and chart data so existing products remain stable if master data changes later.
- Legacy product-specific size groups remain supported for backward compatibility.

## Storefront

- The formatted size description is displayed above the selected group's sizes.
- Existing size quantities and size-chart modal behavior remain intact.

## Production & Shipping

- Production options now use a price-table-style admin layout.
- Minimum and maximum quantity are represented by one read-only Range field such as `1-40` or `100+`.
- The range remains synchronized with the Visible Storefront Pricing Table.
- Each range still supports zero to three production options.
- Production option name, charge, days, description, edit, and remove behavior remain unchanged.

## Database

Migration:

`2026_06_24_000005_create_size_option_master_tables.php`

It creates:

- `size_option_groups`
- `size_options`
- `product_size_groups.size_option_group_id`
- `product_size_groups.description_html`

All new foreign-key and index names are explicitly shortened for MySQL compatibility.
