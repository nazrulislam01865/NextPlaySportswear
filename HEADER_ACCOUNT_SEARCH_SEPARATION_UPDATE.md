# Header account, search, and admin separation update

## Storefront header

- Removed the `Shop Now` button from the main header.
- Added an explicit red search submit button inside the right side of the desktop search field.
- Kept the responsive mobile search submit button inside the mobile search field.
- Replaced the former Login/profile control with a two-line `Hello, sign in / Account & Lists` dropdown.
- Guest dropdown actions are `Sign in` and `New customer? Start here.`
- Signed-in customer dropdown actions are `My Account` and `Sign out`.

## Admin separation

- The storefront header does not inspect the admin guard and contains no admin dashboard or admin logout link.
- An active admin session does not turn storefront customer routes into admin-dashboard redirects.
- Customer account, wishlist, and checkout authentication continues to use only the `web` guard.
- The admin panel remains accessible through its direct `/admin/login` or `/admin` URL.

## Updated files

- `resources/views/components/storefront/header.blade.php`
- `resources/css/storefront.css`
- `app/Http/Middleware/RedirectAdminFromCustomerArea.php`
- `tests/Feature/AuthenticationSeparationTest.php`
- `public/build/manifest.json`
- `public/build/assets/storefront-ed6191b4.css`
