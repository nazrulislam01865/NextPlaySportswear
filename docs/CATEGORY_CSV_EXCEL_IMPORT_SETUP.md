# Category CSV / Excel import setup

The category import now accepts **.csv**, **.xlsx**, and **.xls**, preserves the
existing category upsert/parent validation, and reads the **first** worksheet.

## Required one-time dependency

From your Laravel project root, run:

```bash
composer require phpoffice/phpspreadsheet
npm run build
php artisan optimize:clear
```

Commit the updated `composer.json` and `composer.lock` from `composer require`
alongside the changed source files. When deploying, install dependencies from
that lock file using `composer install`.

PhpSpreadsheet needs PHP extensions such as `zip`, `xml`, `mbstring`, and `gd`.
If Composer reports missing extensions, enable them in the PHP configuration
used by your application and Composer. Do **not** bypass extension checks.

## Upload structure

Choose a CSV file or Excel workbook with these columns in the first row:

`name, slug, parent_slug, menu_label, category_type, page_template, status, short_description, sort_order, is_featured, is_visible_in_catalog, is_visible_in_menu, include_descendant_products, meta_title, meta_description`

`name` and `slug` are required headers. The other supported headers are optional.
For Excel, only the first worksheet is imported. Max 2 MB; max 1,000 data rows.
Formulas are rejected. Existing categories are updated by slug, not duplicated.
The operation remains transactional; invalid parents/duplicate slugs roll back.

The **Export existing CSV** link on the Categories page supplies the correct
column headings for creating the import file. Do not re-import without
reviewing its values, since existing category records are updated by slug.

## Smoke tests

1. Export categories CSV and import a small edited copy; verify updates.
2. Save the same column format as XLSX and XLS (first worksheet); import each.
3. Check that an invalid parent slug shows a validation error and saves nothing.
4. Confirm the file name appears after selection and the action is disabled
   until a file is selected.

No database migrations are required.
