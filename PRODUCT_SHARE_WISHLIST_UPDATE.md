# Product Share and Wishlist Update

This project includes product-page Share and Wishlist controls for every existing and future storefront product.

## Included behavior

- Heart button over the upper-right corner of the product gallery.
- Empty and filled wishlist states.
- AJAX database persistence for authenticated customer accounts.
- Browser `localStorage` persistence for guests with a sign-in invitation.
- Native Web Share support, including the product image when the browser/device supports sharing files.
- Fallback share menu: Copy link, WhatsApp, Facebook, X, and Email.
- Canonical product URL, title, and product image metadata.
- Existing NextPlay toast confirmations and accessible keyboard/focus behavior.
- Precompiled storefront JavaScript in `public/build` so the feature loads immediately after deployment.

## Deployment

```bash
php artisan migrate --force
php artisan optimize:clear
```

No frontend build is required for this archive because the updated compiled storefront bundle and Vite manifest are included.
