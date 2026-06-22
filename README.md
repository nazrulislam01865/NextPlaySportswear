# NextPlay E-commerce

NextPlay is a Laravel 13 custom sportswear storefront with customer accounts, categories, checkout pages, orders, content pages, and a flexible product catalog.

## Admin catalog

A protected admin panel is available at:

```text
/admin/login
```

The product module supports categories, subcategories, featured products, inventory, variable pricing tables, dynamic customization options, sizes, artwork methods, production speeds, rich descriptions, images, FAQs, and complete product SEO control.

See [ADMIN_CATALOG_IMPLEMENTATION_NOTES.md](ADMIN_CATALOG_IMPLEMENTATION_NOTES.md) for the implementation details and setup instructions.

## Requirements

- PHP 8.3+
- Laravel-required PHP extensions
- PDO MySQL or PDO PostgreSQL
- Composer
- Node.js and npm
- MySQL or PostgreSQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Set secure admin credentials before production seeding:

```env
ADMIN_NAME="NextPlay Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="use-a-long-unique-password"
```

## Production cache

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Security notes

- Never trust browser pricing; the cart service recalculates product tiers and option surcharges.
- Keep `APP_DEBUG=false` in production.
- Use HTTPS, secure cookies, a production mail provider, and private storage for customer artwork.
- Change the local fallback admin password immediately.
