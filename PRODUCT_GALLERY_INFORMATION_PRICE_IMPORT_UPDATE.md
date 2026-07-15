# Product Gallery, Information, and Price Import Update

## Scope

This update changes only the Product Header & Gallery, Product Information, and Quantity Price Table areas of the admin Add/Edit Product workflow. Existing customization, production-option, shipping, artwork, cart, and order logic remains in place.

## 1. Product Header & Gallery

- The multi-image upload control now appears directly below **Gallery badge label**.
- Administrators can select up to 20 JPG, JPEG, PNG, WebP, or AVIF files at once, with a 5 MB limit per image.
- Every selected file is previewed before the product is saved.
- A selected upload can be marked as the **Primary image**.
- Individual selected uploads can be removed before saving.
- Existing or remote images are managed in an **Image links** area.
- Each image-link row contains only:
  - **Name** — the customer-accessible image name/alt text.
  - **Image Link** — the secure public image URL.
  - **Make primary / Primary image**.
  - **Remove**.
- Only one image—uploaded or linked—is stored as primary. If none is explicitly selected, the first available image becomes primary.

## 2. Product Information

- The visible **SKU** and **Brand** fields were removed from the Add/Edit Product form.
- Existing SKU values are preserved during edits.
- New products receive a unique internal SKU automatically.
- Brand is no longer changed by this simplified editor.
- **Detail / Information rows** now appear first.
- **Category** and **Tags** appear beneath the detail rows in compact left-label/right-input rows.
- SKU and Brand were also removed from the storefront information block and default generated detail rows.
- Category and Tags remain visible on the storefront beneath the Detail / Information table.

## 3. Quantity Price Table Excel Import

The Visible Storefront Pricing Table now supports both spreadsheet import and the existing manual editor.

### Accepted files

- `.xlsx`
- `.csv`
- Maximum file size: 5 MB

### Supported spreadsheet layouts

#### Separate quantity columns

| Min Qty | Max Qty | Unit Price | Savings |
|---:|---:|---:|---:|
| 1 | 49 | $25.00 | — |
| 50 | 99 | $22.00 | 12% |
| 100 |  | $19.00 | 24% |

#### One quantity-range column

| Quantity | Unit Price | Savings |
|---|---:|---:|
| 1-49 | $25.00 | — |
| 50-99 | $22.00 | 12% |
| 100+ | $19.00 | 24% |

Recognized quantity headings include `Min Qty`, `Minimum Quantity`, `Max Qty`, `Maximum Quantity`, `Quantity`, `Qty`, and `Quantity Range`.

### Import behavior

- Spreadsheet headers generate the visible customer columns.
- Spreadsheet records generate the pricing rows.
- Quantity rows are sorted by minimum quantity.
- Gaps, overlaps, invalid values, and a non-final open-ended range are rejected.
- The most likely unit-price column is highlighted automatically; the administrator can still change the highlight index.
- Imported rows remain fully editable using the existing manual controls.
- Production quantity ranges are regenerated from the imported pricing rows so stale options cannot remain attached to an old range.

## Files changed

- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Requests/Admin/ProductFormRequest.php`
- `app/Services/Storefront/ProductCatalogService.php`
- `resources/js/app.js`
- `resources/views/admin/products/_form.blade.php`
- `resources/views/components/storefront/product/detail-information.blade.php`
- `package.json`
- `package-lock.json`
- rebuilt `public/build` assets

## Deployment

No database migration is required.

```bash
cd /path/to/nextplay
php artisan optimize:clear
php artisan view:cache
```

The patch and full package include rebuilt Vite assets. Running `npm install && npm run build` is only necessary if frontend source files are changed again.

## Validation completed

- 170 PHP files passed syntax checks.
- 165 Blade templates compiled and passed PHP syntax checks.
- 173 Laravel routes loaded successfully.
- JavaScript syntax validation passed.
- Vite production build completed successfully.
- npm dependency audit reported zero vulnerabilities.

Full PHPUnit/database integration tests were not run in the packaging environment because the required PHP DOM/XML/mbstring and PDO SQLite extensions were unavailable.
