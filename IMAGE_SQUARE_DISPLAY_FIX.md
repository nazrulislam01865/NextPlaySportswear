# Image Square Display Fix

Updated storefront product/media presentation so product images display as clean 1:1 square media without decorative framing outside the image.

## Updated areas

- Product details main gallery
  - Removed decorative blue/teal outer frame.
  - Removed gallery image overlay badge from the image area.
  - Removed floating zoom icon outside/over the image.
  - Kept click-to-open image behavior on the image itself.
  - Forced the main image frame to 1:1.

- Product cards
  - Product badge is no longer positioned over the image.
  - Badge now appears in the card content area, keeping the image clean.
  - Product image area remains 1:1.

- Product option images
  - Customization/option preview images now use square 1:1 cards instead of 4:3.

- Global storefront image CSS
  - Product media and category media use square 1:1 aspect ratio.
  - Product images use `object-fit: contain` to avoid cropping product artwork.
  - Category images use `object-fit: cover` to keep category tiles filled.

- Admin image previews
  - Added square normalization for admin image thumbnails/previews.

## Files changed

- `resources/views/storefront/products/show.blade.php`
- `resources/views/components/storefront/product/gallery.blade.php`
- `resources/views/components/storefront/product-card.blade.php`
- `resources/views/components/storefront/product/option-group.blade.php`
- `resources/css/storefront.css`
- `resources/css/admin.css`
- `public/build/assets/storefront-DsMBfpLy.css`
- `public/build/assets/admin-Bbk9rfJr.css`

No database migration is required.
