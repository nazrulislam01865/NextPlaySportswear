# NextPlay — Header & Top Bar Settings

## Administration

Go to **Admin → Storefront Settings → Header & Top Bar Settings**.

- GET `/admin/header-topbar-settings` — editor
- PUT `/admin/header-topbar-settings` — save changes (admin permission required)
- Settings include the announcement ticker, Track Order, WhatsApp contact details, Follow us label, individual social networks, optional colors and link spacing, and visibility switches.
- Track Order keeps the existing `orders.track` Laravel route.
- The existing storefront header markup and theme styles are preserved when optional overrides are empty.
- WhatsApp uses a custom link when entered, otherwise builds a `https://wa.me/<digits>` link from the number.
- When the last social network is disabled or has no URL, the Follow us label and social divider are hidden.

## Storage and compatibility

Settings are stored in the existing `storefront_page_settings` database table as the `header-topbar` JSON content record. No new table or extra provider integration is necessary. Before the first admin save, the current `.env`/`config/storefront.php` WhatsApp and social URLs remain the defaults. The cached settings are forgotten after each successful save. Existing Contact Us settings are intentionally unaffected.

New role-matrix permissions `header_topbar.view` and `header_topbar.manage` are registered through the additive migration. Admin and Content Manager roles gain the defaults without resetting existing customized permission grants; Super Admin has both.

## Install into an existing NextPlay project

Copy/merge the updated project files, then from the **NextPlay root** run:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan test --filter=HeaderTopbarSettingsTest
```

For local development you may omit `--force`; do not migrate a production database without a backup. No new CSS or JS build is needed for this feature.
