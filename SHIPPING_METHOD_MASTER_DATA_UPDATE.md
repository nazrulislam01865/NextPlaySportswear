# Shipping Method Master Data Update

Implemented shipping methods as reusable Master Data for product setup.

## What changed

- Added **Shipping Methods** under the admin **Master Data** sidebar group.
- Kept the existing shipping method form and list fully usable for create, edit, delete, active/default status, pricing, availability, and delivery estimate rules.
- Replaced the product add/edit inline shipping method builder with master-data selection cards.
- Product add/edit now allows admins to:
  - enable or disable shipping methods on the product page,
  - select one or more existing master shipping methods,
  - choose the default product-page shipping method,
  - open the master shipping method create form in a new tab.
- Added a link between `product_shipping_methods` and `shipping_methods` with master pricing fields copied into the product method record.
- Storefront product configuration and cart totals now support master shipping pricing as:
  - base price for the first item,
  - per-item price for each additional item,
  - optional free shipping minimum.
- Existing product-specific shipping method rows continue to work.

## Required deployment command

```bash
php artisan migrate --force
php artisan optimize:clear
```

Built JS assets and the Vite manifest were updated in this package to avoid browser cache issues.
