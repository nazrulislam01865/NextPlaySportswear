# Jersey Roster Admin Visibility Update

Updated the Add/Edit Product form so the **Jersey roster fields** setup is always visible in the admin product form.

## What changed

- The roster field configuration is no longer hidden behind the enable checkbox.
- The field setup is no longer collapsed when `Show jersey roster step` is off.
- Admin can now configure customer heading, optional setting, and roster fields at any time.
- The checkbox still controls whether the Jersey Roster step appears on the storefront.
- The product option tile button now says `Enable roster step` / `Disable roster step` instead of implying the admin fields will be hidden.

## Changed files

- `resources/views/admin/products/_form.blade.php`
- `resources/css/admin.css`
- `public/build/assets/admin-8DPiPWIH.css`
