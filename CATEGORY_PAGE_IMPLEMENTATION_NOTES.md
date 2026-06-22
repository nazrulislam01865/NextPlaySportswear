# Database-Driven Category Page Implementation

## What changed

The category landing page now follows the supplied `nextplay-category-index-no-intro.html` template inside the existing Laravel storefront layout.

The previous PHP-array category definitions were removed. Category content now comes from normalized database tables.

## Database structure

### `categories`

Stores both product collections and sport categories.

Important fields include:

- `parent_id` for future parent/subcategory support
- `name` and unique `slug`
- `display_type`: `collection`, `sport`, or `navigation`
- descriptive content, image, alt text, CTA label
- SEO title and description
- product matching rules and highlights as JSON
- active status and sort order

### `category_tags`

Stores the category filter tabs, such as:

- Team Uniforms
- Custom Jerseys
- Apparel
- Headwear
- Bags
- Fan Gear
- Promotional Products
- Accessories

### `category_category_tag`

Many-to-many relationship allowing one category to appear under multiple filter tabs.

## Main implementation files

- `app/Models/Category.php`
- `app/Models/CategoryTag.php`
- `app/Services/Storefront/CategoryCatalogService.php`
- `app/Http/Controllers/Storefront/CategoryController.php`
- `database/migrations/2026_06_22_000001_create_storefront_categories_tables.php`
- `database/seeders/CategorySeeder.php`
- `resources/views/storefront/categories/index.blade.php`
- `resources/views/components/storefront/category-index-card.blade.php`
- `resources/views/components/storefront/sport-index-card.blade.php`

## Template sections reproduced

1. Browse Categories filter tabs
2. All Product Categories
3. Shop by Sport
4. Customize Your Gear Your Way
5. Team and Bulk Ordering
6. Not Sure Where to Start?
7. Find the Right Product Without Guesswork
8. Category Shopping Questions
9. Final category CTA

The page remains responsive and uses Alpine only for category filtering and FAQ disclosure behavior.

## Database setup

Run:

```bash
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force
```

For a new installation, the standard database seeder also calls `CategorySeeder`:

```bash
php artisan migrate --seed --force
```

## Verification

Completed checks:

- PHP syntax validation for models, migration, seeder, controller, and service
- Blade compilation for the category page and changed shared components
- Vite production asset build
- 26 unique category records defined: 15 visible product collections, 10 sports, and 1 navigation-only apparel collection
- 8 database-backed filter tags
- all shared header and footer category slugs verified against the seeded records
- PHPUnit feature coverage updated to seed and assert database category records

The container PHP installation lacks DOM, mbstring, XML, XMLWriter, and PDO database drivers, so PHPUnit could not execute in this environment. This is an environment limitation; the test files remain included.
