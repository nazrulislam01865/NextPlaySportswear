# Shipping Method Simplified Master Data Update

Implemented changes:

- Product add/edit page now uses cleaner, equal-height shipping method cards.
- Removed the **Add in Master Data** button from the product add/edit shipping section.
- Product setup now only selects existing master shipping methods.
- Master Data → Shipping Methods form is simplified.
- Removed Availability Rules from the shipping method form.
- Removed Sort Order from the shipping method form.
- Pricing Rule now only has:
  - Extra charge
  - How it will apply: Included / per order / per product / per item
- Existing old pricing columns are kept for compatibility, but the new simplified fields are used going forward.
- Checkout and product-level shipping calculation support the simplified charge application.
- Built admin/storefront assets are included.

Deployment:

```bash
php artisan migrate --force
php artisan optimize:clear
```
