# Product Visible Pricing Merge Update

## Admin product editor

The existing section order and design remain unchanged. The following controls were removed:

- Additional categories
- Base price
- Minimum order quantity
- Maximum order quantity
- Separate Quantity pricing tiers editor

The **Visible storefront table** is now the only pricing editor.

Each visible price row contains:

- Min Qty
- Max Qty
- Existing customizable storefront columns

The storefront displays the row's **Min Qty** in the Quantity column. Max Qty is retained as trusted pricing logic and is not displayed as a separate customer column.

## Pricing source of truth

When a product is saved:

1. The highlighted table column is read as the live unit-price column.
2. Each visible row creates the corresponding trusted quantity-price tier.
3. Product minimum quantity is generated from the first tier.
4. Product maximum quantity is generated from the final bounded tier, or remains unlimited when the final Max Qty is blank.
5. Base price is generated from the unit price of the first quantity tier.
6. Storefront configuration and cart pricing continue to use server-side trusted tier records.

Quantity ranges must be continuous. Only the final row may have an empty Max Qty.

Existing products remain compatible. Their current tier records populate Min Qty and Max Qty in the editor, and the storefront immediately displays tier minimum quantities even before a product is resaved.

## Database

No migration is required. Existing product columns and `product_price_tiers` records are reused.

## Deployment

```bash
cd /var/www/nextplay

php artisan optimize:clear
php artisan view:cache
```

The complete package includes rebuilt Vite assets. When using the source-only patch and rebuilding on the server, also run:

```bash
npm ci
npm run build
```
