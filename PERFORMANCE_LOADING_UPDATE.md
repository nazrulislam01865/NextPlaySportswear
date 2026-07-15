# Loading and Caching Update

This update implements the storefront loading optimizations requested for the Laravel project.

## Implemented changes

### 1. Nginx browser caching and compression

Production-ready Nginx snippets are included in `deploy/nginx/`:

- `nextplay-static-cache.conf.example`
- `nextplay-compression-gzip.conf.example`
- `nextplay-compression-brotli.conf.example`
- `install-performance-snippets.sh`

The static-cache configuration gives Vite's hashed `/build/` assets a one-year immutable cache policy, serves `/storage/` directly from `storage/app/public`, and caches repository images/fonts for 30 days.

The compression configuration enables Gzip and includes a Brotli configuration for servers with the Brotli Nginx modules loaded.

### 2. Direct public image delivery

`App\Support\PublicMedia` now generates `/storage/...` URLs instead of `/media/...` URLs. The Laravel `PublicMediaController` route was removed, and `public/storage` is the normal Laravel symlink to `storage/app/public`.

After a deployment, ensure the link exists:

```bash
php artisan storage:link
```

### 3. Redis cache and sessions

Redis is now the default application cache and session backend. Separate Redis databases are configured for general Redis use, cache data and sessions:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=sessions
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
```

The application uses the `phpredis` client by default, so the PHP Redis extension must be installed and enabled on the production PHP-FPM server.

### 4. Limited homepage product query

`ProductCatalogService::featured()` no longer loads and transforms the complete product catalog. It now performs a cached, limited query for featured products and fills any remaining slots with a second limited published-product query.

### 5. Lightweight header cart count

`CartService::count()` now sums the stored cart quantities directly. It no longer loads products, recalculates customization prices or writes calculated cart values merely to display the header quantity badge.

## Local development (XAMPP / no Redis)

Redis remains the recommended production backend, but local development now
defaults to file cache and file sessions. Use:

```env
APP_ENV=local
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_CONNECTION=
```

Do not combine `SESSION_DRIVER=database` with
`SESSION_CONNECTION=sessions`; `sessions` is the dedicated Redis connection
name. The updated session configuration safely normalizes this old invalid
combination, but correcting `.env` is still recommended.

## Deployment sequence

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan down
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
php artisan up
```

Install the Nginx snippets once, using the deployed project path:

```bash
sudo bash deploy/nginx/install-performance-snippets.sh /var/www/nextplay
```

Then include the generated snippets inside the site's existing `server {}` block and run `sudo nginx -t` before reloading Nginx.
