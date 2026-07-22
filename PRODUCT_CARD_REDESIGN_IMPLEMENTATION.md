# Product Card Redesign Implementation

Updated the reusable storefront product card component used by product listing, category pages, home product sections, cart suggestions, and related products.

## Changed files

- `resources/views/components/storefront/product-card.blade.php`
- `resources/css/storefront.css`
- `public/build/assets/storefront-BestGearFontMatch.css`
- `public/build/assets/storefront-BFxF3sem.css`
- `app/Services/Storefront/ProductCatalogService.php`

## What changed

- Added redesigned premium product card layout.
- Added image-area favorite heart control without fake favorite counts.
- Added customization option chips from real product customization data/features.
- Excluded the unwanted texts:
  - `Name & number options`
  - `Team pricing available`
- Added rating and review display only when real rating/review fields are present and greater than zero.
- Added optional shopper/order/favorite activity display only when genuine product fields exist.
- Added bulk quote availability only when the product has multiple price tiers, multiple price-table rows, or a bulk minimum quantity.
- Added prominent `Customize & Order` CTA linking to the product configuration section.
- Added secondary `View product details` link.

## Notes

The component intentionally does not invent visitor counts, order counts, favorite counts, ratings, or reviews. Those elements render only when actual store data is available.
