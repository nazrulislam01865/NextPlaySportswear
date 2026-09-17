# Vue Homepage First-Fold Update

This update is intentionally limited to the new Vue storefront first fold: utility bar, primary header/navigation, and hero.

## Architecture

- `/` remains the Vue 3 storefront homepage.
- The legacy Blade homepage remains in the repository only as rollback code and is not routed publicly.
- All other storefront pages remain on their existing Laravel/Blade routes.
- Homepage content continues to come from Laravel `/api/v1` endpoints through the centralized Vue API client.

## Centralized frontend primitives

- Theme/layout tokens: `resources/js/storefront/styles/tokens.css`
- Fonts/typography: shared font tokens and global typography styles
- Buttons: `resources/js/storefront/components/ui/AppButton.vue`
- Brand: `resources/js/storefront/components/common/BrandLogo.vue`
- Carousel controls: `resources/js/storefront/components/common/CarouselControls.vue`
- Utility/header/hero remain separate reusable modules.

## Apply locally

```bash
npm install
npm run build
php artisan migrate
php artisan optimize:clear
php artisan serve
```

For active frontend development use `npm run dev` in a second terminal.

The package intentionally does not include a stale `package-lock.json` or old `public/build`; `npm install` generates a lock file for the current Vue dependency set and `npm run build` creates fresh Vite assets.
