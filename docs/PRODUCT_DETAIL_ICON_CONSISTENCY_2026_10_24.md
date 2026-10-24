# Product-detail icon consistency fix - October 24, 2026

## Scope

The product-detail controls continue to use the same centrally managed icon upload fields. No new settings, product queries, or dynamic product-to-icon assignment logic were added.

- Newly uploaded PNG, JPG, WebP, or AVIF icon artwork is centered on a uniform **transparent 256 x 256 PNG canvas** with an 208 px maximum visible dimension (24 px transparent margin on each side). Proportions are maintained; no new tile, frame, shadow, or background is drawn.
- The auto-normalizer removes embedded solid white tiles only when a file is clearly dark line-art on a mostly-white background. Photos, SVGs, and unrelated media paths are not modified.
- The admin uploaded-icon preview has no added color tile or border, and the three product summary icons share a 48 px display slot.
- The four icon files included in this project archive were normalized in place, keeping their original storage paths to avoid disrupting existing database references.
- A separate optional pack contains the 39 supplied PNG designs normalized to the same specification.

## Existing live icon uploads

If your production database references other icon uploads that are not included in this ZIP, use the existing repair command on the deployed application, with PHP GD installed:

```bash
php artisan nextplay:normalize-product-detail-icons --dry-run
php artisan nextplay:normalize-product-detail-icons
php artisan optimize:clear
```

The repair command updates only image paths under `storefront/product-detail-ui/` referenced by the centralized product detail settings. Run a normal deployment backup before modifying production uploads. SVG files are intentionally left unchanged.

## Validation

- PHP syntax validation for changed PHP files.
- Three existing standalone product-detail regression checks.
- Artwork-preview inspection and 39-image PNG size/alpha verification.
- PHPUnit regression test supplied for white-tile removal and repeat-run stability; requires the installed Laravel dependencies and PHP GD to execute.

No database migration is required. The site database and live uploaded icon assignments are not part of this ZIP and cannot be verified here.
