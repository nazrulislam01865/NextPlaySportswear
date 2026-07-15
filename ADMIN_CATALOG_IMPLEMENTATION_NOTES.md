# NextPlay Admin Catalog & Product Details Implementation

## What was implemented

This release converts the product details experience into a database-driven Laravel catalog and adds a protected admin area.

### Admin access

- Admin login at `/admin/login`
- Role middleware allows `super_admin`, `admin`, and `catalog_manager`
- CSRF protection, login throttling, session regeneration, inactive-account blocking, and no-index admin pages
- Responsive reusable sidebar with the major e-commerce control areas:
  - Dashboard
  - Products
  - Categories & Subcategories
  - Inventory
  - Orders
  - Customers
  - Discounts & Coupons
  - Reviews
  - Content & Navigation
  - Shipping
  - Taxes
  - Payments
  - Reports
  - Store Settings

Product and category management are fully implemented. The other sidebar modules use the reusable admin shell and are ready for their dedicated workflows.

### Product administration

The admin can create, update, preview, duplicate, archive, feature, activate, and delete products. Each product supports:

- Category and subcategory
- Product name, SKU, slug, brand, type, badges, publication date, and sort order
- Draft, active, and archived status
- Featured or normal product placement
- Customizable or standard product behavior
- Base, compare-at, and cost pricing
- Product-specific quantity tiers
- Fully variable customer-facing price-table columns and rows
- Minimum and maximum quantity rules
- Stock tracking, low-stock threshold, and backorders
- Weight, dimensions, shipping class, and tax class
- Uploaded images and remote image URLs
- Primary image selection and image alt text
- Product tags
- Formatted rich-text product details
- Feature highlights and specification key/value rows
- Product FAQs
- SEO title, description, keywords, slug, canonical URL, Open Graph data, robots directives, and optional schema JSON

### Flexible customization

Every product can have its own option groups. Supported display/input types:

- Image cards
- Color swatches
- Buttons
- Select boxes
- Checkboxes
- Text
- Text area
- Number
- Date
- File upload

Each group can control:

- Customer-facing label and instructions
- Product or decoration section
- Required/optional status
- Minimum and maximum selections
- Upload file types and size limit
- Active/inactive status
- Unlimited option values

Each value can control:

- Label, code, description
- Color
- Image
- Price adjustment
- Option-level stock
- Default selection
- Active/inactive status

The admin can additionally configure size groups, individual sizes, artwork methods, upload requirements, production speeds, delivery ranges, surcharges, and FAQs.

### Storefront product page

The new database-driven product page uses reusable Blade components for:

- Image gallery with thumbnails below the main image
- Product summary and specifications
- Price table before the customization builder
- Dynamic option groups
- Size and quantity matrix
- Artwork methods and secure file upload
- Decoration fields
- Production speed selection
- Sticky live order summary
- Rich product description, specifications, and FAQ tabs
- Related products
- Product schema and configurable SEO metadata

### Security and pricing

- Admin rich text is sanitized on the server before storage.
- Uploaded product images and customer artwork are validated by type and size.
- Customer option IDs are checked against the selected product.
- Required options are validated again on the backend.
- Quantity, stock limits, tier price, option surcharges, size adjustments, artwork price, and production price are recalculated by the Laravel cart service.
- Browser totals are treated only as a customer-facing estimate.

## Installation

Add the following values to `.env` before seeding production:

```env
ADMIN_NAME="NextPlay Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="use-a-long-unique-password"
```

Then run:

```bash
composer install
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan optimize:clear
```

For local development only, when the admin environment variables are not supplied, the seeder creates:

- Email: `admin@nextplay.test`
- Password: `ChangeMe123!`

Change that password immediately. Production seeding refuses to continue without `ADMIN_PASSWORD`.

## Main new files

- `app/Http/Controllers/Admin/*`
- `app/Http/Middleware/EnsureAdmin.php`
- `app/Http/Requests/Admin/*`
- `app/Models/Product*.php`
- `app/Services/Security/SafeHtmlService.php`
- `database/migrations/2026_06_22_000003_add_admin_fields_to_users_table.php`
- `database/migrations/2026_06_22_000004_create_catalog_products_tables.php`
- `database/seeders/AdminUserSeeder.php`
- `database/seeders/ProductSeeder.php`
- `resources/views/admin/*`
- `resources/views/components/admin/*`
- `resources/views/components/storefront/product/*`

## MySQL index-name compatibility fix

MySQL limits index and constraint identifiers to 64 characters. The catalog migration now assigns explicit short names to the two composite indexes that exceeded that limit:

- `pog_product_section_active_sort_idx`
- `ppt_product_qty_range_idx`

If the earlier version failed while running `2026_06_22_000004_create_catalog_products_tables.php`, Laravel did not record that migration, but MySQL may already have created the `products` and `product_images` tables. In a disposable local database, run `php artisan migrate:fresh --seed`. To preserve unrelated data, remove only the partially created catalog tables before rerunning `php artisan migrate --seed`.
