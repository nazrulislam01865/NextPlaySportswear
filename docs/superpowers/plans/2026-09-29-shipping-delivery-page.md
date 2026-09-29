# Shipping & Delivery Page — Delivery Tab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current generic `/shipping-delivery` body with the supplied Order Information → Delivery prototype and make every Delivery-tab content field plus its seven content icons manageable from a dedicated admin editor without changing the page's fixed structure.

**Architecture:** Follow the existing admin-managed About page pattern: one settings record with JSON sections, a storefront service that normalizes persisted content against code-owned defaults, a dedicated media mutation service, strict FormRequest validation, RBAC-protected admin edit/update routes, modular Blade components, and page-scoped CSS. The public `shipping` route stays unchanged; only its controller payload and view body change.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Eloquent JSON casts, Laravel validation/FormRequest, public filesystem disk, existing `AdminRbac`, `PublicUrl`, centralized storefront CSS variables/buttons, PHPUnit/Laravel test suite.

**Spec:** `docs/superpowers/specs/2026-09-29-shipping-delivery-page-design.md`

## Global Constraints

- Preserve `GET /shipping-delivery` and route name `shipping`.
- Preserve the current shared storefront header, navigation, footer, Expose typography, centralized theme variables, buttons, and responsive shell.
- Use the same page-width system as the corrected About page: `--np-commerce-layout-max` and `--np-visible-page-inline-space`; do not create a private narrow max-width.
- Keep five tabs in fixed code order: `before-you-order`, `artwork-customisation`, `after-you-order`, `delivery`, `help`; only `delivery` is active/functional in this phase.
- Keep exactly 2 info cards, 3 numbered delivery steps, 4 FAQs, and 4 address checklist items; admin cannot add/remove/reorder them.
- Timeline circles remain numbered circles; do not add uploaded step icons.
- Provide exactly 7 uploadable content-icon slots: 2 info cards, 1 notice, 4 address checklist items.
- Accepted icon uploads: JPG/JPEG, PNG, WebP, AVIF; max 2 MB each; uploaded icons render with `object-fit: contain`.
- Store feature-owned uploads only under `shipping-delivery-page/...`; never delete unrelated public-storage paths.
- Safe CTA destinations must use `App\Support\PublicUrl`; do not introduce custom URL regex validation.
- New permissions are `shipping_delivery_page.view` and `shipping_delivery_page.manage`; do not repurpose `shipping.view` / `shipping.manage` because those remain Shipping Methods permissions.
- Do not change checkout shipping calculations, Shipping Methods master data, remote-area surcharges, tracking logic, payment behavior, or unrelated pages.
- All implementation tasks use TDD: write the failing test, run it and confirm the intended failure, implement minimally, then rerun.

## Review Focus

1. **Rolling deployment with missing `shipping_delivery_page_settings` table:** `/shipping-delivery` must still render code defaults instead of throwing; pinned by Task 1 service test.
2. **Malformed/duplicate/reordered JSON slots or non-string leaf values:** storefront must reconstruct fixed slot counts/order and scalar defaults; pinned by Task 1 normalization tests.
3. **Corrupt, traversal, foreign-namespace, or missing icon paths:** service must never resolve unsafe paths and must use built-in icon fallbacks; pinned by Task 1 media-resolution tests and Task 3 ownership tests.
4. **Unsafe persisted CTA URLs (`javascript:`, `data:`, protocol-relative, malformed relative paths):** storefront must replace them with safe code defaults; pinned by Task 1 service test and Task 2 validation test.
5. **Upload/DB failure after staging:** old content/media must remain active and staged new files must be removed; pinned by Task 3 media tests and Task 4 admin controller failure test.

---

## File Structure

**Create**
- `app/Models/ShippingDeliveryPageSetting.php` — JSON-cast single-record settings model.
- `app/Services/Storefront/ShippingDeliveryPageService.php` — defaults, normalization, URL hardening, media resolution.
- `app/Http/Requests/Admin/ShippingDeliveryPageRequest.php` — fixed-shape validation and normalized content payload.
- `app/Services/Catalog/ShippingDeliveryPageMediaService.php` — seven icon upload/remove/rollback/cleanup mutations.
- `app/Http/Controllers/Admin/ShippingDeliveryPageController.php` — editor + transactional update flow.
- `database/migrations/2026_09_29_000200_create_shipping_delivery_page_settings_table.php` — table + RBAC default sync.
- `resources/views/admin/shipping-delivery-page/edit.blade.php` — complete fixed-structure editor.
- `resources/views/components/storefront/shipping-delivery/tab-bar.blade.php`
- `resources/views/components/storefront/shipping-delivery/info-card.blade.php`
- `resources/views/components/storefront/shipping-delivery/timeline-step.blade.php`
- `resources/views/components/storefront/shipping-delivery/notice.blade.php`
- `resources/views/components/storefront/shipping-delivery/faq-row.blade.php`
- `resources/views/components/storefront/shipping-delivery/checklist-row.blade.php`
- `resources/views/components/storefront/shipping-delivery/icon.blade.php` — built-in SVG/fallback icon renderer.
- `tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php`
- `tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php`
- `tests/Feature/Admin/ShippingDeliveryPageAccessTest.php`
- `tests/Feature/Admin/ShippingDeliveryPageValidationTest.php`
- `tests/Feature/Admin/ShippingDeliveryPageManagementTest.php`
- `tests/Feature/ShippingDeliveryPageManagedContentTest.php`
- `tests/Support/shipping_delivery_page_static_check.php`
- `tests/Support/shipping_delivery_admin_static_check.php`
- `tests/Support/shipping_delivery_layout_static_check.php`

**Modify**
- `app/Http/Controllers/Storefront/ContentPageController.php:106-109` — inject service, build SEO, pass managed settings.
- `app/Support/AdminRbac.php:112-127,160-172,347-370,425-440` — permissions/default roles/first route/route mapping.
- `routes/web.php:110-116` — add dedicated admin edit/update routes; public route remains untouched at line 64.
- `resources/views/components/layouts/admin.blade.php:314-325` — show Store sidebar link when allowed.
- `resources/views/storefront/content/shipping.blade.php:1-94` — replace generic body with managed prototype markup.
- `resources/css/storefront.css` — append `.np-shipping-delivery-page` scoped prototype styles using existing tokens/layout variables.

---

### Task 1: Persistence and Storefront Settings Service

**Files:**
- Create: `app/Models/ShippingDeliveryPageSetting.php`
- Create: `app/Services/Storefront/ShippingDeliveryPageService.php`
- Create: `database/migrations/2026_09_29_000200_create_shipping_delivery_page_settings_table.php`
- Create/Test: `tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php`

**Interfaces:**
- Produces: `ShippingDeliveryPageService::defaults(): array`
- Produces: `ShippingDeliveryPageService::settings(): array`
- Produces settings keys: `hero`, `tabs`, `delivery_intro`, `info_cards`, `delivery_steps`, `notice`, `faqs`, `address_checklist`, `cta`, `seo`.
- Later tasks rely on resolved icon fields `icon_url`, `has_custom_icon`, and code-owned `fallback_icon` for the seven media-capable slots.

- [ ] **Step 1: Write the failing service tests for exact prototype defaults and missing-row behavior**

Add tests asserting at minimum:

```php
$this->assertSame('ORDER INFORMATION', $page['hero']['title']);
$this->assertSame('DELIVERY', $page['delivery_intro']['title']);
$this->assertSame(['before-you-order','artwork-customisation','after-you-order','delivery','help'], array_column($page['tabs'], 'id'));
$this->assertCount(2, $page['info_cards']);
$this->assertCount(3, $page['delivery_steps']);
$this->assertCount(4, $page['faqs']['items']);
$this->assertCount(4, $page['address_checklist']['items']);
$this->assertSame('/track-order', $page['cta']['primary_url']);
$this->assertSame('/terms-conditions', $page['cta']['policy_url']);
```

- [ ] **Step 2: Run the focused service test and verify RED**

Run: `php artisan test tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php`
Expected: FAIL because model/service/table do not exist.

- [ ] **Step 3: Add tests for rolling deployment with the settings table absent**

Drop/rename the table in the test context and assert `settings()` returns the default title/tab structure without throwing.

- [ ] **Step 4: Add tests for fixed-slot normalization and malformed leaves**

Assert persisted unknown/duplicate/reordered tabs/cards/steps/FAQs/checklist items cannot change code order/count, and arrays/objects stored in text fields fall back to default strings.

- [ ] **Step 5: Add tests for persisted URL hardening**

Persist unsafe `cta.primary_url` / `cta.policy_url` values such as `javascript:alert(1)`, `data:text/html,bad`, and `//evil.example`; assert `settings()` returns `/track-order` and `/terms-conditions`.

- [ ] **Step 6: Add tests for missing/malformed/foreign icon paths**

Use `Storage::fake('public')`; persist missing, traversal (`shipping-delivery-page/../private.png`), and foreign (`about-page/help/icons/x.png`) paths. Assert custom URLs are null and the correct code-owned fallback icon identifiers remain.

- [ ] **Step 7: Implement the migration/model/service minimally**

`ShippingDeliveryPageSetting` fillable/casts must cover the 10 JSON sections plus `created_by`, `updated_by`. Migration creates nullable JSON columns and calls `AdminRbac::syncDefaults(false)` after schema creation. `settings()` must guard a missing table, normalize known shapes by stable ID, restore code-owned IDs/fallbacks, use `PublicUrl::isAllowed()` for persisted destinations, validate feature-owned media paths before `Storage` access, and resolve valid public icons.

- [ ] **Step 8: Run Task 1 tests and verify GREEN**

Run: `php artisan test tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php`
Expected: all ShippingDeliveryPageService tests PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Models/ShippingDeliveryPageSetting.php app/Services/Storefront/ShippingDeliveryPageService.php database/migrations/2026_09_29_000200_create_shipping_delivery_page_settings_table.php tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php
git commit -m "feat: add shipping delivery page settings"
```

---

### Task 2: Admin Request Validation

**Files:**
- Create: `app/Http/Requests/Admin/ShippingDeliveryPageRequest.php`
- Create/Test: `tests/Feature/Admin/ShippingDeliveryPageValidationTest.php`

**Interfaces:**
- Consumes: stable IDs/default shapes from Task 1.
- Produces: `ShippingDeliveryPageRequest::validatedContent(): array`
- Produces normalized boolean fields: `remove_info_card_icon_0..1`, `remove_notice_icon`, `remove_checklist_icon_0..3`.
- Produces upload fields: `info_card_icon_0..1`, `notice_icon`, `checklist_icon_0..3`.

- [ ] **Step 1: Write failing tests for exact fixed collection sizes and ID order**

Assert validation rejects reordered/missing/duplicate IDs for:
- 5 tabs
- 2 info cards
- 3 delivery steps
- 4 FAQ items
- 4 address checklist items

The error should state that fixed Shipping & Delivery slots cannot be added, removed, duplicated, or reordered.

- [ ] **Step 2: Run validation tests and verify RED**

Run: `php artisan test tests/Feature/Admin/ShippingDeliveryPageValidationTest.php`
Expected: FAIL because request class/rules do not exist.

- [ ] **Step 3: Add tests for safe CTA URLs**

Assert relative paths and HTTPS URLs pass, while `javascript:`, `data:`, protocol-relative URLs, empty destinations, and malformed paths fail through `PublicUrl`-based validation.

- [ ] **Step 4: Add tests for all seven upload inputs and 2 MB limit**

Assert JPG/JPEG/PNG/WebP/AVIF image uploads are accepted and non-image/oversized inputs are rejected for each upload field pattern.

- [ ] **Step 5: Add tests for trimming and remove-flag normalization**

Assert normal text values are trimmed, stable `id` values are preserved exactly, and absent checkbox fields become `false` booleans.

- [ ] **Step 6: Implement `ShippingDeliveryPageRequest` minimally**

Define stable ID constants:

```php
TAB_IDS = ['before-you-order','artwork-customisation','after-you-order','delivery','help'];
INFO_CARD_IDS = ['before-you-pay','after-dispatch'];
STEP_IDS = ['confirm-address','choose-delivery-option','follow-dispatch-updates'];
FAQ_IDS = ['delivery-costs','change-address','tracking-number','multiple-locations'];
CHECKLIST_IDS = ['recipient-name','full-address','postcode','contact-details'];
```

Use exact array keys, sensible string length caps matching About-page conventions, `image/file/mimes/mimetypes/max:2048`, and `PublicUrl::isAllowed()` for the two CTA destinations.

- [ ] **Step 7: Run Task 2 tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/ShippingDeliveryPageValidationTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/Admin/ShippingDeliveryPageRequest.php tests/Feature/Admin/ShippingDeliveryPageValidationTest.php
git commit -m "feat: validate shipping delivery page content"
```

---

### Task 3: Seven-Slot Media Mutation Service

**Files:**
- Create: `app/Services/Catalog/ShippingDeliveryPageMediaService.php`
- Create/Test: `tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php`

**Interfaces:**
- Consumes: `ShippingDeliveryPageRequest`, current normalized settings from Task 1, `validatedContent()` from Task 2.
- Produces: `prepare(ShippingDeliveryPageRequest $request, array $current, array $payload): array{payload:array,new_paths:array<int,string>,old_paths:array<int,string>}`
- Produces: `rollback(array $mutation): void`
- Produces: `commitCleanup(array $mutation): void`

- [ ] **Step 1: Write failing tests for all seven upload destinations**

Assert stored paths begin with feature-owned directories such as:
- `shipping-delivery-page/info-cards/icons`
- `shipping-delivery-page/notice/icons`
- `shipping-delivery-page/address-checklist/icons`

and all seven payload slots receive paths when files are uploaded.

- [ ] **Step 2: Run media tests and verify RED**

Run: `php artisan test tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php`
Expected: FAIL because media service does not exist.

- [ ] **Step 3: Add replacement/removal tests**

Assert replacement records the prior feature-owned path in `old_paths`, explicit removal nulls the payload path and records the prior feature-owned path, and `commitCleanup()` deletes obsolete files only after successful persistence.

- [ ] **Step 4: Add rollback test**

After `prepare()`, call `rollback()` and assert all newly staged paths are deleted while previously active paths remain.

- [ ] **Step 5: Add foreign-path ownership protection test**

Set a current path such as `about-page/help/icons/shared.png` or `branding/logo.png`; assert removal/replacement never places it in `old_paths` and cleanup never deletes it.

- [ ] **Step 6: Implement the media service minimally**

Use one internal slot map covering exactly seven slots so upload/remove behavior cannot diverge between sections. `isShippingDeliveryOwnedPath()` must require the `shipping-delivery-page/` namespace and reject traversal/invalid segments.

- [ ] **Step 7: Run Task 3 tests and verify GREEN**

Run: `php artisan test tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Catalog/ShippingDeliveryPageMediaService.php tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php
git commit -m "feat: manage shipping delivery page icons"
```

---

### Task 4: RBAC, Admin Routes, and Transactional Controller

**Files:**
- Create: `app/Http/Controllers/Admin/ShippingDeliveryPageController.php`
- Modify: `app/Support/AdminRbac.php:112-127,160-172,347-370,425-440`
- Modify: `routes/web.php:110-116`
- Modify: `resources/views/components/layouts/admin.blade.php:314-325`
- Create/Test: `tests/Feature/Admin/ShippingDeliveryPageAccessTest.php`
- Create/Test: `tests/Feature/Admin/ShippingDeliveryPageManagementTest.php`

**Interfaces:**
- Consumes: `ShippingDeliveryPageService`, `ShippingDeliveryPageMediaService`, `ShippingDeliveryPageRequest`, `ShippingDeliveryPageSetting`.
- Produces routes: `admin.shipping-delivery-page.edit`, `admin.shipping-delivery-page.update`.
- Produces permissions: `shipping_delivery_page.view`, `shipping_delivery_page.manage`.

- [ ] **Step 1: Write failing access/RBAC tests**

Assert:
- authorized view permission opens editor;
- view-only admin receives 403 on update;
- unauthorized admin cannot view/update;
- `content_manager` defaults include both new permissions after RBAC sync;
- `catalog_manager`, `order_manager`, and `support_agent` do not gain the new defaults;
- existing `shipping.view`/`shipping.manage` still map to Shipping Methods routes.

- [ ] **Step 2: Run access tests and verify RED**

Run: `php artisan test tests/Feature/Admin/ShippingDeliveryPageAccessTest.php`
Expected: FAIL because permissions/routes/controller do not exist.

- [ ] **Step 3: Write failing controller success/failure tests**

Success test: submit a valid payload and assert DB settings update + redirect/status.

Failure test: force a DB exception after media staging; assert response returns editor error, old DB content remains, old active media remains, and staged new files are removed.

- [ ] **Step 4: Implement RBAC definitions and route mapping**

Add Storefront permissions near About page permissions, include both in Content Manager defaults, wire `firstAllowedRoute()`, and map the `shipping-delivery-page.*` admin route prefix in `permissionForRoute()` without touching Shipping Methods mapping.

- [ ] **Step 5: Add admin routes and controller**

Controller methods:

```php
public function edit(): View
public function update(ShippingDeliveryPageRequest $request): RedirectResponse
```

`update()` must stage uploads inside the guarded `try`, persist in `DB::transaction()`, call rollback on any staging/save exception, report the exception, preserve old state, and run obsolete-file cleanup only after commit.

- [ ] **Step 6: Add Store sidebar visibility/link**

Extend the existing Storefront sidebar visibility condition to include `shipping_delivery_page.view`, then add **Shipping & Delivery Page** adjacent to **About Page** without changing other links.

- [ ] **Step 7: Run Task 4 tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/ShippingDeliveryPageAccessTest.php tests/Feature/Admin/ShippingDeliveryPageManagementTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/ShippingDeliveryPageController.php app/Support/AdminRbac.php routes/web.php resources/views/components/layouts/admin.blade.php tests/Feature/Admin/ShippingDeliveryPageAccessTest.php tests/Feature/Admin/ShippingDeliveryPageManagementTest.php
git commit -m "feat: add shipping delivery admin access"
```

---

### Task 5: Complete Fixed-Structure Admin Editor

**Files:**
- Create: `resources/views/admin/shipping-delivery-page/edit.blade.php`
- Create/Test: `tests/Support/shipping_delivery_admin_static_check.php`

**Interfaces:**
- Consumes: normalized `$shippingDelivery` payload from `ShippingDeliveryPageController::edit()` and `$canManageShippingDeliveryPage` boolean.
- Submits: exact nested content keys expected by `ShippingDeliveryPageRequest` plus the seven upload/remove fields.

- [ ] **Step 1: Write the failing admin static check**

Require the editor to contain:
- hero fields;
- five fixed tab label inputs;
- 2 info-card title/description/icon/alt controls;
- delivery intro fields;
- timeline heading + 3 number/title/description controls;
- notice text/icon/alt controls;
- FAQ heading/subtitle + 4 question/answer pairs;
- checklist heading/subtitle + 4 title/description/icon/alt controls;
- CTA heading/description/button label/button URL/policy label/policy URL;
- SEO title/description;
- Preview link to `route('shipping')`;
- Save button;
- exactly seven logical upload slots after loop expansion.

Also fail if structural controls such as `reorder`, `add_faq`, `delete_step`, `active_tab`, or color/layout inputs are introduced.

- [ ] **Step 2: Run static check and verify RED**

Run: `php tests/Support/shipping_delivery_admin_static_check.php`
Expected: FAIL because editor does not exist.

- [ ] **Step 3: Implement the editor using existing admin card/form/button patterns**

Render sections in storefront order. For every custom icon slot show current preview, file input, remove checkbox, alt/accessibility text, and fallback-state note. Disable save controls for view-only admins while keeping Preview available.

- [ ] **Step 4: Run admin static check and verify GREEN**

Run: `php tests/Support/shipping_delivery_admin_static_check.php`
Expected: `Shipping & Delivery admin management static check passed (7 fixed upload slots).`

- [ ] **Step 5: Run focused admin feature tests**

Run: `php artisan test tests/Feature/Admin/ShippingDeliveryPageAccessTest.php tests/Feature/Admin/ShippingDeliveryPageValidationTest.php tests/Feature/Admin/ShippingDeliveryPageManagementTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/admin/shipping-delivery-page/edit.blade.php tests/Support/shipping_delivery_admin_static_check.php
git commit -m "feat: build shipping delivery admin editor"
```

---

### Task 6: Prototype Storefront Components, Page, SEO, and Layout-Matched CSS

**Files:**
- Create: `resources/views/components/storefront/shipping-delivery/tab-bar.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/info-card.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/timeline-step.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/notice.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/faq-row.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/checklist-row.blade.php`
- Create: `resources/views/components/storefront/shipping-delivery/icon.blade.php`
- Modify: `app/Http/Controllers/Storefront/ContentPageController.php:106-109`
- Replace body: `resources/views/storefront/content/shipping.blade.php:1-94`
- Modify: `resources/css/storefront.css` (append scoped section)
- Create/Test: `tests/Feature/ShippingDeliveryPageManagedContentTest.php`
- Create/Test: `tests/Support/shipping_delivery_page_static_check.php`
- Create/Test: `tests/Support/shipping_delivery_layout_static_check.php`

**Interfaces:**
- Consumes: `ShippingDeliveryPageService::settings()` from Task 1.
- Controller passes `shippingDelivery` and `seo` to `storefront.content.shipping`.
- SEO uses managed `seo.title`, `seo.description`, canonical `route('shipping')`, robots `index, follow`, schema type `WebPage`.

- [ ] **Step 1: Write failing managed-content feature test**

Persist custom titles/tab labels/card text/FAQ/checklist/CTA/SEO and assert `GET route('shipping')` renders those values, remains HTTP 200, and exposes the managed SEO title/description.

- [ ] **Step 2: Run feature test and verify RED**

Run: `php artisan test tests/Feature/ShippingDeliveryPageManagedContentTest.php`
Expected: FAIL because storefront still renders the generic hardcoded page.

- [ ] **Step 3: Write failing storefront/static structure test**

Assert the view uses:
- `<x-layouts.storefront :seo="$seo">`
- root `.np-shipping-delivery-page`
- exactly five fixed tab slots in service data with Delivery active;
- 2 info cards;
- 3 timeline steps with number circles and no step icon upload path;
- notice;
- 4 FAQ rows;
- 4 checklist rows;
- CTA button + policy link.

- [ ] **Step 4: Write failing layout/static CSS test**

Require `.np-shipping-delivery-container` to use:

```css
width: min(var(--np-commerce-layout-max), calc(100% - var(--np-visible-page-inline-space)));
```

Require all new selectors to be scoped under `.np-shipping-delivery-page`; reject a page-only `max-width: 1120px`/similar narrow container and reject global font/color redefinitions.

- [ ] **Step 5: Implement storefront controller integration**

Change signature to:

```php
public function shipping(ShippingDeliveryPageService $shippingDeliveryPage): View
```

Build managed SEO exactly like About page conventions with canonical `route('shipping')` and schema `WebPage`.

- [ ] **Step 6: Implement the seven modular storefront components and page view**

The tab bar renders disabled non-Delivery tabs as accessible non-navigation controls (`aria-disabled="true"`) and Delivery with `aria-current="page"`. FAQ rows use native `<details>/<summary>` or equivalent accessible disclosure markup without JavaScript dependency. Uploaded icon images must include inline/self-contained `object-fit: contain` sizing or equivalent scoped CSS already present in the shipped source.

- [ ] **Step 7: Add the scoped prototype CSS**

Match the supplied desktop composition: hero, five equal tabs, two top cards, timeline connector, notice, 2-column FAQ/checklist area, and bottom navy CTA. Add responsive breakpoints that stack logically with no horizontal overflow and preserve existing centralized variables/buttons.

- [ ] **Step 8: Run Task 6 tests and verify GREEN**

Run:

```bash
php artisan test tests/Feature/ShippingDeliveryPageManagedContentTest.php
php tests/Support/shipping_delivery_page_static_check.php
php tests/Support/shipping_delivery_layout_static_check.php
```

Expected: all PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Storefront/ContentPageController.php resources/views/storefront/content/shipping.blade.php resources/views/components/storefront/shipping-delivery resources/css/storefront.css tests/Feature/ShippingDeliveryPageManagedContentTest.php tests/Support/shipping_delivery_page_static_check.php tests/Support/shipping_delivery_layout_static_check.php
git commit -m "feat: build managed shipping delivery page"
```

---

### Task 7: End-to-End Regression and Delivery Verification

**Files:**
- Modify tests from Tasks 1–6 only if a verified gap is found.
- No unrelated production refactors.

**Interfaces:**
- Consumes the complete feature from Tasks 1–6.
- Produces a verified deliverable with no hidden regression in About page, Shipping Methods, or global layout.

- [ ] **Step 1: Run every focused Shipping & Delivery test**

Run:

```bash
php artisan test \
  tests/Unit/Services/Storefront/ShippingDeliveryPageServiceTest.php \
  tests/Unit/Services/Catalog/ShippingDeliveryPageMediaServiceTest.php \
  tests/Feature/Admin/ShippingDeliveryPageAccessTest.php \
  tests/Feature/Admin/ShippingDeliveryPageValidationTest.php \
  tests/Feature/Admin/ShippingDeliveryPageManagementTest.php \
  tests/Feature/ShippingDeliveryPageManagedContentTest.php
php tests/Support/shipping_delivery_page_static_check.php
php tests/Support/shipping_delivery_admin_static_check.php
php tests/Support/shipping_delivery_layout_static_check.php
```

Expected: all PASS.

- [ ] **Step 2: Run independence regressions for About page and Shipping Methods permissions**

Run at least:

```bash
php artisan test tests/Unit/Services/Storefront/AboutPageServiceTest.php tests/Feature/Admin/AboutPageAccessTest.php
```

and the existing test(s) that exercise `shipping.view` / `shipping.manage` if present. Expected: PASS and unchanged permission semantics.

- [ ] **Step 3: Run the complete Laravel suite**

Run: `php artisan test`
Expected: zero failures. If pre-existing unrelated failures exist, record each by test name and do not hide them.

- [ ] **Step 4: Run PHP syntax checks for all changed/new PHP files**

Run `php -l` across the model, services, request, controllers, migration, Blade PHP files where practical, and support checks. Expected: no syntax errors.

- [ ] **Step 5: Run production frontend build**

Run: `npm run build`
Expected: exit code 0 and Vite assets generated successfully.

- [ ] **Step 6: Verify no unrelated storefront CSS/global markup changes**

Inspect diff for changes outside `.np-shipping-delivery-page` styles and intended admin/storefront files. Confirm shared header/footer templates and checkout shipping code are untouched.

- [ ] **Step 7: Final whole-change review**

Review against the spec's fixed structure, all seven icon slots, rolling-deployment fallback, URL safety, transaction semantics, RBAC independence, centralized layout width, and responsive prototype match. Fix any Critical/Important finding through RED→GREEN before packaging.

- [ ] **Step 8: Commit final verification-only adjustments, if any**

```bash
git add <only verified adjustment files>
git commit -m "test: verify shipping delivery page integration"
```

If no adjustments were needed, do not create an empty commit.
