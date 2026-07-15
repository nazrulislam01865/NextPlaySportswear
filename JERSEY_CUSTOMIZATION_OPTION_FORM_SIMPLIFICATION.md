# Jersey Customization Option Form Simplification

## Updated form order

The Master Data > Jersey Customization Options create/edit form now contains only:

1. Name
2. Type
3. Value (visible and required only when Type is Color)
4. Description
5. Optional image collection

The visible Sort Order and Active controls were removed. New and updated records are kept active automatically, while the existing internal sort value is preserved during edits.

## Compact image UI

The reusable image collection Blade component now supports a `compact` mode. The jersey customization form uses this mode to display each image as an 88 x 88 pixel thumbnail. Upload, secure image URL, image name, primary-image selection, removal, and multiple-image support remain available.

The list page also displays only a 40 x 40 pixel primary thumbnail and a count for additional images.

## Data changes

A nullable `description` column was added to `jersey_customization_options`.

- Fresh installations receive the column from the original create-table migration.
- Existing installations receive it from `2026_06_24_000003_add_description_to_jersey_customization_options.php`.

## Deployment

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```

The Vite production assets are included in the package.
