# Admin Role Matrix Update

Implemented a working role matrix for the NextPlay admin panel.

## Added
- `admin_roles`, `admin_permissions`, and `admin_role_permissions` database tables.
- Default roles: Super Admin, Admin, Catalog Manager, Order Manager, Support Agent, Content Manager.
- Permission matrix grouped by Dashboard, Catalog, Commerce, Storefront, Reports, and System.
- Protected Delete Records permission.
- Admin Users page for creating admin users, assigning roles, changing active status, and resetting passwords.
- Route-level permission middleware for admin routes.
- Permission-aware admin sidebar and dashboard links.

## Routes
- `/admin/role-matrix`
- `/admin/users`

## Deploy
Run:

```bash
php artisan migrate --force
php artisan optimize:clear
```
