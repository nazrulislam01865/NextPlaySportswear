# NextPlay — Country/Region master-data usability and UK import

## Changes
- **Countries & Regions**: removed the two requested instructional paragraphs, improved spacing around file inputs, and simplified the country actions.
- **Regions by country**: open **Master Data → Countries & Regions → [Country] → Regions / Import**. This page now has its own CSV/XLS/XLSX upload, downloadable **country-specific template**, and a compact add-region form.
- A shared `resources/views/components/admin/geo-import-field.blade.php` form component and scoped `public/css/country-region-master.css` stylesheet reuse existing fonts and `btn` classes. No new frontend JavaScript, database tables, or migrations.
- New controller: `CountryRegionImportController`; new named routes: `admin.country-regions.import-template` and `admin.country-regions.import`.
- The region-only import **does not edit the country, its ISO code or calling code**. It inserts missing regions and fills blank region codes only; existing region status, sorting, names and codes are always preserved. Existing saved addresses are unchanged.

## Excel region upload format
Upload CSV, XLS or XLSX with four column headings, in this order:

`name,code,sort_order,is_active`

- `name`: required, max 120 characters.
- `code`: optional, max 20 characters.
- `sort_order`: blank or integer between 0 and 999999.
- `is_active`: `1`, `0`, or blank. Any imported status does not overwrite an existing status; new regions default active if blank.
- Max 5,000 rows / 4 MiB; file validated before the transaction.
- For the UK workbook, the importer automatically reads the `Regions Import` worksheet even if a user last opened another tab.

## UK workbook
Upload `NextPlay_UK_All_Regions_Import.xlsx` on **United Kingdom → Regions / Import**. A CSV alternative is provided.

It includes 218 current UK local-authority areas grouped into four constituent nations: England (153), Scotland (32), Wales (22), Northern Ireland (11). This is one level of geographic options under country **GB**. It does not add four new countries to the calling-code master. The workbook includes extra **reference-only** nation/England-region sheets, an authority directory and instructions.

For UK addresses, consider changing the UK region label in **Edit Country** from `County` to `County / Council Area` (optional field). This is a simple country setting change, not a migration.

Sources: 
- UK government 30 July 2026 toponymic guidance (first-order local authorities): https://www.gov.uk/government/publications/toponymic-guidelines/toponymic-guidelines-for-map-and-other-editors-united-kingdom-of-great-britain-and-northern-ireland--2
- ONS countries and regions terminology: https://service-manual.ons.gov.uk/content/language/countries-and-regions
- UK standard GB nation identifiers: https://www.gov.uk/government/publications/open-standards-for-government/country-codes

## Deployment
No DB migration or frontend asset compilation is needed for this update.

```bash
php artisan optimize:clear
```

The existing `country_regions` migration from the prior dynamic-address update must already have run.

## QA checklist
- Country-level import and country-specific import are separate.
- A country-specific upload cannot change another country's regions.
- Country-specific import is blocked for admins lacking `orders.manage`.
- Headers, size, row counts, statuses, and codes are validated.
- Uploaded region names are used only for missing records; existing records are kept and order snapshots are unchanged.
- Desktop and mobile form controls use the project typography/button tokens.
