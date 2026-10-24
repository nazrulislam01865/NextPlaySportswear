# Product detail summary alignment and icon presentation — October 24, 2026

Changes are limited to the product customizer size/quantity summary and configurable-icon presentation.

- Four equal-width summary cells when the inner panel has adequate space; two equal cells in medium-width panels and one per row on narrow screens. The layout uses the **actual container width**, avoiding narrow columns caused by sidebars. Products without fabric controls use three equal cells on wide panels.
- The material preview no longer renders a blank gray tile if no fabric image is configured; existing Change functionality is preserved.
- Configurable uploaded icons have no additional CSS tile, background, frame, padding or shadow. Shipping-method image/icon previews are transparent too. If an uploaded image itself contains a nontransparent background, this cannot be removed through CSS.
- Added a standalone PHP regression test. Existing menu/shipping behavior, checkout and database are untouched. No migration is required.

## Deployment

Replace the project with the updated files using your existing backup/deploy process, then run:

```bash
php artisan optimize:clear
php artisan view:clear
```

The supplied `public/build` manifest already references updated, versioned storefront CSS; an npm build is not required just to see these fixes. When changing frontend source again, use `npm ci && npm run build` in a working Node environment.

## Standalone validation

```bash
php tests/Regression/ProductDetailSizeSummaryLayoutTest.php
php tests/Regression/ProductDetailMultiSizeGuideAndConfigurableHighlightsTest.php
php tests/Regression/ProductDetailMaterialImageFlushTest.php
```
