# Homepage Popular Categories and Use Cases Removal

The following homepage sections were permanently removed:

- Popular Custom Sportswear Categories (`popular_categories`)
- Made for Play, Practice, Travel, and Team Events (`use_cases`)

## Storefront cleanup

- Removed both render cases from the homepage.
- Removed their Blade components.
- Removed the Popular Categories catalog query and homepage payload.
- Removed dedicated Use Cases CSS.

## Backend cleanup

- Removed both definitions from `HomepageSectionRegistry`.
- Added both keys to the retired-section list so stale database rows can never render or appear in admin management.
- Added a migration that deletes existing rows for both keys.
- Removed backend help text that referenced Popular Custom Sportswear Categories.

Run `php artisan migrate --force`, rebuild frontend assets, and clear application caches after deployment.
