# Jersey Customization Master Data

## Scope

This update adds the first stage of reusable jersey customization management. It creates the master-data list and CRUD form only. Product assignment is intentionally left for the next stage.

## Admin navigation

A reusable collapsible **Master Data** menu was added to the admin sidebar with this submenu:

- Jersey Customization Options

## Supported types

The type list is centrally declared in `App\Enums\JerseyCustomizationType`:

- Neck and Collar
- Fabric
- Color
- Sleeves and Cuffs
- Jersey Style

## Form behavior

The form contains only the fields needed for this master record:

- Type
- Option name
- Color code, shown and required only for Color
- Sort order
- Active status
- Optional image collection

Each option can contain zero, one, or multiple images. Each image row supports:

- Name
- Uploaded image
- Image link
- Primary image selection
- Preview
- Removal

Uploaded files and image links cannot be used simultaneously in the same row. The first saved image becomes primary automatically when the admin does not choose one.

## Code organization

- Enum: `app/Enums/JerseyCustomizationType.php`
- Models:
  - `app/Models/JerseyCustomizationOption.php`
  - `app/Models/JerseyCustomizationOptionImage.php`
- Validation: `app/Http/Requests/Admin/JerseyCustomizationOptionRequest.php`
- Persistence/media service: `app/Services/Catalog/JerseyCustomizationOptionService.php`
- Controller: `app/Http/Controllers/Admin/JerseyCustomizationOptionController.php`
- Migration: `database/migrations/2026_06_24_000002_create_jersey_customization_options_tables.php`
- Views: `resources/views/admin/jersey-customization-options/`
- Reusable admin components:
  - `resources/views/components/admin/sidebar-group.blade.php`
  - `resources/views/components/admin/sidebar-sub-link.blade.php`
  - `resources/views/components/admin/image-collection-field.blade.php`
- Feature test: `tests/Feature/Admin/JerseyCustomizationOptionTest.php`

## Security and data integrity

- Admin-only routes use the existing `auth:admin` and `admin` middleware.
- Form Request authorization verifies an active admin account.
- Type values are restricted to the enum.
- Option slugs are generated server-side and unique within each type.
- Image count is limited to 20 per option.
- Uploaded images are limited to JPG, JPEG, PNG, WebP, or AVIF and 5 MB each.
- External links pass the existing safe public URL validation rule.
- Existing image IDs are checked against the edited option before update.
- Only one primary image is stored.
- Database writes and image synchronization run in a transaction.

## Deployment

Run:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```

Ensure the public storage link exists:

```bash
php artisan storage:link
```
