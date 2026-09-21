# Storefront Upload Stability Fix — 2026-09-21

This update fixes the Vue storefront failure that could appear after an admin image/catalog/settings update invalidated caches.

## Changes

- `/api/v1/home` no longer depends on the legacy `NavigationService` or legacy menu tables.
- Global header/navigation/footer data remains exclusively in `/api/v1/storefront/bootstrap`.
- Homepage featured/latest/best-selling product queries are explicitly bounded.
- Header/footer/navigation uploaded media now uses the application-owned `/media/...` route through `PublicMedia`.
- Existing managed `/storage/storefront/settings/...` URLs are normalized to `/media/...` on read, so deployed installs do not require a data migration.
- Homepage section uploaded images now use the same `PublicMedia` URL strategy.
- The archive now contains a portable relative `public/storage -> ../storage/app/public` symlink instead of the machine-specific macOS XAMPP link.
- Regression contracts were added for homepage API separation, bounded product loading, and uploaded-media URL behavior.

## Deployment

After deploying, run the normal Laravel/Vite deployment steps and clear stale application caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci
npm run build
```

No database migration is required for this fix.
