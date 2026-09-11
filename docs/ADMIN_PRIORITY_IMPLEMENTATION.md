# Admin Priority Implementation — 2026-09-11

This change set implements the three priority admin improvements without changing the existing storefront order creation or the established admin visual system.

## 1. RBAC and admin security

- Adds explicit `media.view` and `media.manage` permissions.
- Protects Media Library gallery/upload routes through the central `AdminRbac` resolver.
- Maps World Cup customization routes to `customization.view` / `customization.manage`.
- Forces Jersey fabric import to `customization.manage`.
- Maps Bulk Quote mutations and FlowTrack retry actions to `orders.manage` while preserving `orders.view` for read-only pages.
- Prevents `/admin/users/{user}` from binding a storefront customer as an editable admin target.
- Prevents non-Super-Admins from modifying a Super Admin account.
- Expands the first-allowed admin route resolver for existing modules.

## 2. Persistent FlowTrack order sync monitoring

The `orders` table now stores:

- sync status and attempt count
- FlowTrack order ID and order number
- FlowTrack job ID
- last attempt and successful sync timestamps
- latest safe error message
- last receiver response

The Admin Order list can filter by FlowTrack status and the Order detail page uses a reusable sync panel with a permission-protected retry action.

Integration state is saved without touching the order's business `updated_at` timestamp. Checkout remains independent from FlowTrack availability. Queue retries stay locked as `syncing` until success or final queue failure, preventing concurrent manual retries.

## 3. Bulk Quote workflow

Bulk Quote requests now support:

- status pipeline
- priority
- assigned admin
- quoted amount/currency
- internal admin note
- last contacted timestamp
- closed timestamp/admin
- persisted workflow activity history
- FlowTrack retry from Admin

The detail page shows the latest 50 workflow activities for predictable page performance while the database retains the full history.

## Reusable UI

New reusable components:

- `resources/views/components/admin/status-pill.blade.php`
- `resources/views/components/admin/integration-sync-panel.blade.php`

They use the existing admin layout, button classes, form classes, typography, theme colors, spacing and card system. No parallel CSS/theme layer was introduced.

## Deploy / verify

Run from the project root:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan test --filter=AdminRbacSecurityRegressionTest
php artisan test --filter=AdminUserSecurityTest
php artisan test --filter=BulkQuoteRequestAdminTest
php artisan test --filter=FlowTrackOrderSyncAdminTest
php artisan test
php artisan queue:restart
php artisan optimize:clear
```

For local development, omit `--force` from `php artisan migrate` if preferred.

If production uses queued FlowTrack delivery, keep the existing queue worker running for the configured integration queue.

## Verification performed on the delivered archive

- PHP syntax lint passed across all PHP files under `app`, `database/migrations`, `routes`, and `tests`.
- `composer.json` and `package.json` parse as valid JSON.
- New routes and reusable Blade components were statically verified.
- The supplied archive does not include `vendor/autoload.php` and the execution environment does not provide Composer, so Laravel/PHPUnit could not be executed in this sandbox. Run the commands above in the normal project environment before production deployment.

## Full-suite regression follow-up

After the first local full-suite run reported 40 failures, the shared root causes were corrected without rolling back the security/integration features. See `docs/TEST_REGRESSION_FIXES_2026-09-11.md` for the application fixes, stale test-harness updates, and targeted verification commands.
