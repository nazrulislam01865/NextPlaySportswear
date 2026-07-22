# Best-Selling Gear Border Refinement

Refined the homepage Best-Selling Team Gear section to better match the supplied sports reference image.

## Changed files

- `resources/views/components/storefront/home/best-selling-gear.blade.php`
- `resources/css/storefront.css`
- `public/build/assets/storefront-NpGearFix.css`
- `public/build/manifest.json`

## What changed

- Added a dedicated decorative divider element for the featured card instead of relying on the content panel pseudo-element.
- Restored the `01` badge on the featured card.
- Tightened the featured card width, grid split, rounded border, and shadow to match the reference card proportions.
- Refined the navy angled divider and blue accent line so it no longer looks oversized or cuts into the content area.
- Adjusted supporting card height, number badge position, border, and spacing.
- Added responsive overrides for tablet and mobile so the divider is hidden when the featured card stacks vertically.
- Updated the Vite manifest to load a new storefront CSS asset and avoid stale browser cache.
