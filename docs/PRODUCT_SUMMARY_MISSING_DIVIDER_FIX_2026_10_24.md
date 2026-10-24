# Product summary divider repair - October 24, 2026

## Scope
Restored the missing one-pixel vertical separator between "Sizes Available" and "Minimum Order Quantity" in the product customizer's Sizes & Quantities summary. The line matches the existing summary separators (`#e6ebf1`). No markup, business rules, database migrations, icon uploads, or admin settings were changed.

## Responsive behavior
On panels wide enough for four columns, the Sizes Available cell has an explicit right border. On two-column tablet panels with a selected fabric, that border is removed because Sizes Available ends its row. On stacked mobile panels the vertical border is removed. Products without a fabric selection continue to use their three-column arrangement on wide panels.

## Files
- `resources/css/storefront.css`: targeted separator rule and responsive rules.
- `public/build/manifest.json`, `public/build/assets/storefront-divider-*.css`: updated prebuilt stylesheet to be served immediately without npm.
- `tests/Regression/ProductDetailSizeSummaryLayoutTest.php`: existing layout regression expanded to cover separator.

## After deployment

```sh
php artisan optimize:clear
php artisan view:clear
```

Reload the product page with a hard refresh if browser CSS remains cached. No migration or npm build is needed when deploying the full project including its `public/build` folder.

## Verification
Focused PHP regression test, PHP syntax, CSS syntax and generated ZIP integrity passed. Browser visual verification was not completed in this environment.
