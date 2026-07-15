# Product Customization Toast Notifications Update

## Implemented behavior

A reusable storefront toast notification system has been added. It displays a white notification card with a colored circular icon, status title, message, close button, shadow, and animated progress bar.

Notifications now appear when a customer changes product configuration on the product details page, including:

- Image-based options such as fabric previews
- Color swatches
- Button and select options
- Checkbox/multi-select options
- Size quantities
- Text, number, date, and textarea custom fields when committed
- Product-level file and artwork uploads
- Jersey roster activation and completed roster fields
- Production speed
- Shipping method

Repeated changes to the same option replace the previous matching toast instead of creating an unlimited stack. A maximum of three notifications can be visible at once.

When an item is successfully stored in the cart, the cart page receives this flash toast:

> Added: Your order has been added to your shopping cart.

## Main files changed

- `resources/js/storefront.js`
- `resources/css/storefront.css`
- `resources/views/components/storefront/toast-center.blade.php`
- `resources/views/components/layouts/storefront.blade.php`
- `resources/views/components/storefront/product/option-group.blade.php`
- `resources/views/components/storefront/product/builder.blade.php`
- `app/Http/Controllers/Storefront/CartController.php`
- `public/build/manifest.json`
- `public/build/assets/storefront-*.css`
- `public/build/assets/storefront-*.js`

## Build and validation

The Vite production bundle was rebuilt with:

```bash
npm ci
npm run build
```

Validation completed:

- Storefront JavaScript syntax check
- 280 PHP files syntax checked
- 222 Blade templates compiled
- Toast component rendered through Laravel's view factory
- Compiled CSS and JavaScript checked for toast code

## Manual test

1. Open a configurable product details page.
2. Select a color, fabric, option, or size quantity.
3. Confirm a toast appears at the top-center of the page.
4. Add the configured product to the cart.
5. Confirm the cart page displays the successful cart toast.
