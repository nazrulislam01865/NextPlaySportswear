# Global Vue Storefront Logo

The Header Settings page now includes a **Global Storefront Logo** upload.

- Stored on Laravel's `public` disk under `storefront/settings/branding/`.
- Returned by `/api/v1/storefront/bootstrap` as `site.logo` and `site.logo_alt`.
- Rendered through the reusable `resources/js/storefront/components/common/BrandLogo.vue` component.
- Used by the new Vue storefront header and footer and available to every migrated Vue storefront page through the same component/store.
- The legacy Blade storefront logo configuration is intentionally unchanged.
- Removing an uploaded logo falls back to `config('storefront.vue_logo')`.

Supported upload formats: PNG, JPG/JPEG, WEBP, maximum 4 MB and 4000x2000 pixels.
