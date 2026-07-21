# Product Card Tick and Visitor Visibility Update

## Changed
- Increased customization option text weight so options like `Custom design` and `Artwork upload` are easier to read.
- Strengthened the circular tick icon with a heavier border, clearer cyan fill color, and subtle background.
- Improved shopper activity/visitor row styling with a clearer eye icon and stronger text weight.
- Visitor information still only appears when genuine product data exists.

## Visitor information behavior
The card shows visitor/activity information only when one of these product metrics is greater than zero:

1. `current_viewers_count` or `active_shoppers_count` → shows `X shoppers viewing now`
2. `recent_viewers_count` → shows `X shoppers viewed this recently`
3. `recent_orders_count` / `orders_count` / `order_count` → shows `X orders placed recently`
4. `favorites_count` / `favorite_count` / `wishlist_count` → shows `X customers saved this product`

No visitor, favorite, or order count is displayed when the value is empty or zero, so the storefront does not show fake activity.
