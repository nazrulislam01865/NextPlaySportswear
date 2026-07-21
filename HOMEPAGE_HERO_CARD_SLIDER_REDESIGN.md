# Homepage Hero Card Slider Redesign

Updated the storefront homepage hero right-side visual area so every slide behaves as an individual image card.

## What changed

- Replaced the old absolute/fade hero image carousel behavior with a real horizontal card track.
- Each image is now a separate card that slides in one by one.
- The next card is partially visible on desktop as a preview.
- Added smoother transform-based transitions with infinite-loop clone handling.
- Kept previous/next arrows, pagination dots, keyboard navigation, swipe gestures, hover pause, and automatic rotation.
- Retained the `500+ Teams — Trusted across the USA` badge.
- Kept all left-side hero content unchanged.
- Added responsive behavior so the preview card is visible on desktop/tablet and the active card uses full width on smaller mobile screens.

## Updated files

- `resources/views/components/storefront/home/hero.blade.php`
- `resources/css/storefront.css`
- `resources/js/storefront.js`
- `public/build/assets/storefront-DtB7IeKE.css`
- `public/build/assets/storefront-CF3xmX36.js`

No database migration is required.
