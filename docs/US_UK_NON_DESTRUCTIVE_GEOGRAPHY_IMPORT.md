# NextPlay — Non-destructive Country & Region imports (US + UK)

## What changed
Both admin import routes now use the same `App\Services\Geography\ImportMissingGeography` implementation. Existing country records match by ISO code; region records match by country and region name.

- Existing country name, dial code, region label, active status and sort order are preserved.
- Existing region name, code, active status and sort order are preserved.
- Missing country records and missing regions are inserted.
- Only genuinely blank country text fields or blank region codes are filled from uploaded data.
- Reimporting a complete dataset is safe: existing rows do not get updated, and new rows are not duplicated.
- All data is validated before a transaction. Country and region lookups are batched, with bulk inserts and grouped upserts, not an individual select per region.
- Storefront address values and historical order data are not changed.
- Import still requires existing `orders.manage` admin permission.

## Main workbook
`NextPlay_US_UK_Countries_And_Regions_Import.xlsx` and its CSV equivalent contain two countries (`US`, `GB`) and **269 subdivisions** in the first worksheet:
- United States: 50 states + District of Columbia = 51
- United Kingdom: 218 first-order local-authority units (153 England, 32 Scotland, 22 Wales, 11 Northern Ireland)

Use **Admin → Master Data → Countries & Regions → Import File**. The nine columns are `iso_code, country_name, dial_code, region_label, region_name, region_code, sort_order, country_active, region_active`. Leave status columns blank for a safe import of new rows with default active status. Existing active statuses are always kept regardless of imported values. The `sort_order` for new records is used, but existing sorting is preserved.

The five inhabited US territories are listed for reference, not imported automatically because they may need different delivery eligibility and pricing rules. The UK region list contains first-order local authorities, not every lower-tier district or every traditional county.

## Individual per-country workbooks
- `NextPlay_US_Regions_Only_Import.xlsx` — 51 rows, upload under United States → Regions / Import
- `NextPlay_UK_Regions_Only_Import.xlsx` — 218 rows, upload under United Kingdom → Regions / Import

Each has an active first worksheet `Regions Import` with exactly four columns: `name,code,sort_order,is_active`.

## Deployment
Upload the patch or updated full project (including the new service) and run `php artisan optimize:clear`. No new migration or asset compilation is needed if the previous Country & Regions migration was already applied.

## Verification
PHP lint succeeded for changed PHP files. Regression tests added in `tests/Feature/GeographyImportPreservationTest.php`, covering repeat imports, preserved disabled status/sorting and blank code enrichment. Run them with dependencies installed: `php artisan test --filter=GeographyImportPreservationTest`. Laravel runtime tests were not executable from this ZIP because no Composer `vendor/` is supplied.

### Sources
- US states: https://www.census.gov/library/reference/code-lists/ansi/ansi-codes-for-states.html
- US postal abbreviations: https://about.usps.com/who/profile/history/state-abbreviations.htm
- UK first-order authorities: https://www.gov.uk/government/publications/toponymic-guidelines/toponymic-guidelines-for-map-and-other-editors-united-kingdom-of-great-britain-and-northern-ireland--2
