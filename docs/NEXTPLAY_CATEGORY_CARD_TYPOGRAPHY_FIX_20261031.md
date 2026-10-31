# NextPlay – category/subcategory card typography correction (31 October 2026)

The category page used the generic design-system H4 (20px) on category hierarchy tiles. The **actual homepage category card** uses `.96rem` (15.36px) and a `.72rem` (11.52px) count. This update gives the homepage, `/categories` and every depth of `/category/{slug}` the *same compact category card typography*. An existing generic homepage `#categories h3/p` rule was also overriding the shared card, so the updated stylesheet explicitly preserves the compact category card there too.

## New central theme tokens

- `--nps-category-card-title: .96rem` (15.36px), weight 800, line-height 1.25
- `--nps-category-card-description: .86rem` (13.76px)
- `--nps-category-card-count: .72rem` (11.52px)
- `--nps-category-card-link: .72rem` (11.52px)

These are defined only in `resources/css/storefront-theme.css`. Specific opt-in mappings under `.np-category-typography` are in `resources/css/storefront-typography.css`, loaded last as a generated `public/css/storefront-typography.css` stylesheet. Category hierarchy tiles use the compact tokens; informational cards and sport cards retain H4/body tokens.

The old 17.5rem category-index card body override is removed so the existing 16rem equal-height contract from `resources/css/storefront.css` applies. All data, images, categories, functionality, and card spacing otherwise remain unchanged.

## Explicitly excluded

- Canonical product cards and listing cards (markup/CSS untouched).
- Product filters, filter heading, mobile filter drawer, search, sorting, and Clear buttons (the previously restored styles remain untouched).
- Backend, routes, database, controller, migrations, queries.

## Deployment

Publish the complete archive or merge the changed-files package. The public stylesheet has **already been regenerated** and the shared layout cache-busting query was bumped to `20261031cards3`, so **no npm/Vite build is necessary**. On your server, preserve existing Expose font files, then run:

```bash
php artisan view:clear
```

Optional to rebuild only the typography asset: `python3 deploy/build-storefront-typography.py`.

## QA

Check `/categories` (category cards), a `/category/{slug}` with child category tiles (titles and counts), nested child category pages, and the original filters. Compare category title and count size to homepage. Confirm product listing cards remain visually identical.

Source CSS parse, generated CSS equality, PHP syntax and archive integrity are validated. Full Laravel/browser testing requires a working server and Composer dependencies.
