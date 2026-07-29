# Wishlist Center and Product Sharing Update

## Product details
- Removed the duplicate Share button beneath the product gallery.
- Connected native sharing and the fallback share menu to the Share button beside the product title.
- Connected the title-area Save button and gallery heart to the same wishlist state.
- Both wishlist controls update without a page reload.

## Wishlist persistence
- Logged-in customers use the `product_wishlists` database table.
- Guests use `nextplay:guest-wishlist:v1` in browser local storage.
- Wishlist counts update immediately across the product page, header, and wishlist page.

## Wishlist center
- Added `/wishlist` with cart-style saved product cards.
- Supports viewing and removing saved products.
- Guest product data is refreshed from the product catalog when possible.
- Added a global heart icon and live count beside the cart, plus a mobile-menu Wishlist card.

## Assets
- Source assets are updated in `resources/js/storefront.js` and `resources/css/storefront.css`.
- Ready-to-deploy compiled files are included through `public/build/manifest.json`:
  - `assets/storefront-wishlist-center-v2.js`
  - `assets/storefront-wishlist-center-v2.css`
