# Admin cloud CSS permanent fix

## Root cause

The cloud was already receiving the newer Vite manifest and build file names, but the admin CSS still contained older, more specific rules such as `body.admin-clean-ui main .font-black { font-weight: 750 !important; }` and `header h1 { font-weight: 780 !important; }`.

The earlier global typography override existed, but it was less specific than those admin-specific rules. Because both used `!important`, the more specific old admin rules continued to win on cloud pages after rebuild/cache.

## Fix

A final source-level override block was added at the very end of `resources/css/admin.css` with marker:

`NEXTPLAY_ADMIN_CLOUD_PERMANENT_DENSITY_FIX`

The same block was also appended to the currently referenced compiled admin CSS file:

`public/build/assets/admin-4aTCQkgT.css`

This makes the fix immediate on the current build and permanent for future local rebuilds because the source CSS is fixed.

## Deployment check

After uploading/replacing files, run:

```bash
php artisan optimize:clear
php artisan optimize
systemctl reload nginx
```

Verify:

```bash
grep -RIn "NEXTPLAY_ADMIN_CLOUD_PERMANENT_DENSITY_FIX" resources/css/admin.css public/build/assets | head
cat public/build/manifest.json | grep -E "admin|storefront"
```
