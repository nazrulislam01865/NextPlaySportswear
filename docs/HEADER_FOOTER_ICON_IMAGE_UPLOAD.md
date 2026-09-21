# Header/Footer uploaded icon images

The new Vue storefront Header/Footer settings use uploaded raster images instead of icon dropdowns.

- Header utility links: PNG/JPG/WEBP upload, max 2 MB, max 1024x1024.
- Footer menu links: PNG/JPG/WEBP upload.
- Footer social links: PNG/JPG/WEBP upload.
- Footer legal links: PNG/JPG/WEBP upload.
- Existing legacy preset icon values remain supported as a compatibility fallback until replaced.
- New files are stored on the Laravel `public` disk under `storefront/settings/icons/...`.
- Replaced/removed managed icon files are deleted after the settings update succeeds.
- Vue renders uploaded icon URLs through the reusable `StorefrontLinkIcon.vue` component.

The settings forms use `multipart/form-data`. Ensure the standard Laravel public storage symlink exists (`php artisan storage:link`) on a fresh deployment.
