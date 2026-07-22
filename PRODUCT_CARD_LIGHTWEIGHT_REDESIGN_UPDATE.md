# Product Card Lightweight Redesign Update

Implemented a lighter storefront product card that follows the approved prototype while keeping the existing NextPlay theme.

## What changed

- Reduced card shadow, border radius, title weight, price weight, button weight, and spacing so the card no longer looks oversized or too bold.
- Kept the main card structure: product image, favorite control, customizable badge, category label, title, rating/review row, starting price, bulk quote label, Customize & Order button, and View product details link.
- Removed the unwanted text signals: Name & number options and Team pricing available.
- Customization options now display as compact chips, limited to two visible options plus a “+ more” chip when more exist.
- Product listing grids now use a maximum of 3 product cards per row for better balance with the richer card layout.
- Rating/review display now works whenever genuine rating and review values exist, even if the explicit `has_reviews` flag is not present.
- Added optional real product-card metric fields to products:
  - `rating_average`
  - `reviews_count`
  - `recent_viewers_count`
  - `favorites_count`
  - `recent_orders_count`
- Added these optional fields to the admin product form under “Product card store data”. Leave them blank unless the data is real.
- Storefront activity text still only appears when a real viewer/favorite/order metric is present.

## Important

Run migrations after uploading so the optional metric fields are available:

```bash
php artisan migrate
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
```

If rating/review values are left blank, the card intentionally hides the rating row instead of showing fake data.
