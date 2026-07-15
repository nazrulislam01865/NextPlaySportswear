# WebP / Cloud Upload Image Display Fix

## Root cause

Uploaded images are stored on Laravel's public disk (`storage/app/public`). In cloud deployments, `public/storage` may be a copied directory from the ZIP instead of a real symlink to `storage/app/public`. When that happens, new uploads save correctly but `/storage/...` URLs point at the stale copied directory, so WebP/product/category/homepage uploads do not display.

## Fix

- Added a session-free `/media/{path}` route that streams image files directly from `storage/app/public`.
- Changed `PublicMedia` generated URLs from `/storage/...` to `/media/...` by default.
- Added WebP/AVIF/JPG/PNG/GIF MIME fallback so WebP files return the correct `Content-Type`.
- Updated the Nginx static-cache snippet so `/media/...` can also be served directly by Nginx when the performance snippet is installed.

## After deploy

Run:

```bash
php artisan optimize:clear
php artisan cache:clear
```

No migration is required.
