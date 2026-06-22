# Homepage Slider and Authentication Separation Update

## What was implemented

### Admin-managed homepage slider

A complete homepage slider module is now available at:

```text
/admin/homepage-slides
```

Administrators can:

- Add, edit, activate, deactivate, and delete slides.
- Upload JPG, PNG, WebP, or AVIF images up to 10 MB.
- Use a secure remote image URL instead of uploading.
- Set accessible image alt text and the image focal point.
- Show an image-only slide by turning off all text and buttons.
- Independently show or hide the eyebrow, title, description, primary button, and secondary button.
- Customize all slider text and button destinations.
- Open buttons in the same tab or a new tab.
- Choose left, center, or right content placement.
- Choose left, center, or right text alignment.
- Use light or dark text.
- Set the overlay color and opacity.
- Set slide display order.
- Schedule optional start and end dates.

The original three homepage promotions are preserved through an idempotent seeder. Existing database records are never overwritten when the seeder runs again.

### Performance

- Storefront slide data is cached as scalar arrays, so it remains compatible with `cache.serializable_classes=false`.
- Slider cache is invalidated after every admin create, update, status change, or delete.
- The service has request-level runtime caching through Laravel's singleton container binding.
- Only active, currently scheduled slides are queried.
- The storefront uses a reusable Blade component: `components/storefront/homepage-slider.blade.php`.

### Security

- Admin slider routes require the dedicated `admin` guard and administrator middleware.
- All forms use CSRF protection and validated Form Requests.
- Plain text is stripped of HTML before storage.
- CTA destinations are validated with the existing `SafePublicUrl` rule.
- Image uploads are validated by type, MIME, extension, size, and dimensions.
- Uploaded files receive generated storage filenames.
- Dynamic CSS values are restricted to validated allow-lists or strict HEX values.
- External links opened in a new tab receive `rel="noopener noreferrer"`.

## Admin/customer authentication separation

The application now uses separate session guards:

```text
Admin panel: admin guard
Customer panel: web guard
```

Rules now enforced:

- Admin accounts can authenticate only through `/admin/login`.
- Customer accounts can authenticate only through `/login`.
- Admin credentials are rejected by the customer login.
- Customer credentials are rejected by the admin login.
- An admin session cannot open customer login, registration, or account pages.
- Successful admin login removes any existing customer session.
- Customer account routes require an active user with the `customer` role.
- Admin routes require an active administrator role.
- The storefront header shows **Admin Dashboard** instead of customer login controls when an administrator is signed in.

Existing users, products, categories, menus, product customization, colors, fabric images, SEO, and category functionality were preserved.

## Additional correction

The existing category product-assignment Blade template contained an inline `@if ... @endif` sequence that compiled incorrectly. It was reformatted without changing its behavior. All Blade templates now compile successfully.

## Installation

From the project root, run:

```bash
php artisan optimize:clear
php artisan migrate --seed
php artisan storage:link

npm install
npm run build
```

The storage-link command may report that the link already exists; that is safe.

Because the admin guard changed, sign in again at:

```text
http://127.0.0.1:8000/admin/login
```

No database reset or `migrate:fresh` is required.

## Verification performed

- 143 PHP files passed `php -l` syntax validation.
- 135 Blade templates were compiled through Laravel's Blade compiler and the compiled PHP passed syntax validation.
- Homepage slider and authentication-separation feature tests were added.
- Admin slider routes were verified with `php artisan route:list`.

The container did not have the PHP DOM, XML, mbstring, XMLWriter, or PDO database extensions required to execute PHPUnit and database migrations. The Vite build could not run because `node_modules` was not present. Run the commands above in the normal project environment for final database, browser, and asset-build verification.
