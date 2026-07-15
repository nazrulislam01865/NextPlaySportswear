# Custom Production Table Update

## Scope

The Add/Edit Product **Production & Shipping** section now contains an independent production-table builder. It does not copy rows, ranges, or columns from the Visible Storefront Pricing Table or master data.

## Admin workflow

- **+ Column** adds a production option such as Standard Production, Rush Production, or Priority Production.
- **+ Row** adds a manually entered quantity range such as `1-40`, `41-99`, or `100+`.
- Each range/option cell can be enabled or disabled.
- Each enabled cell stores:
  - separate charge per piece,
  - minimum production days,
  - maximum production days,
  - optional customer-facing description.
- Columns and rows can be removed independently.
- Maximum limits: 12 option columns and 100 quantity rows.

## Storefront and pricing behavior

The table is saved on the product as JSON for exact editor reconstruction. Enabled cells are also normalized into the existing `product_production_speeds` relation, so the current storefront selection, live order summary, cart validation, and separate per-piece production charge continue to work.

The first enabled production option for a matching quantity range remains the default customer choice.

## Validation

- Accepted range formats: `1-40`, `41+`, or a single quantity such as `25`.
- Ranges cannot overlap.
- Maximum production days cannot be lower than minimum production days.
- Column names are required and unique.
- Production charges cannot be negative.

## Database

Migration added:

`2026_06_24_000007_add_custom_production_table_to_products.php`

New nullable product columns:

- `production_table_headers` JSON
- `production_table_rows` JSON

Existing products are shown through a compatibility fallback that reconstructs a table only from that product's saved production options. No data is fetched from its price table or master data.
