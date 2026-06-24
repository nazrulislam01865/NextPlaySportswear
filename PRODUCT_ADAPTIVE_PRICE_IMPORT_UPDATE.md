# Adaptive Quantity Price Table Import

## Purpose

The Quantity Price Table importer no longer requires a predefined Excel structure.

Administrators may upload any `.xlsx` or `.csv` pricing sheet and map its columns before generating the storefront table.

## Import workflow

1. Upload an XLSX or CSV file.
2. The application detects a likely header row.
3. A mapping modal opens with a spreadsheet preview.
4. The administrator chooses:
   - the header row;
   - one quantity-range column, or separate minimum/maximum quantity columns;
   - the spreadsheet columns that should appear on the storefront;
   - the primary live-price column used for cart calculations.
5. Select **Generate Price Table**.
6. The current visible storefront table is replaced by the imported structure and remains manually editable.

## Supported quantity formats

A single mapped quantity column may contain values such as:

- `1-24`
- `25 to 49`
- `50+`
- `100 and above`
- `200 or more`
- `500 unlimited`

Alternatively, any spreadsheet columns can be mapped as minimum and maximum quantity columns. A blank maximum value means there is no upper limit.

## Dynamic storefront columns

- Spreadsheet headings are preserved as customer-visible table headings.
- Column order is preserved.
- Blank headings receive a safe generated name.
- Duplicate headings receive a numeric suffix.
- Internal spreadsheet columns can be unchecked before import.
- Up to 19 customer-visible columns and 200 price rows are supported.

## Validation

The importer verifies that:

- a valid quantity mapping is selected;
- at least one storefront column is selected;
- the primary live-price column is one of the selected storefront columns;
- minimum and maximum quantities are valid;
- ranges do not overlap or contain gaps;
- only the final range is open-ended;
- rows containing quantities also contain storefront values.

## Connected production ranges

After generation, the imported quantity ranges synchronize with Production & Shipping. Each range can continue to contain zero to three admin-defined production options.

## Changed files

- `resources/js/app.js`
- `resources/views/admin/products/_form.blade.php`
- rebuilt `public/build` assets

## Database

No migration is required.
