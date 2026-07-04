# Admin Non-Product Minimal UI Update

This update makes the remaining admin pages visually lighter and closer to the already-clean Product list/Add Product screens.

## What changed

- Added route-aware admin body classes in `resources/views/components/layouts/admin.blade.php`.
- Product pages and Dashboard keep their current reference styling.
- All other admin pages get a lighter typography treatment through `body.admin-ui-minimalize`.
- Reduced heavy `font-black` / `font-extrabold` styling inside non-product admin page content.
- Reduced oversized headings, buttons, filters, stat cards, table headers, pills, and action buttons.
- Removed the overly bold/uppercase feel from large page headings while keeping labels and table headers clear.
- Updated the current compiled admin CSS so the change can apply immediately before a fresh Vite build.

## Files changed

- `resources/views/components/layouts/admin.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-8DPiPWIH.css`
- `ADMIN_NON_PRODUCT_MINIMAL_UI_UPDATE.md`

## Deploy notes

Run:

```bash
php artisan optimize:clear
php artisan optimize
```

When rebuilding frontend locally later:

```bash
npm ci
npm run build
```
