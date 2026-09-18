# NextPlay Homepage Admin CMS Realignment Design

Date: 2026-09-18
Status: Approved architecture, pending implementation-plan review
Project: NextPlay Sportswear

## 1. Purpose

Realign the existing Laravel homepage CMS to the new Vue 3 storefront homepage so an administrator can manage the new homepage section by section without code changes.

The implementation must preserve the current single-project architecture:

- Laravel remains the source of truth.
- Vue 3 remains the storefront renderer.
- Storefront content is delivered through `/api/v1`.
- Admin remains Laravel Blade.
- Product/catalog data continues to come from Laravel services and MySQL.
- Product-card images and product data are not duplicated into homepage CMS settings.
- Existing storefront behavior outside the homepage must not change.

This is a realignment of the existing homepage CMS, not a new page-builder platform.

## 2. Current-State Findings

The existing project already has useful CMS foundations:

- `homepage_sections` table and `HomepageSection` model.
- `homepage_slides` table and `HomepageSlide` model.
- `HomepageSectionRegistry` for definitions/defaults/order.
- `HomepageSectionController` and `HomepageSlideController`.
- `HomepageSectionMediaService` and `HomepageSlideMediaService`.
- `HomePageService` and `/api/v1` `HomepageResource`.
- Vue homepage components in `resources/js/storefront/features/home/components/`.

The problems are alignment and duplication:

1. `HomepageSectionRegistry` still describes many sections from the old Blade homepage.
2. The new Vue homepage uses a different set of sections.
3. Several Vue homepage labels, headings, links, and images are still hardcoded.
4. The current generic admin section editor does not support item-level uploaded images cleanly.
5. Hero slides exist as a separate admin menu, while the new homepage should present one coherent Homepage Control Center.
6. Existing `sort_order` does not represent actual Vue rendering order because `HomePage.vue` currently renders sections explicitly.
7. The old admin styling does not match the approved NextPlay storefront visual system.

## 3. Chosen Approach

Use the existing homepage CMS architecture and realign it to the new Vue homepage.

Do not:

- create a new CMS subsystem,
- create a separate Laravel frontend project,
- add microservices,
- move business rules into Vue,
- convert the full admin panel to Vue,
- make product cards independently editable in Homepage Controls.

The new design will retain the existing tables and services where they are appropriate, add only the missing structured configuration, retire obsolete homepage section definitions, and make the Vue homepage consume the resulting configuration.

## 4. Canonical Homepage Sections

The Homepage Control Center will expose exactly these storefront sections, in this order:

1. Hero Banner
2. Audience Tiles
3. Shop By Sport
4. New Arrivals
5. Shop By Category
6. Best Choices For You
7. Season Sale
8. Make It Yours
9. Design Process

The rendering order is fixed to the approved design for this phase. The admin may show/hide each section, but section reordering will not be exposed because the Vue design is layout-specific and the current `sort_order` field would otherwise be misleading.

`sort_order` remains in the database for compatibility and internal ordering, using registry-defined values.

## 5. Section Keys and Defaults

The registry will be rewritten around these keys:

| Key | Admin name | Default sort |
| --- | --- | ---: |
| `hero` | Hero Banner | 10 |
| `audience` | Audience Tiles | 20 |
| `shop_by_sport` | Shop By Sport | 30 |
| `new_arrivals` | New Arrivals | 40 |
| `shop_by_category` | Shop By Category | 50 |
| `best_choices` | Best Choices For You | 60 |
| `season_sale` | Season Sale | 70 |
| `make_it_yours` | Make It Yours | 80 |
| `design_process` | Design Process | 90 |

The registry remains the fallback source when a database row is missing.

## 6. Legacy Section Retirement and Migration

The old registry contains sections that no longer belong to the approved Vue design. They must stop appearing in admin navigation and storefront CMS output.

### Retire without destructive deletion

Add these keys to retired legacy definitions:

- `slider`
- `categories`
- `buyer_paths`
- `process`
- `featured_products`
- `latest_products`
- `best_selling_products`
- `best_selling_gear`
- `why_choose`
- `testimonials`
- `faq`
- existing already-retired keys

The database rows should not be physically deleted in this phase. They remain available for rollback/data recovery but are excluded by `HomepageSectionRegistry::retiredKeys()`.

### Data migration

A migration/service migration step will copy reusable legacy values into the new keys only when the new target row has not already been customized:

- `categories` -> `shop_by_category`
- `process` -> `design_process`
- `latest_products` title/visibility -> `new_arrivals`
- `shop_by_sport` remains the same key and receives new defaults/settings
- hero slide data remains in `homepage_slides`; old `slider` section visibility is migrated to `hero.is_active` if needed

Featured/latest/best-selling product rows are not copied as standalone page sections because the product feeds continue to exist in Laravel services and are consumed by New Arrivals / Best Choices / Make It Yours.

## 7. Database Model Changes

Add one nullable JSON column to `homepage_sections`:

```text
settings JSON NULL
```

Update `HomepageSection`:

- add `settings` to `$fillable`
- cast `settings` to `array`

The existing columns remain useful for common fields:

- `title`
- `description`
- `primary_label`
- `primary_url`
- `secondary_label`
- `secondary_url`
- `image_path`
- `image_url`
- `image_alt`
- `mobile_image_path`
- `mobile_image_url`
- `mobile_image_alt`
- `items`
- `is_active`

The new `settings` JSON stores section-specific structured values that do not belong in generic columns.

## 8. Section Data Contracts

### 8.1 Hero Banner

Hero continues to use `homepage_slides` as the authoritative slide store.

Each slide remains independently editable with:

- desktop image upload/change
- mobile image upload/change
- image alt text
- image focal position
- eyebrow
- title
- description
- primary button label / URL / target / visibility
- secondary button label / URL / target / visibility
- text visibility controls
- content position
- text alignment
- text theme
- overlay color / opacity
- active state
- schedule
- order

The `hero` section row controls only section-level visibility and administrative grouping. The standalone "Homepage Slider" entry will be removed from the Storefront sidebar. Hero slide management will be entered through the Hero Banner card in Homepage Controls.

The existing `HomepageSlideController` and model remain; this is a UI/navigation consolidation, not a data rewrite.

### 8.2 Audience Tiles

`homepage_sections.items` contains exactly three stable items by default:

```json
[
  {"id":"men","title":"MEN","url":"/products?q=men","image_path":null,"image_url":null,"image_alt":"Men sportswear"},
  {"id":"women","title":"WOMEN","url":"/products?q=women","image_path":null,"image_url":null,"image_alt":"Women sportswear"},
  {"id":"kids","title":"KIDS","url":"/products?q=kids","image_path":null,"image_url":null,"image_alt":"Kids sportswear"}
]
```

Admin controls per item:

- title
- destination URL
- image upload/change
- optional image URL
- alt text

The section itself has visibility only; no unnecessary description fields are shown.

### 8.3 Shop By Sport

Common fields:

- section heading
- visibility

`items` stores selected sports and optional presentation overrides:

- stable item ID
- `category_id`
- optional title override
- optional URL override
- optional image upload / image URL
- image alt text

`settings` stores:

```json
{
  "default_sport_id": null,
  "quick_links": [
    {"id":"jersey","label":"JERSEY","url":"/products?q=jersey"},
    {"id":"bottoms","label":"BOTTOMS","url":"/products?q=bottoms"},
    {"id":"uniform-kits","label":"UNIFORM KITS","url":"/products?q=uniform"},
    {"id":"accessories","label":"ACCESSORIES","url":"/products?q=accessories"}
  ]
}
```

If an item has no image override, the storefront uses the selected category/sport banner/image.

### 8.4 New Arrivals

Admin controls:

- section heading
- visibility

Products remain automatic from `ProductCatalogService::latest()`.

No product selection, image upload, price, badge, or product text is duplicated into Homepage Controls.

### 8.5 Shop By Category

Admin controls:

- section heading
- visibility
- category tile list

Each item supports:

- stable item ID
- category/subcategory selection (`category_id`)
- optional title override
- optional destination URL override
- image upload/change
- optional image URL
- image alt text

If an override image is absent, the selected category image/banner is used.

The UI should support the number of tiles required by the design; default configuration uses four.

### 8.6 Best Choices For You

Admin controls:

- section heading
- visibility
- tab labels and tab visibility

`settings`:

```json
{
  "tabs": {
    "featured": {"label":"FEATURED","enabled":true},
    "popular": {"label":"POPULAR","enabled":true},
    "trending": {"label":"TRENDING","enabled":true}
  }
}
```

Product feeds stay Laravel-owned:

- Featured -> `ProductCatalogService::featured()`
- Popular -> `ProductCatalogService::bestSelling()`
- Trending -> latest-products feed for this phase

No product-card images or product content are editable here.

### 8.7 Season Sale

Admin controls:

- title (`SEASON SALE`)
- subtitle/description (`UP TO 20% OFF`)
- CTA label
- CTA destination
- desktop background image upload/change
- mobile background image upload/change
- image alt text
- visibility

The storefront no longer borrows the second hero slide as the Season Sale background. Season Sale has its own CMS image.

### 8.8 Make It Yours

Admin controls:

- section heading
- Explore All label
- Explore All destination
- visibility

Products remain derived from Laravel product data, prioritizing customizable products from existing feeds.

No product-card images or product attributes are duplicated into Homepage Controls.

### 8.9 Design Process

Admin controls:

- section heading
- visibility
- up to five design-process steps in the approved layout

Each step supports:

- stable item ID
- title
- description
- image upload/change
- optional image URL
- image alt text

Unlike the current implementation, every process step can have a real CMS image. The placeholder behavior remains only as a safe fallback when no image has been configured.

## 9. Item-Level Media Architecture

The current media service only manages section-level images and legacy hero-slide images stored in the section row. New-design sections require images on individual items.

Extend the media layer rather than processing uploads in controllers.

Recommended implementation:

```text
HomepageSectionMediaService
  syncSectionMedia(...)
  syncItemMedia(...)
  deleteRemovedItemMedia(...)
```

Every item has a stable string `id` so image ownership survives item reordering.

Supported item media fields:

- `image_file` (request-only)
- `image_path` (stored)
- `image_url` (stored external/public URL override)
- `image_alt`
- `remove_image` (request-only)

Stored paths use:

```text
homepage/sections/{section-key}/items/{item-id}/...
```

Rules:

1. Replacing an uploaded image deletes the old local file.
2. Switching from upload to URL deletes the old local file.
3. Removing an item deletes its owned local image.
4. Reordering an item does not delete its image because ownership uses stable IDs, not indexes.
5. External URLs are never deleted from storage.
6. File deletion occurs only on the `public` disk and only for paths owned by the homepage media service.

## 10. Validation

`HomepageSectionRequest` will remain the central Form Request but become registry-aware per section.

Common validation:

- safe URLs through `SafePublicUrl`
- images: JPG/JPEG/PNG/WebP/AVIF, max 10 MB
- title/description/button length limits
- boolean visibility

Item validation adds:

- `items.*.id`
- `items.*.image_file`
- `items.*.image_path`
- `items.*.image_url`
- `items.*.image_alt`
- `items.*.remove_image`

Section-specific rules enforce:

- Audience has three items.
- Shop By Category has unique category IDs when supplied.
- Shop By Sport has unique sport/category IDs.
- Design Process is capped at five items in this design.
- CTA labels require a destination URL.
- At least one Best Choices tab stays enabled.

The request payload strips request-only upload/remove fields before saving JSON.

## 11. Admin Homepage Control Center

### 11.1 Navigation

The Storefront admin menu will show one main entry:

```text
Homepage Controls
```

The previous per-section submenu list generated from the old registry will be removed.

The separate "Homepage Slider" sidebar item will also be removed. Hero slide routes/controllers still exist and are reached from Hero Banner management.

### 11.2 Index page

`/admin/homepage` becomes a section dashboard.

Each card shows:

- section number/order
- section name
- active/hidden state
- concise editable-content summary
- thumbnail when the section owns an image
- item count where relevant
- Edit Section action

Hero Banner card also shows slide count and a Manage Slides action.

### 11.3 Section-specific editors

Replace the oversized generic edit experience with a small orchestrator plus section-specific Blade partials.

Proposed view structure:

```text
resources/views/admin/homepage-sections/
  index.blade.php
  edit.blade.php
  partials/
    _hero.blade.php
    _audience.blade.php
    _shop-by-sport.blade.php
    _new-arrivals.blade.php
    _shop-by-category.blade.php
    _best-choices.blade.php
    _season-sale.blade.php
    _make-it-yours.blade.php
    _design-process.blade.php
```

Reusable Blade components:

```text
resources/views/components/admin/homepage/
  section-panel.blade.php
  text-field.blade.php
  link-field.blade.php
  media-field.blade.php
  visibility-field.blade.php
  item-card.blade.php
  save-bar.blade.php
```

Keep JavaScript/Alpine helpers modular. Do not grow another all-purpose multi-hundred-line editor if section-specific partials can keep responsibilities clear.

## 12. Homepage Admin Design System

Only Homepage Controls is restyled in this phase. The rest of the admin stays unchanged.

Create a scoped homepage-admin design layer using the approved storefront values:

```text
Expose font
Navy:       #061F44
Orange:     #CF5D38
Orange hover:#B94F2F
White:      #FFFFFF
Soft BG:    #F4F6F8
Muted text: #677386
Border:     #E1E6EB
```

Proposed stylesheet:

```text
resources/css/admin/homepage.css
```

Imported by the existing admin CSS entrypoint, with styles scoped under `.np-home-admin` to avoid changing unrelated admin pages.

Central CSS variables:

```css
.np-home-admin {
  --np-admin-home-navy: #061f44;
  --np-admin-home-orange: #cf5d38;
  --np-admin-home-orange-hover: #b94f2f;
  --np-admin-home-bg: #f4f6f8;
  --np-admin-home-surface: #ffffff;
  --np-admin-home-muted: #677386;
  --np-admin-home-border: #e1e6eb;
  --np-admin-home-font: 'Expose', ui-sans-serif, system-ui, sans-serif;
}
```

Buttons, panels, media controls, inputs, status pills, empty states, and sticky save bars use these scoped tokens.

This creates a reusable foundation for later admin conversion without changing the entire admin now.

## 13. API Contract

The `/api/v1` homepage endpoint remains frontend-neutral JSON.

`HomepageResource` continues to return catalog feeds separately and returns all CMS sections in `sections`.

`HomeSection` gains:

```ts
settings?: Record<string, unknown>;
```

Items include resolved public image URLs in the API. Raw local storage paths should not be required by Vue.

Example section payload:

```json
{
  "key": "season_sale",
  "component": "season_sale",
  "title": "SEASON SALE",
  "description": "UP TO 20% OFF",
  "primary_label": "SHOP SALE",
  "primary_url": "/products",
  "image": "/storage/homepage/sections/season_sale/banner.webp",
  "mobile_image": null,
  "image_alt": "NextPlay season sale",
  "settings": {},
  "items": [],
  "is_active": true
}
```

`HomepageSectionRegistry::mergeForView()` remains responsible for merging defaults and stored values, and for resolving item defaults safely.

## 14. `HomePageService` Simplification

The service continues to own data composition.

It will expose:

- `sections`
- hero slides
- category catalog data
- sports catalog data
- featured products
- latest products
- best-selling products
- navigation/menus

Remove API payloads that only existed for old homepage sections when the Vue homepage no longer consumes them, including buyer-path and FAQ payloads, after regression tests prove no current consumer depends on them.

Product feed methods remain in services; no duplicated business logic enters Vue.

## 15. Vue Homepage Changes

`HomePage.vue` continues to render the approved section order explicitly for this phase.

Every configurable component receives its `HomeSection` configuration.

Target flow:

```text
HomePage.vue
  -> sectionsByKey
  -> section component props
  -> section renders CMS content with safe design-matched fallback
```

Component changes:

- `HomeHero.vue`: use admin-managed slides; hero section controls visibility.
- `AudienceTiles.vue`: remove hardcoded MEN/WOMEN/KIDS definitions; use `audience.items` with defaults.
- `ShopBySport.vue`: use section heading, items, default sport, and quick links from CMS settings.
- `NewArrivals.vue`: use section heading from CMS.
- `ShopByCategory.vue`: use section heading and CMS category tile definitions/image overrides.
- `BestChoices.vue`: use section heading and tab labels/enabled state from CMS settings.
- `SeasonSaleBanner.vue`: use its own section text, CTA, desktop/mobile image; no hero-slide borrowing.
- `MakeItYours.vue`: use section heading and Explore All label/link from CMS.
- `DesignProcess.vue`: use section heading and item images/text from CMS.

All components keep the current approved visual fallbacks so incomplete CMS data does not destroy the page.

If a section is inactive, `HomePage.vue` does not render it.

## 16. Frontend Type Safety

Add typed section helpers rather than spreading untyped records throughout components.

Extend `home.types.ts` with:

- `HomeSection.settings`
- `HomeSectionItem`
- `HomeAudienceItem`
- `HomeSportItem`
- `HomeCategoryItem`
- `HomeDesignProcessItem`
- `HomeBestChoicesSettings`

Use small normalization helpers/composables where necessary so components receive predictable values.

Do not put server data into Pinia solely for this CMS. Homepage query state remains in `useHome()`.

## 17. Cache Invalidation

Any section update, section media update, hero slide create/update/delete/toggle, or migrated setting change must flush homepage section/slide cache through the existing services.

Bump the homepage section cache key version when the new contract is deployed to prevent stale old-shape content from surviving rollout.

## 18. Permissions

Reuse existing RBAC permissions:

- `homepage_sections.view`
- `homepage_sections.manage`
- hero slide permissions already present

Do not introduce new roles unless required by an existing permission gap.

Hero slide actions accessed through Homepage Controls still honor their existing permissions.

## 19. Compatibility and Rollback

This migration must remain incremental.

- Old database rows are retired, not destroyed.
- Existing `homepage_slides` records are preserved.
- Product/catalog services are preserved.
- Existing Blade admin outside Homepage Controls is untouched.
- The legacy Blade storefront is not removed by this work.
- The Vue homepage keeps defaults when a new CMS row is absent.
- New migrations must be reversible where schema changes are involved.

## 20. Testing Strategy

### Backend feature tests

Add/extend tests for:

1. Registry contains exactly the new homepage section keys in approved order.
2. Legacy keys are retired and absent from admin-managed definitions.
3. `settings` casts/persists correctly.
4. Admin can update each section's allowed fields.
5. Section-specific validation works.
6. Audience/category/sport/design-process item image upload works.
7. Replacing an item image deletes the old local file.
8. Removing an item deletes its owned local file.
9. Reordering items does not delete images.
10. Hero slides remain manageable and are reachable from Hero Banner controls.
11. Cache is flushed after changes.
12. `/api/v1` returns new section settings/items/images.
13. Inactive sections are omitted/marked so Vue hides them.
14. Legacy rows do not appear in CMS output.
15. Product feeds remain unchanged.

### Frontend contract tests

Add/extend `.mjs` tests for:

- no hardcoded editable homepage headings where CMS configuration exists
- no hardcoded Audience tile definitions
- no hardcoded Shop By Sport quick links
- no hardcoded Best Choices tab labels
- Season Sale uses its own section config
- Design Process uses CMS step images/text
- product cards remain catalog-driven
- section visibility is respected
- exact approved visual design tokens remain unchanged

### Regression

Run the existing homepage fidelity suite after each component migration.

The implementation must not intentionally change the current approved storefront dimensions, typography, colors, spacing, hover states, product-card layout, header, mega menu, or footer.

## 21. Planned File Areas

Expected backend files:

```text
app/Support/HomepageSectionRegistry.php
app/Models/HomepageSection.php
app/Http/Controllers/Admin/HomepageSectionController.php
app/Http/Requests/Admin/HomepageSectionRequest.php
app/Services/Catalog/HomepageSectionMediaService.php
app/Services/Storefront/HomepageSectionService.php
app/Services/Storefront/HomePageService.php
app/Http/Resources/Api/V1/Storefront/HomepageResource.php
app/Http/Controllers/Admin/HomepageSlideController.php (navigation/integration only if needed)
database/migrations/*_add_settings_to_homepage_sections.php
database/migrations/*_realign_homepage_sections_for_vue_design.php
```

Expected admin files:

```text
resources/views/admin/homepage-sections/index.blade.php
resources/views/admin/homepage-sections/edit.blade.php
resources/views/admin/homepage-sections/partials/*.blade.php
resources/views/components/admin/homepage/*.blade.php
resources/views/components/layouts/admin.blade.php
resources/css/admin/homepage.css
resources/css/app.css
```

Expected Vue files:

```text
resources/js/storefront/features/home/pages/HomePage.vue
resources/js/storefront/features/home/types/home.types.ts
resources/js/storefront/features/home/components/HomeHero.vue
resources/js/storefront/features/home/components/AudienceTiles.vue
resources/js/storefront/features/home/components/ShopBySport.vue
resources/js/storefront/features/home/components/NewArrivals.vue
resources/js/storefront/features/home/components/ShopByCategory.vue
resources/js/storefront/features/home/components/BestChoices.vue
resources/js/storefront/features/home/components/SeasonSaleBanner.vue
resources/js/storefront/features/home/components/MakeItYours.vue
resources/js/storefront/features/home/components/DesignProcess.vue
resources/js/storefront/features/home/components/ProcessStepCard.vue
```

Tests will be added under existing `tests/Feature`, `tests/Unit`, `tests/frontend`, and `tests/ui` locations.

## 22. Out of Scope

This phase does not:

- convert the full admin panel to Vue,
- redesign unrelated admin pages,
- change header/mega-menu/footer global content management,
- create a generic drag-and-drop page builder,
- let homepage admins edit product prices, product images, badges, SKU, inventory, or product customization data,
- change checkout, cart, account, payments, FlowTrack, or catalog business logic,
- remove the old Blade storefront,
- remove legacy homepage database rows permanently.

## 23. Exit Criteria

The work is complete only when:

1. Admin Homepage Controls shows only the nine new-design sections.
2. Hero slides are managed from the Hero Banner workflow, without a duplicate sidebar Homepage Slider entry.
3. All editable homepage text in those sections is controlled by Laravel CMS data rather than hardcoded Vue strings, except intentional fallback defaults.
4. Every non-product image used by those sections can be uploaded/replaced from admin where the design contains an image.
5. Product cards remain catalog-driven.
6. Old homepage section menu items no longer appear.
7. New admin homepage UI uses centralized NextPlay homepage colors/font/styles without changing unrelated admin pages.
8. `/api/v1` exposes the new CMS configuration cleanly.
9. Vue renders the approved design using the admin data without changing established visual fidelity.
10. Media replacement/removal is safe and cleans up owned files.
11. Existing homepage/catalog behavior has regression coverage.
12. Focused backend/API/frontend tests pass and no new unrelated regressions are introduced.
