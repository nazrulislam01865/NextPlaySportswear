# Full Test-Suite Regression Fixes - 2026-09-11

This follow-up addresses the failure families reported from the local XAMPP `php artisan test` run (40 failed / 404 passed before these fixes).

## Application fixes

1. **Jersey color descriptions**
   - Color-based customization options now preserve their optional descriptions instead of normalizing them to `null`.
   - File: `app/Enums/JerseyCustomizationType.php`

2. **Reusable size-group redirect compatibility**
   - Default Jersey size groups redirect to the canonical edit URL without an unnecessary `?customization=jersey` query string.
   - Non-Jersey customization contexts keep their context query parameter.
   - File: `app/Http/Controllers/Admin/SizeOptionGroupController.php`

3. **Central email service + `Mail::fake()` compatibility**
   - The centralized email service now depends on Laravel's mail factory contract rather than the concrete `MailManager` class.
   - This preserves dependency inversion and allows Laravel's mail fake to replace the implementation during tests.
   - File: `app/Services/Email/CentralEmailService.php`

4. **Cart customization compatibility**
   - `CartService` uses the catalog service's backwards-compatible `findBySlug()` boundary.
   - The default implementation still resolves the full product payload, while custom/test catalog implementations can safely override the documented compatibility method.
   - File: `app/Services/Cart/CartService.php`

5. **Cross-database price filtering**
   - SQLite in-memory tests now cast the tier/base-price expression to a numeric value before bound range comparisons.
   - MySQL production SQL remains unchanged to avoid unnecessary live-query casting overhead.
   - File: `app/Services/Storefront/ProductCatalogService.php`

6. **Consistent RBAC denial**
   - Unauthorized admin module access returns HTTP 403 instead of silently redirecting to another module.
   - Admin 403 responses reuse the centralized Admin layout/theme/components; storefront 403 responses keep the storefront error design.
   - Files: `app/Http/Middleware/EnsureAdminPermission.php`, `resources/views/errors/403.blade.php`

## Test-harness / stale assertion fixes

1. The default homepage smoke test now uses `RefreshDatabase`, ensuring required catalog tables exist in SQLite memory.
2. Customer-session tests now seed the same security-version session value created by a real login; the stale/pre-deployment-session revocation tests remain unchanged.
3. Homepage collection assertions now target the current canonical reusable product-card markup instead of the intentionally removed legacy mobile list.
4. The size-extra-charge component assertion now targets the current alias-aware `flatMap` implementation.
5. PHPUnit data providers use PHPUnit 12 `#[DataProvider(...)]` attributes instead of legacy docblock metadata.
6. RBAC tests now consistently expect 403 for disallowed admin operations.

## Static verification completed

- PHP/Blade syntax lint: 757 files checked successfully.
- Merge-conflict markers: none in application/test PHP scope.
- Legacy `@dataProvider` annotations: none remaining.
- `CartService` direct `findFullBySlug()` calls: none remaining.
- Debug calls in the changed scope: none.
- SQLite numeric-affinity behavior was reproduced independently and the normalized expression returns the expected row.

## Required runtime verification

The provided archive does not contain `vendor/`, and Composer/PHP SQLite are not installed in the sandbox. Run this in the normal XAMPP project where the reported suite was executed:

```bash
php artisan optimize:clear

php artisan test --filter=JerseyCustomizationOptionTest
php artisan test --filter=SizeOptionGroupTest
php artisan test --filter=AuthenticationSeparationTest
php artisan test --filter=CentralizedEmailServiceTest
php artisan test --filter=ExampleTest
php artisan test --filter=HomepageProductCollectionsTest
php artisan test --filter=OrderManagementTest
php artisan test --filter=ProductConfigurationBackendTest
php artisan test --filter=StorefrontCatalogFiltersTest
php artisan test --filter=StorefrontContentPagesTest
php artisan test --filter=CustomerDirectoryTest
php artisan test --filter=AdminRbacSecurityRegressionTest

php artisan test
```

No database migration was added by this regression-fix follow-up. The three migrations from the Admin Priority implementation still need to be applied if they have not already been run.
