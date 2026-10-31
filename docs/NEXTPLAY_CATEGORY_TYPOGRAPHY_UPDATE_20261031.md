# NextPlay — Category and catalog typography alignment (31 October 2026)

## Applied pages

The following storefront templates now reuse the homepage typography system: `storefront/categories/index.blade.php` (`/categories`), `storefront/categories/show.blade.php` (all parent, nested subcategory and product-category pages at `/category/{slug}`), and `storefront/products/index.blade.php` (`/products`). Their inline product-results partials also use the shared style for empty-state messages and buttons.

Non-product UI coverage: main titles, section headings, introductory descriptions, dark hero banners, breadcrumbs, category hierarchy cards, sport cards, category landing CMS blocks, content cards, testimonials/bulk CTA equivalents where present, FAQ, product-result summaries and empty states, pagination and contact details.

## Centralization and product-card isolation

- `resources/css/storefront-theme.css` remains the single source of type, family, button-size and color variables (`--nps-*`).
- `resources/css/storefront-typography.css` defines opt-in semantic classes and scoped page mappings under `.np-category-typography`.
- `public/css/storefront-typography.css` is a generated copy, created by `python3 deploy/build-storefront-typography.py` and loaded last in the shared storefront layout. Update source CSS, not the generated file.
- Expose is primary. Segoe UI is fallback. Desktop H1/H2/H3/H4 = 40/32/24/20px; body 16px, lead 18px, menu 15px, CTA 16px. Mobile headings and lead use existing responsive tokens. All colors reuse the approved navy, orange, text and muted color roles.
- The canonical `<x-storefront.product-card>` Blade component, its styling rules, and the compiled `resources/css/storefront.css` **were not modified**. The selector extension targets category page content and avoids the shared catalog filter panel. No backend, routes, queries, filtering behavior or database changes.

## Deployment

From the complete archive, copy the updated project files as normal, but **preserve your existing self-hosted `/public/fonts/expose/` fonts** (font binaries are intentionally not redistributed in these delivery archives). If the files are not already on the server, run your existing installer with the original Expose package you provided:

```bash
bash deploy/install-expose-fonts.sh '/absolute/path/to/Expose_Complete.zip'
python3 deploy/build-storefront-typography.py
php artisan view:clear
```

No database migration and no NPM/Vite build is required just for the typography patch. Shared layout version parameter is changed so browsers request the new generated stylesheet.

## Verification checklist

1. `/categories`: category filter tabs, cards, heading, Shop by Sport, bulk CTA and contact panel.
2. `/category/{slug}`: banner desktop/mobile, child cards, normal/quote-only pages, original sidebar and mobile filter drawer, result counts, sort control, clear filter action, pagination, FAQ and all CMS block types.
3. `/products`: catalog title, query/filtered heading, desktop and mobile filters, active filters, sorting, empty search, pagination.
4. At 1440px, 768px and 375px widths, confirm Expose loads and no clipped titles or controls. With keyboard, confirm visible focus rings on interactive elements.
5. Compare product card names, badges, prices, CTA type/size and layouts to the previous version: they should be unchanged.

### Validation limitations

CSS syntax and static project checks were performed. This source archive does not include installed Composer `vendor/` dependencies or a configured database, so full Laravel/browser integration tests cannot run until installed in a working NextPlay environment.

## Filter typography preservation — 31 October 2026

The sidebar and mobile filter drawer now intentionally use the **unchanged, original** filter styles from `resources/css/storefront.css` (Archive 2 baseline). The typography extension no longer overrides `.np-catalog-filter-*` headings, category names, search fields, option labels, counts, prices, reset/clear controls, sorting or their sizes. The category filter **Clear** button and mobile **Filters** trigger also use their exact previous classes.

This applies to the category page at every depth and to `/products`. The category titles, banners, categories/cards (not product cards), CMS body and pagination still use the homepage typography system. Regenerate the published CSS with `python3 deploy/build-storefront-typography.py` and clear Laravel's compiled views.
