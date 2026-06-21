# Storefront Implementation Notes

This project has been updated with a Laravel full-stack storefront homepage based on reusable Blade components.

## What was added

- Storefront homepage route at `/`
- `HomeController` for the homepage request
- `HomePageService` to keep homepage data separate from Blade files
- SEO-friendly storefront layout with meta tags, Open Graph tags, Twitter cards, canonical URL, and JSON-LD schema
- Reusable Blade components:
  - topbar
  - header
  - nav link
  - full-width image slider
  - section heading
  - category card
  - product card
  - buyer path card
  - sport card
  - FAQ list
  - footer
- Tailwind v3 setup fixed
- Alpine.js configured for slider, mobile menu, and FAQ interactions
- Placeholder pages for products, quote request, and cart

## Run locally

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Build assets

```bash
npm run build
```

## Next suggested step

Create database tables and admin CRUD for categories, products, product images, and homepage slider items. After that, replace the temporary arrays in `HomePageService` with database queries.
