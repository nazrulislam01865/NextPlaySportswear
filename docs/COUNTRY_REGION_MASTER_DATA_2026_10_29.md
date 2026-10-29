# Country / Region Master Data (2026-10-29)

## Scope
Country and phone codes reuse the **existing** `country_calling_codes` table and its admin menu. One new table, `country_regions`, stores *optional* administrative divisions. The added `region_label` column controls whether forms say State, Province, County, Division, etc. No duplicate countries table or shipping table was created.

## Admin
Open **Master Data → Countries & Regions**. Add/edit a country (ISO code, name, phone dial code, region label, active status); click **Regions** to add/edit/disable regions for that country. If no active regions exist, storefront users get an **optional free text** region field. Otherwise they get an **optional dropdown** populated on demand. Country and region lists are never hardcoded in customer forms.

## Import
Download **CSV Template** on the Country Master Data page. It also works as an Excel sheet (`.xlsx` or `.xls`) when saved with exactly the same nine columns:

`iso_code,country_name,dial_code,region_label,region_name,region_code,sort_order,country_active,region_active`

- All columns must be present, in that order. `iso_code` must be two letters; `country_name` and `dial_code` are required on the first row for a new country; subsequent rows for that country may omit them. Existing countries may supply only the ISO and leave those columns blank.
- Leave `region_name` blank to import a country without a region; add one row per region otherwise.
- Optional `region_label`, `region_code`, `sort_order`, `country_active`, `region_active` allow blank values; active flags when populated must be `1` or `0`. Blank active flags preserve existing statuses; new records default active.
- Imports are **non-destructive**: existing countries match by ISO; existing regions match by country + region name. Existing country names, calling codes, labels, status, sorting, region names and region status remain unchanged. Only genuinely empty country text fields or blank region codes are filled from the file. Absent countries and regions are inserted. Repeating an import does not add duplicates or update an unchanged record. Existing customer addresses and historic orders remain untouched.
- Imports are validated before a transaction; limit 5,000 rows and 4 MB per upload. A file with an invalid row is rejected without partial updates.

## Install
1. Upload the updated project files. Run `php artisan migrate --force`.
2. Run `php artisan optimize:clear`. No `npm` rebuild is required: the dependent selector uses `public/js/address-geography.js`.
3. In admin, populate the regions you want or import the CSV/Excel template. Existing countries from the original calling-code master data remain available.
4. Verify new checkout shipping, billing, saved addresses, referral checkout and bulk quotes. Confirm the dropdown switches when the country changes, a region without configured options becomes free text, and the optional field can be empty.
5. Existing shipping eligibility, rural surcharges, currencies and tax logic are **not changed by this geography update**. Configure shipping rules separately before enabling additional destinations.

## Implementation notes
- The storefront component reads active countries once per request using `AddressGeography` and a `withExists` region flag, then requests only the selected country's active region options (`/address-regions/{iso}`).
- Existing country values (full country names) and region values (names) continue to be persisted as strings; no migration of order snapshots is needed.
- Existing address and checkout form layouts, design tokens, central font family, master-data permissions and standard `btn` classes are preserved.
