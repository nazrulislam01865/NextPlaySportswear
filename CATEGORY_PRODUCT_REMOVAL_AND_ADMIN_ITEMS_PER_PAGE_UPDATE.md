# Category product removal and admin items-per-page update

## Category product assignment removal

- Added a **Remove from [category]** action to each product row on the admin category product-assignment page.
- The action is available for every direct assignment shown for the product, including its primary category.
- Removing an assignment never deletes the product.
- If the removed assignment was primary, another remaining leaf-category assignment is promoted automatically.
- If no assignments remain, the product becomes categoryless and its legacy category fields are cleared.
- Older direct legacy assignments can also be removed from the current category page.
- Category/product counts and storefront product caches are refreshed after removal.
- Category managers can remove assignments without needing the separate destructive-record delete permission.

## Admin pagination

- Added an eBay-style **Items per page** selector to standard admin pagination controls.
- Available values: 10, 15, 20, 25, 30, 40, 60, and 100.
- Existing search and filter query parameters are preserved.
- Changing the page size resets to page 1 to prevent invalid page numbers.
- The existing “Showing X to Y of Z items” summary remains visible.
- Dashboard mini-paginators and the media-library AJAX gallery keep their specialized behavior.

## Validation performed

- PHP syntax validation completed successfully across application, route, database, and test PHP files.
- Frontend source CSS and production manifest/assets were updated.
- Automated Laravel tests could not be executed in this environment because Composer is not installed.

## Main files changed

- `app/Http/Controllers/Admin/CategoryProductController.php`
- `app/Http/Controllers/Controller.php`
- `app/Support/AdminPagination.php`
- `app/Support/AdminRbac.php`
- Admin controllers containing standard paginated lists
- `resources/views/admin/categories/products.blade.php`
- `resources/views/pagination/nextplay.blade.php`
- `resources/css/admin.css`
- `resources/css/pagination.css`
- `routes/web.php`
- `public/build/manifest.json`
- `public/build/assets/admin-b3968cfb.css`
- `public/build/assets/storefront-9e32dfe5.css`
- `tests/Feature/CategoryDeletionAndLeafAssignmentTest.php`
- `tests/Unit/AdminPaginationTest.php`
- `tests/Unit/AdminRbacCategoryProductAssignmentTest.php`

## Cache note

- The stale generated route cache file was removed so the new assignment-removal route is available immediately. Run your normal `php artisan optimize`/cache deployment commands after release if desired.
