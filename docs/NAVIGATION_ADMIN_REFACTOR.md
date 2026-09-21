# Navigation Admin Refactor

- New admin location: Storefront / Store / Navigation Menu.
- Header Settings no longer owns header menu/mega-menu content.
- Vue desktop and mobile navigation both read `/api/v1/storefront/bootstrap.data.navigation.items`.
- Existing customized header navigation is copied once into the new `navigation` storefront setting when the new navigation row is absent.
- Legacy Navigation Menus admin CRUD is removed.
- Legacy `menus` and `menu_items` tables remain temporarily for rollback and old Blade dependencies.
- Do not delete those tables until the old Blade storefront is retired and regression-tested.

## Deployment

1. `php artisan migrate --force`
2. `php artisan storage:link` if the public-storage symlink does not already exist
3. `php artisan optimize:clear`
4. `npm install`
5. `npm run build`
