# NextPlay E-commerce

NextPlay is a Laravel 13 custom sportswear storefront with customer accounts, categories, catalog administration, checkout, persistent orders, payments, shipments, returns, refunds, invoices, private downloads, content pages, and flexible product customization.

## Admin panel

The protected admin login is available at:

```text
/admin/login
```

The catalog module supports categories, subcategories, featured products, inventory, variable pricing tables, dynamic customization options, sizes, artwork methods, production speeds, descriptions, images, FAQs, and product SEO.

The order operations module supports order review, payment reconciliation, split shipments, cancellation/change requests, returns/exchanges, refunds, credit notes, and private digital files. Order operations are restricted to Super Admin and Admin roles.

Implementation documentation:

- [ORDER_MANAGEMENT_IMPLEMENTATION_NOTES.md](ORDER_MANAGEMENT_IMPLEMENTATION_NOTES.md)
- [ADMIN_CATALOG_IMPLEMENTATION_NOTES.md](ADMIN_CATALOG_IMPLEMENTATION_NOTES.md)
- [CATEGORY_PAGE_IMPLEMENTATION_NOTES.md](CATEGORY_PAGE_IMPLEMENTATION_NOTES.md)
- [HOMEPAGE_SLIDER_AND_AUTH_SEPARATION_UPDATE.md](HOMEPAGE_SLIDER_AND_AUTH_SEPARATION_UPDATE.md)
- [RESPONSIVE_IMPLEMENTATION_NOTES.md](RESPONSIVE_IMPLEMENTATION_NOTES.md)

## Requirements

- PHP 8.3 or newer
- Laravel-required PHP extensions, including DOM, Mbstring, XML, XMLWriter, Fileinfo, OpenSSL, and PDO
- PDO MySQL or PDO PostgreSQL
- Composer
- Node.js and npm
- MySQL or PostgreSQL

## New installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

## Updating an existing installation

Back up the database and uploaded files first, then run:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm install
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set secure admin credentials before production seeding:

```env
ADMIN_NAME="NextPlay Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="use-a-long-unique-password"
```

## Security notes

- Never trust browser pricing; server services recalculate product tiers and customization surcharges.
- Keep `APP_DEBUG=false` in production.
- Use HTTPS and secure session cookies.
- Keep customer evidence, artwork, invoices, credit notes, and order downloads private.
- Raw card data must be collected only by a PCI-compliant payment provider and is never stored by NextPlay.
- Configure a real payment provider and signature-verified webhooks before accepting live online payments.
- Change all development/fallback administrator credentials before deployment.
