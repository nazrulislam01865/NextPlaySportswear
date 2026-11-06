# NextPlay Catalog Admin — Centralized Typography, Buttons and Form Controls

**Scope (first rollout, 04 Nov 2026)**

- Dashboard
- Products (index, create, edit, show, shared product-form partials)
- Product Detail Controls (edit and nested shared field components)
- Media (Gallery, Upload Media File and their interactive editors)
- Categories (index, create, edit, category product assignments, ordering, import)
- Catalog Attributes (index, create, edit and field component)
- Navigation Menus (index, create, edit and menu-item editor)
- Personalization Categories and the associated Personalization Templates index, create/edit wizard, field editor and preview

## Implementation

The single source of styling is `public/css/nextplay-admin-catalog-design-system-20261104.css`. `resources/views/components/layouts/admin.blade.php` has one route gate: `np-catalog-system` is added to the body and the versioned stylesheet is loaded only on matching Catalog routes. The design is scoped to `#admin-main-content`; the common sidebar/top bar, non-Catalog admin pages, storefront, business logic, permission checks, controllers, and forms are unchanged.

The system refers back to existing `--np-*` theme variables for Expose (with Segoe UI fallback), heading navy, and CTA orange. It intentionally **does not** register another `@font-face` or another independent theme color palette. Form typography follows the supplied rules: labels 14px/600, control text 16px, helper/error text 13px; text/select inputs 48px high, 1px border `#bac6d5`, radius 8px, white fill, no duplicate shadows; textareas at least 120px and vertically resizable. Primary/secondary button styles share 48px minimum height, 16px/600 font, 8px radius and one 1px border; primary actions retain the existing theme orange, with secondary actions outlined in navy. Focus-visible is 3px blue, disabled fields remain visibly disabled, and invalid fields are red only on error.

Compatibility rules preserve input padding for icon-bearing product filters, the **single outer border** on media search inputs, search icon placement, native checkbox/radio/file inputs, compact table actions, destructive menu actions, image-grid operations, upload drop zones and Alpine reactive bindings. No input names, types, routes, form actions, Alpine expressions, or validation logic were changed.

## Expose font prerequisite

The uploaded archive contains the existing `@font-face` URLs in `resources/css/storefront-theme.css`, but does **not** contain the actual WOFF2 files expected at `/fonts/expose/Expose-Regular.woff2`, `/fonts/expose/Expose-Medium.woff2`, `/fonts/expose/Expose-Bold.woff2` and `/fonts/expose/Expose-Black.woff2`. The stylesheet uses the existing font configuration. Expose renders only where these font assets already exist on your local or production deployment; otherwise it gracefully falls back to Segoe UI. No font files are redistributed in this update.

## How to install

Option A: Extract the changed-files archive over the existing project (preserve folder structure). Option B: use the full-project archive (make backups of `.env`, storage, uploaded media and database). The new CSS is a public versioned file, not a Vite dependency. Run `php artisan optimize:clear`. Hard-refresh the browser after deployment.

## Verification

- 9 exact route-pattern families are included; `admin.categories.*` also covers ordering and product-assignment routes, `admin.media-library.*` covers Gallery/Upload, `admin.personalization-templates.*` covers linked setup pages.
- New CSS passes `tinycss2` top-level and declaration parsing.
- Shared Blade layout preserves `x-data`, sidebar elements, header, permission checks and flash notification infrastructure; no PHP controller or JS implementation files are modified.
- Browser smoke rendering with Chromium was attempted but this execution environment blocked both `file://` and loopback HTTP navigation. Thus **visual, keyboard, and authenticated Laravel integration checks still require the user's working installation**.
- `vendor/` and `node_modules/` were not included in the supplied project, so no `php artisan test` or `npm run build` was possible here; the new stylesheet does not require a build.

## Local manual QA

Open every listed admin screen and verify font rendering in Developer Tools (`Computed > font-family`), 48px controls, primary/secondary/disabled button states, long labels/errors, select focus, and edit/save actions. In particular: test Media Gallery search and metadata panel; add/rename a media file; add/edit an attribute; create/edit a navigation menu; create/edit a product with options and quantity tiers; filter categories and assign products; edit and preview a personalization template. At widths 390px, 768px and desktop, verify that the product filter row, table scroll, action menus, and media upload remain usable. These are UI validations only; do not write to production data during testing.
