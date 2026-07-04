<?php

namespace App\Support;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminRbac
{
    public const DELETE_PERMISSION_KEY = 'records.delete';

    /** @return array<int, array<string, mixed>> */
    public static function roles(): array
    {
        return [
            [
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Full store owner access. Can manage every module, administrator and permission.',
                'sort_order' => 1,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'admin',
                'name' => 'Admin',
                'description' => 'Store administrator with broad catalog, order, customer, store and report access.',
                'sort_order' => 2,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'catalog_manager',
                'name' => 'Catalog Manager',
                'description' => 'Manages products, categories, options, size charts, menus and storefront content.',
                'sort_order' => 3,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'order_manager',
                'name' => 'Order Manager',
                'description' => 'Manages orders, shipments, returns, customer requests and order downloads.',
                'sort_order' => 4,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'support_agent',
                'name' => 'Support Agent',
                'description' => 'Views orders, returns, customer records and support-oriented reports.',
                'sort_order' => 5,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'slug' => 'content_manager',
                'name' => 'Content Manager',
                'description' => 'Manages homepage slides, navigation menus, static content, shipping and payment display data.',
                'sort_order' => 6,
                'is_system' => true,
                'is_active' => true,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function permissions(): array
    {
        return [
            self::permission('dashboard.view', 'Dashboard', 'View', 'View Dashboard', 'Open the admin dashboard.', 'admin.dashboard', 10),

            self::permission('products.view', 'Catalog', 'View', 'View Products', 'Open product lists, product previews, product stats and admin product search suggestions.', 'admin.products.index', 20),
            self::permission('products.manage', 'Catalog', 'Manage', 'Manage Products', 'Create, update, duplicate and maintain product details, pricing, options, fulfillment, descriptions and FAQs.', 'admin.products.store', 21),
            self::permission('categories.view', 'Catalog', 'View', 'View Categories', 'Open category lists, category ordering and assigned product pages.', 'admin.categories.index', 30),
            self::permission('categories.manage', 'Catalog', 'Manage', 'Manage Categories', 'Create, update, import, export, duplicate, reorder and assign category products.', 'admin.categories.store', 31),
            self::permission('attributes.view', 'Catalog', 'View', 'View Catalog Attributes', 'Open catalog attribute records.', 'admin.attributes.index', 40),
            self::permission('attributes.manage', 'Catalog', 'Manage', 'Manage Catalog Attributes', 'Create and update catalog attributes.', 'admin.attributes.store', 41),
            self::permission('customization.view', 'Catalog', 'View', 'View Customization Master Data', 'Open product customization options and size option groups.', 'admin.jersey-customization-options.index', 50),
            self::permission('customization.manage', 'Catalog', 'Manage', 'Manage Customization Master Data', 'Create and update product customization options and size option groups.', 'admin.jersey-customization-options.store', 51),
            self::permission('menus.view', 'Catalog', 'View', 'View Navigation Menus', 'Open admin navigation menu records.', 'admin.menus.index', 60),
            self::permission('menus.manage', 'Catalog', 'Manage', 'Manage Navigation Menus', 'Create and update storefront navigation menus.', 'admin.menus.store', 61),
            self::permission('inventory.view', 'Catalog', 'View', 'View Inventory', 'Open inventory placeholder and stock-related dashboard links.', 'admin.modules.show', 70),

            self::permission('orders.view', 'Commerce', 'View', 'View Orders', 'Open orders, order details, shipments, order downloads and customer change requests.', 'admin.orders.index', 100),
            self::permission('orders.manage', 'Commerce', 'Manage', 'Manage Orders', 'Update order status, payment status, fulfillment status, shipments, change requests and order downloads.', 'admin.orders.update', 101),
            self::permission('returns.view', 'Commerce', 'View', 'View Returns & Exchanges', 'Open return and exchange requests and their attachments.', 'admin.returns.index', 110),
            self::permission('returns.manage', 'Commerce', 'Manage', 'Manage Returns & Exchanges', 'Update return and exchange status, refunds and review notes.', 'admin.returns.update', 111),
            self::permission('customers.view', 'Commerce', 'View', 'View Customers', 'Open customer management placeholder and customer dashboard links.', 'admin.modules.show', 120),
            self::permission('coupons.view', 'Commerce', 'View', 'View Coupons', 'Open discounts and coupon lists.', 'admin.coupons.index', 130),
            self::permission('coupons.manage', 'Commerce', 'Manage', 'Manage Coupons', 'Create and update discounts and coupons.', 'admin.coupons.store', 131),
            self::permission('reviews.view', 'Commerce', 'View', 'View Reviews', 'Open reviews placeholder module.', 'admin.modules.show', 140),

            self::permission('homepage_slides.view', 'Storefront', 'View', 'View Homepage Slider', 'Open homepage slide records.', 'admin.homepage-slides.index', 170),
            self::permission('homepage_slides.manage', 'Storefront', 'Manage', 'Manage Homepage Slider', 'Create, update and toggle homepage slides.', 'admin.homepage-slides.store', 171),
            self::permission('content.view', 'Storefront', 'View', 'View Content', 'Open content and navigation placeholder module.', 'admin.modules.show', 180),
            self::permission('content.manage', 'Storefront', 'Manage', 'Manage Content', 'Manage content-oriented store modules and navigation content.', 'admin.modules.show', 181),
            self::permission('shipping.view', 'Catalog', 'View', 'View Shipping Methods', 'Open shipping method master data records.', 'admin.shipping-methods.index', 190),
            self::permission('shipping.manage', 'Catalog', 'Manage', 'Manage Shipping Methods', 'Create and update shipping method master data records.', 'admin.shipping-methods.store', 191),
            self::permission('rural_surcharges.view', 'Storefront', 'View', 'View Rural Surcharges', 'Open rural area surcharge records.', 'admin.rural-area-surcharges.index', 200),
            self::permission('rural_surcharges.manage', 'Storefront', 'Manage', 'Manage Rural Surcharges', 'Create and update rural area surcharges.', 'admin.rural-area-surcharges.store', 201),
            self::permission('payment_methods.view', 'Storefront', 'View', 'View Payment Methods', 'Open payment method records and payment placeholder modules.', 'admin.payment-methods.index', 210),
            self::permission('payment_methods.manage', 'Storefront', 'Manage', 'Manage Payment Methods', 'Create and update payment methods.', 'admin.payment-methods.store', 211),
            self::permission('taxes.view', 'Storefront', 'View', 'View Taxes', 'Open taxes placeholder module.', 'admin.modules.show', 220),

            self::permission('reports.view', 'Reports', 'View', 'View Reports', 'Open report placeholder module and report dashboard links.', 'admin.modules.show', 250),
            self::permission('settings.view', 'System', 'View', 'View Settings', 'Open store settings placeholder module.', 'admin.modules.show', 280),
            self::permission('settings.manage', 'System', 'Manage', 'Manage Settings', 'Manage store-level settings when implemented.', 'admin.modules.show', 281),
            self::permission('users.view', 'System', 'View', 'View Admin Users', 'Open admin user records and role assignment page.', 'admin.users.index', 290),
            self::permission('users.manage', 'System', 'Manage', 'Manage Admin Users', 'Create admin users, update roles, reset passwords and deactivate admin accounts.', 'admin.users.store', 291),
            self::permission('role_matrix.view', 'System', 'View', 'View Role Matrix', 'Open the role and permission matrix.', 'admin.role-matrix.index', 300),
            self::permission('role_matrix.manage', 'System', 'Manage', 'Manage Role Matrix', 'Create roles and update permission access for admin roles.', 'admin.role-matrix.update', 301),
            self::permission(self::DELETE_PERMISSION_KEY, 'System', 'Delete', 'Delete Records', 'Delete records from admin modules. Super Admin has this by default and can grant it to other roles.', null, 310),
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function defaultAllowedPermissions(): array
    {
        $catalogAll = [
            'dashboard.view',
            'products.view', 'products.manage',
            'categories.view', 'categories.manage',
            'attributes.view', 'attributes.manage',
            'customization.view', 'customization.manage',
            'menus.view', 'menus.manage',
            'inventory.view',
            'homepage_slides.view', 'homepage_slides.manage',
            'content.view', 'content.manage',
        ];

        $commerceAll = [
            'dashboard.view',
            'orders.view', 'orders.manage',
            'returns.view', 'returns.manage',
            'customers.view',
            'reports.view',
        ];

        $contentAll = [
            'dashboard.view',
            'menus.view', 'menus.manage',
            'homepage_slides.view', 'homepage_slides.manage',
            'content.view', 'content.manage',
            'shipping.view', 'shipping.manage',
            'rural_surcharges.view', 'rural_surcharges.manage',
            'payment_methods.view', 'payment_methods.manage',
        ];

        $adminAllExceptProtected = collect(self::permissions())
            ->pluck('key')
            ->reject(fn (string $key): bool => in_array($key, [self::DELETE_PERMISSION_KEY, 'role_matrix.manage'], true))
            ->values()
            ->all();

        return [
            'super_admin' => collect(self::permissions())->pluck('key')->all(),
            'admin' => $adminAllExceptProtected,
            'catalog_manager' => $catalogAll,
            'order_manager' => $commerceAll,
            'support_agent' => ['dashboard.view', 'orders.view', 'returns.view', 'customers.view', 'reports.view'],
            'content_manager' => $contentAll,
        ];
    }

    public static function syncDefaults(bool $forceDefaultPermissions = false): void
    {
        if (! Schema::hasTable('admin_roles') || ! Schema::hasTable('admin_permissions')) {
            return;
        }

        $now = now();

        foreach (self::roles() as $role) {
            AdminRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'sort_order' => $role['sort_order'],
                    'is_system' => $role['is_system'],
                    'is_active' => $role['is_active'],
                ]
            );
        }

        foreach (self::permissions() as $permission) {
            AdminPermission::query()->updateOrCreate(
                ['key' => $permission['key']],
                [
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                    'route_name' => $permission['route_name'],
                    'sort_order' => $permission['sort_order'],
                ]
            );
        }

        if (! Schema::hasTable('admin_role_permissions')) {
            return;
        }

        $permissionIds = AdminPermission::query()->pluck('id', 'key');
        $defaults = self::defaultAllowedPermissions();

        AdminRole::query()->get()->each(function (AdminRole $role) use ($permissionIds, $defaults, $forceDefaultPermissions, $now): void {
            $allowedKeys = $role->isSuperAdmin()
                ? $permissionIds->keys()->all()
                : ($defaults[$role->slug] ?? []);

            foreach ($permissionIds as $permissionKey => $permissionId) {
                $allowed = in_array((string) $permissionKey, $allowedKeys, true);
                $exists = DB::table('admin_role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if ($forceDefaultPermissions || ! $exists || $role->isSuperAdmin()) {
                    DB::table('admin_role_permissions')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permissionId],
                        [
                            'allowed' => $allowed,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
    }

    public static function schemaReady(): bool
    {
        return Schema::hasTable('admin_roles')
            && Schema::hasTable('admin_permissions')
            && Schema::hasTable('admin_role_permissions');
    }

    public static function roleIsAdmin(?string $roleSlug): bool
    {
        $roleSlug = trim((string) $roleSlug);

        if ($roleSlug === '' || $roleSlug === 'customer') {
            return false;
        }

        if (! self::schemaReady()) {
            return in_array($roleSlug, ['super_admin', 'admin', 'catalog_manager', 'order_manager', 'support_agent', 'content_manager'], true);
        }

        return AdminRole::query()
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->exists();
    }

    public static function roleLabel(?string $roleSlug): string
    {
        $roleSlug = trim((string) $roleSlug);
        if ($roleSlug === '') {
            return 'No role';
        }

        if (self::schemaReady()) {
            $name = AdminRole::query()->where('slug', $roleSlug)->value('name');
            if ($name) {
                return (string) $name;
            }
        }

        return Str::of($roleSlug)->replace(['_', '-'], ' ')->title()->toString();
    }

    public static function userCan(?User $user, string $permissionKey): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        if (! self::schemaReady()) {
            return self::legacyUserCan($user, $permissionKey);
        }

        return DB::table('admin_role_permissions')
            ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_permissions.role_id')
            ->join('admin_permissions', 'admin_permissions.id', '=', 'admin_role_permissions.permission_id')
            ->where('admin_roles.slug', $user->role)
            ->where('admin_roles.is_active', true)
            ->where('admin_permissions.key', $permissionKey)
            ->where('admin_role_permissions.allowed', true)
            ->exists();
    }

    public static function legacyUserCan(User $user, string $permissionKey): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'admin') {
            return $permissionKey !== self::DELETE_PERMISSION_KEY;
        }

        if ($user->role === 'catalog_manager') {
            return in_array($permissionKey, self::defaultAllowedPermissions()['catalog_manager'] ?? [], true);
        }

        return false;
    }

    public static function firstAllowedRoute(?User $user): ?string
    {
        $routesByPermission = [
            'dashboard.view' => 'admin.dashboard',
            'products.view' => 'admin.products.index',
            'orders.view' => 'admin.orders.index',
            'categories.view' => 'admin.categories.index',
            'homepage_slides.view' => 'admin.homepage-slides.index',
            'coupons.view' => 'admin.coupons.index',
            'users.view' => 'admin.users.index',
            'role_matrix.view' => 'admin.role-matrix.index',
        ];

        foreach ($routesByPermission as $permission => $route) {
            if (self::userCan($user, $permission) && function_exists('route')) {
                return route($route);
            }
        }

        return null;
    }

    public static function permissionForRoute(?string $routeName, Request $request): ?string
    {
        if (! $routeName || ! Str::startsWith($routeName, 'admin.')) {
            return null;
        }

        $name = Str::after($routeName, 'admin.');

        if (in_array($name, ['login', 'login.store', 'logout'], true)) {
            return null;
        }

        if ($name === 'dashboard') {
            return 'dashboard.view';
        }

        if (Str::startsWith($name, 'products.')) {
            return self::resourcePermission($name, 'products');
        }

        if (Str::startsWith($name, 'categories.products.')) {
            return (Str::endsWith($name, '.update') || Str::endsWith($name, '.sync-legacy')) ? 'categories.manage' : 'categories.view';
        }

        if (Str::startsWith($name, 'categories.ordering')) {
            return Str::endsWith($name, '.update') ? 'categories.manage' : 'categories.view';
        }

        if (Str::startsWith($name, 'categories.')) {
            if (in_array($name, ['categories.bulk', 'categories.duplicate', 'categories.import', 'categories.export'], true)) {
                return 'categories.manage';
            }
            return self::resourcePermission($name, 'categories');
        }

        foreach ([
            'attributes' => 'attributes',
            'menus' => 'menus',
            'coupons' => 'coupons',
            'homepage-slides' => 'homepage_slides',
            'shipping-methods' => 'shipping',
            'rural-area-surcharges' => 'rural_surcharges',
            'payment-methods' => 'payment_methods',
            'users' => 'users',
        ] as $routePrefix => $permissionPrefix) {
            if (Str::startsWith($name, $routePrefix.'.')) {
                return self::resourcePermission($name, $permissionPrefix);
            }
        }

        if (Str::startsWith($name, 'jersey-customization-options.') || Str::startsWith($name, 'size-option-groups.')) {
            return self::resourcePermission($name, 'customization');
        }

        if (Str::startsWith($name, 'orders.')) {
            return self::resourcePermission($name, 'orders');
        }

        if (Str::startsWith($name, 'returns.')) {
            return self::resourcePermission($name, 'returns');
        }

        if (Str::startsWith($name, 'role-matrix.')) {
            return in_array($name, ['role-matrix.index'], true) ? 'role_matrix.view' : 'role_matrix.manage';
        }

        if ($name === 'modules.show') {
            $module = (string) $request->route('module');
            return match ($module) {
                'customers' => 'customers.view',
                'inventory' => 'inventory.view',
                'reviews' => 'reviews.view',
                'content' => 'content.view',
                'reports' => 'reports.view',
                'shipping' => 'shipping.view',
                'taxes' => 'taxes.view',
                'payments' => 'payment_methods.view',
                'settings' => 'settings.view',
                'discounts' => 'coupons.view',
                'orders' => 'orders.view',
                default => null,
            };
        }

        return null;
    }

    public static function requiresDeletePermission(Request $request): bool
    {
        return $request->isMethod('DELETE');
    }

    private static function resourcePermission(string $routeName, string $permissionPrefix): string
    {
        $manageActions = [
            'create', 'store', 'edit', 'update', 'destroy', 'duplicate', 'toggle', 'bulk', 'import',
            'ordering.update', 'sync-legacy', 'shipments.store', 'shipments.update', 'requests.update',
            'downloads.store', 'downloads.destroy', 'roles.store',
        ];

        foreach ($manageActions as $action) {
            if (Str::endsWith($routeName, '.'.$action) || Str::contains($routeName, '.'.$action.'.')) {
                return $permissionPrefix.'.manage';
            }
        }

        return $permissionPrefix.'.view';
    }

    private static function permission(string $key, string $module, string $action, string $label, ?string $description, ?string $routeName, int $sortOrder): array
    {
        return compact('key', 'module', 'action', 'label', 'description', 'routeName') + [
            'route_name' => $routeName,
            'sort_order' => $sortOrder,
        ];
    }
}
