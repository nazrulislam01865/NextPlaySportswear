# Product Card Compact 4-Column Update

Implemented the requested product-card refinement.

## What changed

- Restored the home product section to 4 cards per row on desktop.
- Product/category listing grids now support a 4-column layout on wide screens while remaining responsive on smaller screens.
- Reduced the card visual weight so it no longer looks oversized or too bold.
- Kept the reference-card structure using the NextPlay theme:
  - product image panel
  - favorite control
  - customizable badge
  - category label
  - product title
  - customization option indicators
  - rating and review count
  - starting price
  - bulk quote availability
  - Customize & Order CTA
  - product details link
- Removed bulky customization pills and replaced them with lightweight check indicators.
- Rating and review count now display using product-level values when available, with configurable storefront fallback values.
- Visitor, favorite, and order activity still stay hidden unless real product data exists.

## Config

The product-card rating fallback can be controlled in `.env`:

```env
STOREFRONT_PRODUCT_CARD_SHOW_DEFAULT_RATING=true
STOREFRONT_PRODUCT_CARD_DEFAULT_RATING=4.8
STOREFRONT_PRODUCT_CARD_DEFAULT_REVIEWS_COUNT=23
```

Set `STOREFRONT_PRODUCT_CARD_SHOW_DEFAULT_RATING=false` if you only want ratings shown after each product has product-level rating data.

## Files changed

- `resources/views/components/storefront/product-card.blade.php`
- `resources/views/components/storefront/home/product-collection.blade.php`
- `resources/views/storefront/products/index.blade.php`
- `resources/views/storefront/categories/show.blade.php`
- `resources/views/components/storefront/category/content-block.blade.php`
- `resources/css/storefront.css`
- `config/storefront.php`
- `public/build/assets/storefront-*.css`
