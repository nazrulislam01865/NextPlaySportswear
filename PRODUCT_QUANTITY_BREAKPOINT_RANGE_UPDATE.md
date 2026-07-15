# Product Quantity Breakpoint Range Update

## Purpose

The adaptive Excel/CSV price-table importer now accepts a quantity column that contains either:

- starting quantity breakpoints such as `1`, `5`, `12`, `24`, or
- explicit quantity ranges such as `1-4`, `5-11`, `12 to 23`, or `100+`.

No fixed spreadsheet layout is required.

## Range generation

When a row contains only one quantity value, that value is treated as the row's minimum quantity. The maximum quantity is generated from the next row's minimum quantity minus one.

Example using `Book2.xlsx`:

| Spreadsheet quantity | Generated storefront range |
|---:|---:|
| 1 | 1-4 |
| 5 | 5-11 |
| 12 | 12-23 |
| 24 | 24-49 |
| 50 | 50-99 |
| 100 | 100-199 |
| 200 | 200-299 |
| 300 | 300-499 |
| 500 | 500-999 |
| 1000 | 1000+ |

## Explicit ranges

If a cell contains two values, the first is parsed as the minimum and the second as the maximum. When that maximum leaves a gap or overlaps the following row, the following row's minimum remains authoritative and the preceding maximum is normalized to `next minimum - 1`.

The final row may:

- remain open-ended, displayed as `1000+`, or
- retain an explicit final maximum such as `1000-2000`.

## Admin interface

The visible pricing table now shows one editable **Quantity starts at** input per row. Its generated range is displayed directly below it. The maximum quantity is submitted through a hidden field and cannot be edited into an inconsistent value.

Adding, removing, or changing a starting quantity recalculates all affected maximum quantities and synchronizes the linked production-option quantity ranges.

## Import mapping improvements

- The mapping option is now named **One quantity or range column**.
- Entirely empty spreadsheet columns are not selected as storefront columns by default.
- Single quantity values and explicit ranges can be used in the same imported sheet.
- Rows are sorted by their minimum quantity before ranges are generated.
- Duplicate or descending starting quantities are rejected.

## Server-side protection

Laravel repeats the same range normalization before validation and persistence. A modified browser request cannot save quantity gaps or overlaps.

## Database

No migration is required.
