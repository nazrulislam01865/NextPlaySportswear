# Shipping Method Quantity Price Update

## New logic

- Shipping methods are created in Master Data only for the reusable method information: name, code, description, delivery days, default status, active status and sort order.
- Master shipping methods no longer collect product pricing or charge rules.
- Product add/edit now lets the admin select which master shipping methods are available for that product.
- Product add/edit now contains a quantity-based shipping-price table that follows the product price-table rows.
- The selected quantity on the storefront chooses the matching shipping tier and updates the shipping card price automatically.

## Database

A new table was added:

`product_shipping_method_price_tiers`

It stores per-product, per-shipping-method ranges:

- `minimum_quantity`
- `maximum_quantity`
- `price`
- `sort_order`

The migration also converts existing product shipping method charges into tier rows where possible, then makes the product shipping method itself a quantity-table method.

## Admin flow

1. Create Shipping Methods from Master Data.
2. Add or edit a product.
3. Create the product price-table quantity rows.
4. Enable shipping methods.
5. Select the shipping methods for this product.
6. Enter the shipping price for each quantity range and each selected method.
7. Save.

## Frontend flow

- The product builder receives shipping methods with `price_tiers`.
- When quantity changes, the selected shipping method finds the matching range.
- The shipping card displays `Included` if the range price is zero, otherwise it displays the extra price.
- The cart backend repeats the same quantity-tier calculation, so the server-side total matches the frontend total.
