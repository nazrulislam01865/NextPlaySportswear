# About Page Admin Management Design

Date: 2026-09-29
Project: NextPlay Sportswear
Scope: Make the existing `/about-us` storefront page fully admin-manageable while preserving its current approved layout, fixed section order, centralized theme, shared components, and storefront behavior.

## 1. Goal and constraints

The About NextPlay page already exists and matches the approved prototype. This change must move all page-specific content out of hardcoded Blade markup and into admin-managed persisted data without redesigning the page.

The page section order is permanently fixed:

1. Hero
2. Introduction
3. What We Do
4. How We Work
5. Gallery
6. Made for Your Team CTA
7. Need Help

The admin can edit all content inside those sections, including text, image alt text, button labels and destinations, card/step content, uploaded icons, and uploaded images. The admin cannot reorder, add, or delete the seven primary sections. The storefront layout, responsive rules, section spacing, card counts, process arrows, typography, colors, and button styles remain code-controlled.

No unrelated admin or storefront features will be refactored.

## 2. Existing project patterns to preserve

The implementation will follow the existing project conventions:

- Admin routes live under the authenticated `admin` route group in `routes/web.php`.
- Admin authorization is enforced by the existing RBAC middleware and `AdminRbac` permission registry.
- Store navigation is rendered in `resources/views/components/layouts/admin.blade.php`.
- Uploaded media uses Laravel's `public` disk and is exposed through the existing `PublicMedia` support layer and `/media/...` application route fallback.
- Existing storefront About components remain responsible for presentation.
- Centralized storefront fonts, theme variables, button classes, header, footer, and responsive layout remain unchanged.

## 3. Storage model

Create one dedicated `about_page_settings` table and one `AboutPageSetting` Eloquent model. This isolates About content from `homepage_sections`, whose semantics include homepage ordering and behaviors that do not apply here.

The table will contain one logical settings record with these fields:

- `id`
- `hero` JSON
- `introduction` JSON
- `what_we_do` JSON
- `how_we_work` JSON
- `gallery` JSON
- `cta` JSON
- `help` JSON
- `seo` JSON
- `created_by` nullable foreign key/reference pattern consistent with the project
- `updated_by` nullable foreign key/reference pattern consistent with the project
- timestamps

The model casts all section fields to arrays.

Only one settings row is used. A service method retrieves the first row or creates/normalizes it from defaults. The storefront must never depend on a row ID in URLs or templates.

### 3.1 Hero JSON

```text
{
  eyebrow,
  title,
  description
}
```

### 3.2 Introduction JSON

```text
{
  title,
  description,
  image_path,
  image_alt
}
```

### 3.3 What We Do JSON

Exactly three fixed card slots:

```text
{
  title,
  cards: [
    {
      id,
      title,
      description,
      icon_path,
      icon_alt,
      fallback_icon
    },
    ... exactly 3
  ]
}
```

`id` is stable and not admin-editable. The three default ids will map to the current three cards. `fallback_icon` stores the current built-in icon key so the approved UI still renders if a custom icon is absent or removed.

### 3.4 How We Work JSON

Exactly four fixed step slots:

```text
{
  title,
  steps: [
    {
      id,
      number,
      title,
      description,
      icon_path,
      icon_alt,
      fallback_icon
    },
    ... exactly 4
  ]
}
```

The step positions remain fixed. The visible step number is editable because it is page content, but admin cannot add, remove, or reorder steps. Process arrows remain structural frontend UI and are not stored.

### 3.5 Gallery JSON

Exactly four fixed image slots corresponding to the current desktop composition:

```text
{
  items: [
    { id, image_path, image_alt },
    ... exactly 4
  ]
}
```

Stable ids preserve slot identity. The CSS-specific gallery slot classes remain mapped by slot index/id in the storefront view and are not editable.

### 3.6 CTA JSON

```text
{
  eyebrow,
  title,
  primary_label,
  primary_url,
  secondary_label,
  secondary_url
}
```

### 3.7 Help JSON

```text
{
  title,
  description,
  icon_path,
  icon_alt,
  fallback_icon,
  button_label,
  button_url
}
```

### 3.8 SEO JSON

```text
{
  title,
  description
}
```

Canonical URL remains `route('about')`, schema type remains `AboutPage`, and robots behavior continues to come from the shared storefront SEO layer.

## 4. Default data and migration safety

The initial migration/default provider must reproduce the current live About page exactly. Deployment must not blank or visually change the page before an administrator edits it.

Defaults are sourced from the current implementation:

- Hero eyebrow, title, and description currently in `about.blade.php`.
- Introduction copy and current `team-intro.webp` asset.
- Three current What We Do cards and current built-in icon keys.
- Four current How We Work steps and current built-in icon keys.
- Four existing gallery assets and current alt text.
- Existing CTA labels and URLs.
- Existing Need Help content and headset fallback icon.
- Existing About SEO title and description.

Existing prototype assets under `public/images/storefront/about/` remain valid defaults. They are not copied to storage during migration. The settings data may represent these defaults as a default/fallback media value in the service layer rather than a storage-owned path, preventing deletion logic from ever deleting shipped application assets.

## 5. AboutPageService

Add a focused storefront/admin service, `App\Services\Storefront\AboutPageService`, responsible for:

- returning normalized settings for the storefront and admin form;
- merging persisted values over the immutable default schema;
- enforcing exactly three What We Do slots, four How We Work slots, and four gallery slots;
- resolving uploaded storage paths through `PublicMedia`;
- exposing current built-in asset URLs when no uploaded replacement exists;
- keeping stable card/step/gallery ids;
- preventing malformed legacy JSON from breaking the view.

The service is the only place that knows the default data shape. Blade templates and controllers should not duplicate default strings.

## 6. Admin interface

Add a dedicated admin screen:

- GET `/admin/about-page` -> `admin.about-page.edit`
- PUT `/admin/about-page` -> `admin.about-page.update`

Controller: `App\Http\Controllers\Admin\AboutPageController`
Request: `App\Http\Requests\Admin\AboutPageRequest`
View: `resources/views/admin/about-page/edit.blade.php`

The sidebar adds `About Page` under the existing Store group. It appears only when the current admin has `about_page.view`.

The screen will show seven fixed section cards in storefront order. It must not include drag handles, ordering inputs, "add section", or delete-section controls.

### 6.1 Admin fields

Hero:
- eyebrow
- heading
- description

Introduction:
- heading
- description
- current image preview
- image upload
- remove custom image action
- alt text

What We Do:
- section heading
- three fixed card editors
- per card: title, description, current icon preview, icon upload, remove custom icon, icon alt text

How We Work:
- section heading
- four fixed step editors
- per step: visible number, title, description, current icon preview, icon upload, remove custom icon, icon alt text

Gallery:
- four fixed image-slot editors
- per slot: current image preview, image upload, remove custom image, alt text

CTA:
- eyebrow
- heading
- primary button label
- primary button URL
- secondary button label
- secondary button URL

Need Help:
- current icon preview
- icon upload
- remove custom icon
- icon alt text
- heading
- description
- button label
- button URL

SEO:
- page title
- meta description

No section visibility toggles are added because the user requirement is that the fixed prototype sections remain present and the entire content inside them is editable. This avoids an admin accidentally removing a required prototype section.

## 7. Upload design

Create `App\Services\Catalog\AboutPageMediaService` to own About-specific uploaded files and cleanup.

Storage roots:

- `about-page/introduction`
- `about-page/what-we-do/icons`
- `about-page/how-we-work/icons`
- `about-page/gallery`
- `about-page/help/icons`

Uploaded files use the existing `public` disk. Public URLs are generated through `PublicMedia`, not by hardcoding `/storage`.

### 7.1 Supported image/icon uploads

Allow image formats consistent with current application image handling and browser rendering:

- JPEG/JPG
- PNG
- WebP
- AVIF when Laravel/PHP validation recognizes it in the deployed environment

SVG upload is intentionally excluded for this page unless the existing project already sanitizes arbitrary SVG uploads. The current built-in icons continue to provide vector-quality fallback icons without introducing an XSS-capable SVG upload surface.

The request validates files as real images. Content images use a 10 MB maximum to match the existing homepage image-management pattern; uploaded icons use a 2 MB maximum to match existing category-icon handling. Icon dimensions are not forced to be square. Frontend icon wrappers use contain behavior so transparent icons are not cropped.

### 7.2 Replace and remove behavior

For each upload slot:

- If no new file is submitted, the existing custom file remains unchanged.
- If a new file is uploaded, it is stored first; after successful persistence, the prior About-owned uploaded file is deleted.
- If "remove custom image/icon" is selected, the custom file reference is removed and the storefront falls back to the original default asset/built-in icon where one exists.
- Shipped assets in `public/images/storefront/about/` are never deleted.
- Media Library records or files owned by other modules are never deleted by About cleanup.

All database and media mutations are coordinated so a validation or save failure cannot silently erase the existing production asset.

## 8. URL validation

CTA and Help button destination fields allow:

- relative internal paths beginning with `/`, or
- absolute `http://` or `https://` URLs.

Reject script/data schemes and malformed values. Empty button URLs are rejected when their corresponding button label is present, and button labels are required for the fixed prototype buttons.

The default links preserve the current destinations:

- Explore Products -> products index
- Request a Bulk Quote -> quote request
- Contact Us -> contact page

The service/view may resolve named-route defaults to URLs at render time while persisted custom values are stored as strings.

## 9. RBAC

Add two permissions to `AdminRbac::permissions()`:

- `about_page.view`
- `about_page.manage`

Both belong to the Storefront module and use the new admin routes.

Default permission assignment follows the project's current storefront content policy:

- Super Admin: view + manage
- Admin: view + manage
- Content Manager: view + manage
- other roles: unchanged unless the existing default role mapping explicitly grants equivalent storefront content permissions

The admin screen is visible with `about_page.view`. The update route requires `about_page.manage`. Existing route-to-permission resolution must recognize the `admin.about-page.*` route family.

## 10. Storefront integration

Update `ContentPageController::about()` to inject `AboutPageService`, retrieve normalized settings, and pass them to `storefront.content.about`.

The About Blade view continues using the same approved DOM/CSS structure and classes. Only data sources change.

Update existing About components as follows:

### service-card
Accept optional custom icon URL and icon alt text in addition to the fallback icon key. If a custom icon exists, render an `<img>` in the same icon footprint; otherwise render the existing `x-storefront.about.icon` fallback.

### process-step
Same icon behavior as service-card. Number, title, and description come from settings. `last` remains a structural argument determined by the fixed fourth slot.

### icon
Keep all current built-in icons. It remains responsible only for code-owned fallback/structural icons such as process chevrons.

Introduction and gallery images resolve via `AboutPageService`/`PublicMedia`, with current prototype files as defaults.

No About-specific content remains hardcoded in the Blade template except structural ARIA ids/classes and non-content UI such as chevrons.

## 11. Error handling and resilience

- Missing settings row: render full approved defaults.
- Missing JSON key: merge the missing key from defaults.
- Wrong card/step/gallery count: normalize back to the fixed slot count while preserving valid matching stable ids.
- Missing uploaded file on disk: use default asset/icon fallback where available instead of producing a broken image.
- Validation failure: redirect back with errors and old text input; persisted media references remain unchanged.
- Upload failure: abort update with validation/storage error and keep previous persisted state.
- Database failure after uploads: remove newly staged About-owned uploads and keep previous stored media references.
- Old custom upload replacement: delete old file only after the new settings have been successfully saved.

## 12. Validation limits

Use explicit limits to protect the admin and layout:

- eyebrow: required string, max 120
- headings/titles: required string, max 255
- descriptions/card/step copy: required string, max 1000
- button labels: required string, max 100
- alt text: required when a custom image/icon is present, max 255
- step number: required string, max 10
- SEO title: required string, max 255
- SEO description: required string, max 500
- uploaded content image: `image`, JPG/JPEG/PNG/WebP/AVIF, max 10 MB
- uploaded icon: `image`, JPG/JPEG/PNG/WebP/AVIF, max 2 MB

The request rejects unexpected array expansion. Exactly three What We Do cards, four process steps, and four gallery slots must be submitted.

## 13. Security

- Use normal Laravel CSRF protection.
- Use the existing authenticated admin middleware stack.
- Enforce RBAC server-side; hiding sidebar links is not authorization.
- Escape all admin-managed text using normal Blade escaped output.
- Do not allow arbitrary HTML in About content fields.
- Do not accept unsanitized SVG uploads.
- Validate button URLs against allowed schemes.
- Generate filesystem names through Laravel storage rather than user-supplied names.
- Never let request input choose arbitrary storage paths for deletion.

## 14. Testing strategy

Implementation follows TDD for behavior changes.

### 14.1 Feature tests

Add tests proving:

1. `/about-us` renders the stored admin-managed hero/intro/service/process/gallery/CTA/help/SEO content.
2. `/about-us` renders approved defaults when the settings row does not exist.
3. A view-only admin can open the editor but cannot update it.
4. A manage-enabled admin can update every text field.
5. Intro image upload stores and renders through the public media URL.
6. All three What We Do icon uploads persist and render.
7. All four process icon uploads persist and render.
8. All four gallery image uploads persist and render.
9. Help icon upload persists and renders.
10. Replacing an About-owned custom file deletes the prior About-owned file after save.
11. Removing a custom image/icon restores the default fallback and deletes only the About-owned upload.
12. Invalid file types are rejected.
13. Invalid button URL schemes are rejected.
14. Submitted extra/missing cards, steps, or gallery slots are rejected/normalized according to the request/service contract.
15. Existing default application assets are never deleted.
16. A validation failure leaves current persisted media unchanged.

### 14.2 Unit/service tests

Test `AboutPageService` normalization:

- missing keys merge defaults;
- stable ids are preserved;
- malformed arrays cannot alter fixed slot counts;
- uploaded media URLs resolve via `PublicMedia`;
- missing custom files fall back safely.

### 14.3 Regression verification

Run:

- targeted About admin/storefront tests;
- the full Laravel test suite;
- frontend production build (`npm run build`) because Blade/CSS/component changes must not break Vite compilation;
- PHP syntax checks on newly created/changed PHP files where appropriate.

## 15. Expected file changes

New files:

- `app/Models/AboutPageSetting.php`
- `app/Services/Storefront/AboutPageService.php`
- `app/Services/Catalog/AboutPageMediaService.php`
- `app/Http/Controllers/Admin/AboutPageController.php`
- `app/Http/Requests/Admin/AboutPageRequest.php`
- `resources/views/admin/about-page/edit.blade.php`
- `database/migrations/2026_09_29_000100_create_about_page_settings_table.php`
- focused About page/admin tests

Updated files:

- `routes/web.php`
- `app/Support/AdminRbac.php`
- `app/Http/Controllers/Storefront/ContentPageController.php`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/storefront/content/about.blade.php`
- `resources/views/components/storefront/about/service-card.blade.php`
- `resources/views/components/storefront/about/process-step.blade.php`
- About-related tests already present in the repository, if their expected hardcoded text contract changes

CSS changes should be minimal and limited to custom uploaded icon/image fitting if the current About styles do not already provide the required behavior. The approved page width and visual design must not be changed.

## 16. Non-goals

This change does not:

- make primary About sections reorderable;
- allow admins to add/delete primary sections;
- change the About page design;
- change global fonts, theme colors, or button definitions;
- change homepage management;
- change Promotions, products, checkout, cart, or account logic;
- create a generic page builder;
- add arbitrary rich HTML/script editing;
- alter the shared header/footer except adding the admin sidebar entry.

## 17. Acceptance criteria

The implementation is accepted when:

- every user-visible About-specific text value can be edited from admin;
- every About-specific content image can be uploaded/replaced/removed from admin;
- every editable card/step/help icon can be uploaded/replaced/removed from admin with a safe built-in fallback;
- section order and the approved visual layout remain unchanged;
- defaults reproduce the current About page on first deployment;
- upload replacement/removal cannot delete unrelated media;
- admin permissions are enforced through existing RBAC;
- storefront remains usable even with missing/malformed About settings;
- targeted tests, full test suite, and production frontend build are verified before delivery.
