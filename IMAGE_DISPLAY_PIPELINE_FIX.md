# Image Display Pipeline Fix

## Problem

Uploaded images were written to `storage/app/public`, but the application generated absolute `/storage` URLs from `APP_URL`. Images failed when the configured host, port, or protocol differed from the current request, when the public storage symlink was absent, or when the Content Security Policy rejected the generated origin. Product edit also treated saved uploads like remote URLs, which could remove their stored-path identity on later saves.

## Resolution

### Application-owned media delivery

- Added `GET /media/{path}` through `PublicMediaController`.
- Files are streamed from Laravel's `public` disk, so rendering no longer depends on `public/storage` or `APP_URL`.
- Only existing image MIME types are served.
- Traversal and invalid paths are rejected.
- Long-lived browser caching and `nosniff` headers are applied.

### Central URL normalization

Added `App\Support\PublicMedia` to:

- generate same-origin `/media/...` URLs for stored uploads;
- repair legacy `/storage/...` and local absolute storage URLs;
- preserve genuine remote/CDN image URLs;
- keep a consistent image URL contract across admin and storefront screens.

### Product gallery persistence

- Existing uploaded product images keep their database `path` and physical file.
- Edit previews use the stored media URL without placing that URL into the remote image-link field.
- Existing image IDs are submitted and validated against the current product.
- Removed images delete their associated public-disk file.
- New uploads, remote links, image names, ordering, and primary-image selection remain supported.
- Legacy product images previously converted into local `/storage/...` URL records are repaired back into stored-path records when the product is saved.

### Updated image consumers

The shared media URL logic is now used for:

- product gallery images;
- jersey customization option images;
- size chart images and product size-group snapshots;
- category images and category content blocks;
- homepage slider images;
- catalog attribute images;
- product customization value images;
- account order item thumbnails;
- affected admin edit and list previews.

## Deployment

No migration is required.

```bash
php artisan optimize:clear
php artisan view:cache
```

The rebuilt Vite assets are included. `php artisan storage:link` is no longer required for the corrected image URLs, although an existing link can remain.

## Validation

- PHP syntax validation passed for the project.
- JavaScript syntax validation passed.
- Laravel routes loaded successfully, including the new media route.
- All Blade templates compiled successfully with the direct Blade compiler.
- Vite production build completed successfully.
- A stored JPEG was streamed through `PublicMediaController`; the response MIME type and bytes matched the source file.
