# Storefront Update Notes

This update keeps the homepage design visually the same as the provided `nextplay-base44-style-homepage` template and adds one new feature: a full-width promotional image slider immediately after the menu/navigation bar.

## What changed

- Replaced the component-based Tailwind homepage output with the original template-style Blade markup for exact visual matching.
- Added a full-width slider section after the header menu and before the original hero section.
- Kept the original template CSS, responsive layout, header, hero, cards, bulk quote section, products, sports, FAQ, and footer style.
- Added plain JavaScript for slider controls, autoplay, dots, previous/next buttons, mobile menu, and FAQ toggle.
- The homepage does not require `npm run dev` because its CSS and JavaScript are inline like the original template.

## How to run

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

If you later change Vite/Tailwind assets for other pages, run:

```bash
npm run build
```

## Main updated file

```text
resources/views/storefront/home.blade.php
```

## Product View Customization Update

Added reusable storefront product detail implementation:

- Product catalog service with mock/admin-ready data arrays.
- Product controller with index and detail routes.
- Product detail page at `/product/{slug}` and legacy `/products/{slug}`.
- Reusable Blade components:
  - `resources/views/components/storefront/product/customizable-options.blade.php`
  - `resources/views/components/storefront/product/size-chart.blade.php`
  - `resources/views/components/storefront/product/price-table.blade.php`
  - `resources/views/components/storefront/product/gallery.blade.php`
- Homepage featured product buttons now open real product detail pages.
- JSON-LD keys in the standalone homepage were escaped for Blade compatibility.
- Product customization component currently displays one admin-defined option group, but supports multiple option groups for future admin configuration.
- Size chart component supports multiple groups/tabs such as Adult Unisex and Youth Unisex.
- Pricing table component supports quantity tier rows for future admin-managed price setup.

## Product View UI Refinement - Size Selector and Custom Options

Updated the product detail page to make the custom product ordering sections more user-friendly and reusable.

### Updated components
- `resources/views/components/storefront/product/customizable-options.blade.php`
  - Redesigned the admin-defined customizable option cards.
  - Improved visual card layout, selected state, badges, artwork upload, delivery selection, design notes, and action buttons.
  - Keeps support for one option group now and multiple option groups later.

- `resources/views/components/storefront/product/size-quantity-selector.blade.php`
  - Added a reusable Step 7 style size quantity selector.
  - Supports Men, Women, Youth, and Toddler size groups.
  - Includes live total quantity and estimated price calculation through Alpine.js.
  - Includes a View Size Chart modal.
  - Includes roster upload and custom size notes.

- `resources/views/components/storefront/product/detail-information.blade.php`
  - Added reusable detail information table.
  - Shows SKU, product type, fabric, collection tier, neckline, customization, MOQ, lead time, category, tags, and brand.

- `resources/views/components/storefront/product/price-table.blade.php`
  - Improved table layout and added estimated order total.

### Data updates
- `app/Services/Storefront/ProductCatalogService.php`
  - Added admin-ready data structures for detail information, size selector, size groups, price tiers, and product metadata.
  - Added exact basketball jersey detail information requested for `custom-basketball-jersey`.

## Cart Page Update

Implemented a clean, reusable storefront cart module.

### Added
- Storefront cart controller with thin controller methods.
- Session-backed cart service that recalculates all prices on the backend.
- Form request validation for add/update/coupon actions.
- Cart page at `/cart`.
- Cart item update/remove routes.
- Coupon apply/remove routes.
- Checkout placeholder route at `/checkout`.
- Reusable cart components:
  - `components/storefront/cart/item-card.blade.php`
  - `components/storefront/cart/summary-card.blade.php`
  - `components/storefront/cart/trust-panel.blade.php`
- Product detail page Add to Cart forms.
- Header cart count badge.

### Security and scalability notes
- Cart totals are not trusted from the browser.
- Unit prices, customization charges, discounts, shipping, and taxes are recalculated in `App\Services\Cart\CartService`.
- Validation is handled through Form Request classes.
- Cart data is currently session-backed for MVP and can be migrated to `carts` and `cart_items` database tables later.
- `/cart?preview=1` shows a sample cart for UI review without saving data.

## Customer Authentication UI Update

Added professional customer login and registration pages that follow the Laravel full-stack, API-ready architecture direction.

### Added files
- `app/Http/Controllers/Storefront/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Storefront/Auth/RegisteredUserController.php`
- `app/Http/Requests/Storefront/Auth/LoginRequest.php`
- `app/Http/Requests/Storefront/Auth/RegisterRequest.php`
- `resources/views/components/storefront/auth/shell.blade.php`
- `resources/views/components/storefront/auth/input.blade.php`
- `resources/views/storefront/auth/login.blade.php`
- `resources/views/storefront/auth/register.blade.php`

### Added routes
- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`
- `POST /logout`
- `GET /account`
- `GET /forgot-password` placeholder

### Notes
- Controllers remain thin and use Form Request validation.
- Login and registration POST routes are throttled.
- Auth pages are `noindex, nofollow`.
- Header now shows Login or My Account depending on auth state.
- Registration keeps checkout-proof custom-order terms visible to the customer.

## 2026-06-20 - Customer Account/Profile UI

Implemented a full customer account dashboard and secure profile management page.

### Added
- `/account` customer dashboard.
- `/account/profile` profile and password management.
- Placeholder-ready protected customer sections for orders, repeat orders, saved designs, saved carts, quotes, addresses, payment methods, support, and gift cards.
- Reusable account Blade components:
  - `resources/views/components/storefront/account/shell.blade.php`
  - `resources/views/components/storefront/account/action-card.blade.php`
  - `resources/views/components/storefront/account/stat-card.blade.php`
  - `resources/views/components/storefront/account/form-field.blade.php`
  - `resources/views/components/storefront/account/section-panel.blade.php`
- Clean controllers and requests:
  - `app/Http/Controllers/Storefront/Account/AccountController.php`
  - `app/Http/Controllers/Storefront/Account/ProfileController.php`
  - `app/Http/Requests/Storefront/Account/UpdateProfileRequest.php`
  - `app/Http/Requests/Storefront/Account/UpdatePasswordRequest.php`
- Reusable service:
  - `app/Services/Storefront/CustomerAccountService.php`
- Customer profile migration:
  - `2026_06_20_000001_add_customer_profile_fields_to_users_table.php`

### Security/structure
- All account routes are behind `auth` middleware.
- Profile update and password update are throttled.
- Password update requires current password.
- Form Requests validate all profile/password updates.
- Account pages are marked `noindex, nofollow`.
- No raw card/payment details are stored.
- Business/display metadata is kept out of Blade and prepared by `CustomerAccountService`.
