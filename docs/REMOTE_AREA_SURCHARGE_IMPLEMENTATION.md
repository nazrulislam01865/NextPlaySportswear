# Remote Area Surcharge Implementation

## UPS workbook analysis

The supplied workbook `REMOTE AREA LIST-surcharge-en-UPS(1).xlsx` contains one sheet, `EAS Definitions`.
The actual header is on row 17 and the workbook contains 65,897 data rows across 86 countries.

Source columns:

1. Country
2. IATA Code
3. Postal Code Low
4. Postal Code High
5. City
6. Origin Surcharge
7. Destination Surcharge

The workbook does **not** contain a money amount, so the application adds the requested `extra_charge` field.

Important data characteristics:

- Postal codes are mixed numeric and alphanumeric values, so they must be stored as strings.
- Leading-zero values exist (for example `000000`).
- Canadian and UK values contain letters; some UK rows are postcode prefixes such as `AB37`, `IM`, or `IV1 3`.
- 28,314 rows are ranges where Low and High differ.
- 4,104 rows use the City column. Some countries use `Low = 0` and `High = 0` and are matched by city instead.
- The sheet contains Remote, Extended, Pickup, and Delivery area classifications. The exact source text is preserved.

## Stored fields

The existing `rural_area_surcharges` table is preserved and extended instead of being replaced.
Legacy `name`, `state`, `postal_code_patterns`, and `amount` columns remain compatible.

New structured fields:

- `carrier`
- `iata_code`
- `postal_code_low`
- `postal_code_high`
- `postal_code_low_normalized`
- `postal_code_high_normalized`
- `postal_code_low_numeric`
- `postal_code_high_numeric`
- `city`
- `city_normalized`
- `origin_surcharge`
- `destination_surcharge`
- `extra_charge`
- `source_file`
- `source_row`
- `source_hash`
- `import_batch_id`

Internal normalized/numeric columns are intentionally stored because checkout must search tens of thousands of rows without loading the complete UPS list into PHP memory.

## Import design

The admin page accepts the UPS XLSX directly.
The existing `read-excel-file` frontend dependency reads the workbook in the browser and sends rows to Laravel in 1,000-row chunks.
This avoids PHP upload/post-size issues and does not add a new PHP spreadsheet dependency.

Rows are first written to staging tables. Live checkout data is changed only during finalization.
Repeated chunks are idempotent through a per-batch hash, and repeat workbook imports upsert by a stable source hash.

When **Replace previous imported rows** is enabled, only older spreadsheet-imported rows for the same carrier are removed. Manual surcharge rules are never deleted by the importer.

## Checkout matching

Matching order:

1. Exact normalized city match for city-based UPS records.
2. Indexed numeric postal range lookup for numeric postal systems.
3. Prefix/natural-range matching for alphanumeric systems such as Canada and the UK.
4. Legacy pattern matching for pre-existing manual rules.

Rows whose `Destination Surcharge` is `No` are stored for source fidelity but do not apply an extra charge at checkout.

## Apply the update

```bash
php artisan migrate
npm run build
php artisan optimize:clear
```

Recommended tests:

```bash
php artisan test --filter=RemoteAreaSurchargeNormalizerTest
php artisan test --filter=RemoteAreaSurchargeServiceTest
```

Then open **Admin -> Remote Surcharges**, enter the default Extra charge, choose the UPS XLSX file, and run the import.
