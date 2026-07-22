# Homepage Hero Carousel Update

Updated the storefront homepage hero section so the left-side heading, text, checklist, CTAs, and trust indicators remain unchanged while the right-side static image is replaced by a responsive carousel.

## Changed
- `resources/views/components/storefront/home/hero.blade.php`
  - Replaced the static `<picture>` hero image with carousel markup.
  - Added slide support for image, heading, supporting text, and label/badge.
  - Kept the existing `500+ Teams — Trusted across the USA` badge.

- `resources/js/storefront.js`
  - Added hero carousel behavior.
  - Supports autoplay, pause on hover/focus, arrows, dots, keyboard left/right controls, and swipe gestures.

- `resources/css/storefront.css`
  - Added responsive hero carousel styles, smooth transitions, slide overlays, navigation arrows, pagination dots, and desktop next-slide preview.

- `public/storage/storefront/home/hero-slide-team-jerseys.webp`
- `public/storage/storefront/home/hero-slide-custom-kit.webp`
- `public/storage/storefront/home/hero-slide-team-store.webp`
  - Optimized default slide images.

- `public/build/*`
  - Rebuilt Vite assets.

## Deploy
```bash
php artisan optimize:clear
php artisan view:clear
npm install
npm run build
```
