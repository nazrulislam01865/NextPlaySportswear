# Fully Dynamic Vue Header Menu

## Scope

This implementation applies to the new Vue 3 storefront header. The menu and mega-menu editor is now separate from Header Settings.

## Source of truth

The new Vue storefront navigation is stored independently under:

`storefront_settings.navigation`

Laravel defaults preserve the approved starting navigation:

- SHOP
- SPORTS
- MEN
- WOMEN
- KIDS
- CUSTOM TEAMWEAR
- EXPLORE

The default SHOP mega menu preserves the approved Top Choices rail, New Arrivals/Men/Women/Kids columns, and promo card.

If an upgraded installation already has customized navigation stored inside `storefront_settings.header.navigation`, the extraction migration copies that exact navigation into the new `navigation` row only when a navigation row does not already exist.

## Admin controls

Use:

`Admin > Store > Navigation Menu`

The page manages:

- Root menu items: add, remove, show/hide, reorder, label, URL, target.
- Mega menu enable/disable on any root item.
- Top Choices: show/hide, eyebrow, links, ordering.
- Mega menu columns: show/hide, title, links, ordering, up to four columns.
- Promo card: show/hide, image upload/replacement/removal, alt text, CTA label and URL.

Header Settings now manages only branding/logo, announcements, utility links and header actions.

Promo uploads remain in the existing managed public-disk location:

`storage/app/public/storefront/settings/navigation/mega-menu/`

## Frontend flow

`Navigation Menu -> StorefrontSettingsService -> /api/v1/storefront/bootstrap -> Pinia -> StorefrontHeader -> DesktopNavigation / MobileNavigation / MegaMenu`

Desktop and mobile use the same `navigation.items` payload.

## Legacy navigation module

The old Navigation Menus admin CRUD, routes, sidebar entry and active RBAC permissions are retired.

The legacy `menus` and `menu_items` database tables, `Menu`/`MenuItem` models and `NavigationService` remain temporarily because old Blade/category code still depends on them. They are not used by the new Vue header/bootstrap path.

## Deployment

Run:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
npm install
npm run build
```

`php artisan storage:link` only needs to be created once per deployment filesystem.
