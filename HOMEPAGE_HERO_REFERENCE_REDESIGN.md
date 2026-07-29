# Homepage Hero Reference Redesign

Updated the storefront homepage hero section so the right side matches the supplied carousel mockup more closely while keeping all existing left-side content unchanged.

## What changed

- Replaced the old right-side visual behavior with a larger responsive carousel frame.
- Kept the left-side headline, description, checklist, buttons, trustline, and trust badges unchanged.
- Added a white rounded hero frame with a large active slide and a small next-slide preview on desktop.
- Added/retained carousel navigation arrows, pagination dots, keyboard support, swipe support, auto-rotation, and pause on hover/focus.
- Retained the `500+ Teams — Trusted across the USA` badge and fixed its stacking/visibility.
- Hid slide headings/copy from the next-slide preview so only the active slide shows content.
- Replaced the default graphic-style slides with optimized WebP jersey/product visuals.

## Updated files

- `resources/views/components/storefront/home/hero.blade.php`
- `resources/css/storefront.css`
- `public/build/assets/storefront-reference-hero.css`
- `public/storage/storefront/home/hero-slide-real-team-gear.webp`
- `public/storage/storefront/home/hero-slide-uniform-detail.webp`
- `public/storage/storefront/home/hero-slide-product-lineup.webp`

## Notes

No database migration is required.

After deploying, run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
npm install
npm run build
```
