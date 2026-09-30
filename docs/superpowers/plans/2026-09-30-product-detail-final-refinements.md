# Product Detail Final Refinements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refine the NEXTPLAY product-detail customizer and sidebar to match the approved prototype while adding optional Production Method and Shipping Method media in existing master data.

**Architecture:** Keep the existing Laravel Blade + Alpine product-builder as the single storefront state source. Extend existing `production_methods` and `shipping_methods` rows with nullable media fields, resolve them through `PublicMedia`, and expose them through `ProductCatalogService`; do not introduce a generic media subsystem or new sample-request flow.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Alpine.js, Tailwind/PostCSS/Vite, MySQL.

**Spec:** `docs/superpowers/specs/2026-09-30-product-detail-final-refinements-design.md`

## Global Constraints

- Request a Sample is explicitly out of scope.
- Reuse centralized NEXTPLAY colors, fonts, and button styles.
- Do not change product pricing formulas, cart persistence, checkout, Stripe, campaigns, categories, or email logic.
- Reuse existing ProductCatalogService and master-data models/controllers/views.
- Sidebar completion state reflects existing builder validation and does not replace final submit validation.

## Review Focus

- Replacing/removing an uploaded master image must not delete files outside `master-data/production-methods/` or `master-data/shipping-methods/`.
- External `image_url` and uploaded file precedence must remain deterministic and safely resolved through `PublicMedia`.
- Master-linked product methods inherit new media; unlinked legacy methods keep fallback storefront icons.
- Sidebar Add to Cart must remain disabled if any existing required builder condition is incomplete, while final submit validation remains authoritative.
- Review/sidebar totals must reuse existing pricing methods and never calculate a second independent total.

---

### Task 1: Master production/shipping media persistence

**Files:**
- Create: `database/migrations/2026_09_30_000300_add_media_to_production_and_shipping_methods.php`
- Create: `app/Services/Catalog/MasterMethodMediaService.php`
- Modify: `app/Models/ProductionMethod.php`
- Modify: `app/Models/ShippingMethod.php`
- Modify: `app/Http/Requests/Admin/ProductionMethodRequest.php`
- Modify: `app/Http/Requests/Admin/ShippingMethodRequest.php`
- Modify: `app/Http/Controllers/Admin/ProductionMethodController.php`
- Modify: `app/Http/Controllers/Admin/ShippingMethodController.php`
- Test: `tests/Feature/Admin/MasterMethodMediaTest.php`

**Interfaces:**
- Produces: `ProductionMethod::imageUrl(): ?string`, `ShippingMethod::imageUrl(): ?string`.
- Produces: `MasterMethodMediaService::persist(Model $method, FormRequest $request, string $directory): void` and `deleteOwned(?string $path, string $directory): void`.

- [ ] **Step 1: Write failing feature tests** for upload, external URL, replacement, explicit removal, destroy cleanup, and path-ownership protection for both master types.
- [ ] **Step 2: Run the focused tests** and confirm failure because media columns/helpers do not exist.
- [ ] **Step 3: Add nullable `image_path`/`image_url` columns** to both master tables and model fillables plus `imageUrl()` via `PublicMedia::url()`.
- [ ] **Step 4: Add request validation** for optional `image_file`, nullable public `image_url`, and boolean `remove_image`; allow JPG/JPEG/PNG/WebP/AVIF with the same small upload limit used elsewhere in admin.
- [ ] **Step 5: Implement focused media persistence** under `master-data/production-methods` and `master-data/shipping-methods`, with owned-path deletion only.
- [ ] **Step 6: Wire controllers** so create/update/destroy persist or clean media without changing default-method behavior.
- [ ] **Step 7: Run focused tests** and confirm pass.

### Task 2: Admin master-data media controls

**Files:**
- Modify: `resources/views/admin/production-methods/_form.blade.php`
- Modify: `resources/views/admin/shipping-methods/_form.blade.php`
- Test: `tests/Regression/MasterMethodMediaAdminUiTest.php`

**Interfaces:**
- Consumes: `imageUrl()` and request fields from Task 1.
- Produces: multipart admin forms with upload, URL, current preview, and remove control.

- [ ] **Step 1: Write a failing static regression test** asserting both forms are multipart and contain media upload/url/preview/remove controls.
- [ ] **Step 2: Run the test** and confirm failure.
- [ ] **Step 3: Add a reusable-looking Media/Icon section** to both existing forms using the existing admin design system; do not alter unrelated fields.
- [ ] **Step 4: Run the regression test** and confirm pass.

### Task 3: Storefront payload exposes master method media

**Files:**
- Modify: `app/Services/Storefront/ProductCatalogService.php`
- Test: `tests/Feature/Storefront/ProductMethodMediaPayloadTest.php`

**Interfaces:**
- Consumes: `ProductionMethod::imageUrl()` and `ShippingMethod::imageUrl()`.
- Produces: `production_speeds[*].image` and `shipping_methods[*].image` in the existing builder payload.

- [ ] **Step 1: Write failing payload tests** proving master-linked production/shipping methods expose resolved media and legacy unlinked rows return `null` media.
- [ ] **Step 2: Run focused tests** and confirm failure.
- [ ] **Step 3: Eager-load `shippingMethods.shippingMethod`** and map master relation data/media without changing price-source logic.
- [ ] **Step 4: Map production media** from the already-loaded `productionMethod` relation.
- [ ] **Step 5: Run focused tests** and confirm pass.

### Task 4: Product header and option/image refinements

**Files:**
- Modify: `resources/views/components/storefront/product/builder.blade.php`
- Modify: `resources/views/components/storefront/product/purchase-signals.blade.php`
- Modify: `resources/css/storefront.css`
- Test: `tests/Regression/ProductDetailFinalRefinementsTest.php`

**Interfaces:**
- Produces: copyable SKU beside shopper activity using local Alpine state only.

- [ ] **Step 1: Write failing static regression assertions** for SKU placement/copy hook, larger material media without outer media frame, larger size images, and hidden roster Preview column.
- [ ] **Step 2: Run the regression test** and confirm failure.
- [ ] **Step 3: Move SKU beside the shopper-count metadata** and add a compact copy button with temporary copied feedback using `navigator.clipboard.writeText` with a safe fallback.
- [ ] **Step 4: Enlarge material option media** and remove the extra image-frame border/background while retaining selected-card state.
- [ ] **Step 5: Enlarge size-row sample images** while preserving `object-fit: contain`.
- [ ] **Step 6: Enlarge player-step summary image and remove the Preview column/header/cells**, redistributing table widths without changing roster behavior.
- [ ] **Step 7: Run regression test** and confirm pass.

### Task 5: Production/shipping option cards use uploaded media

**Files:**
- Modify: `resources/views/components/storefront/product/builder.blade.php`
- Modify: `resources/css/storefront.css`
- Test: `tests/Regression/ProductMethodMediaStorefrontTest.php`

**Interfaces:**
- Consumes: `production_speeds[*].image` and `shipping_methods[*].image` from Task 3.
- Produces: image/icon-first option cards with existing inline SVG fallback when no media exists.

- [ ] **Step 1: Write failing static regression assertions** for payload image hooks and fallback icon markup.
- [ ] **Step 2: Run test** and confirm failure.
- [ ] **Step 3: Render uploaded media in production/shipping cards** with contained sizing; keep current SVG only when `image` is empty.
- [ ] **Step 4: Run test** and confirm pass.

### Task 6: Complete Review & Add to Cart presentation

**Files:**
- Modify: `resources/views/components/storefront/product/builder.blade.php`
- Modify: `resources/css/storefront.css`
- Test: `tests/Regression/ProductReviewCompletenessTest.php`

**Interfaces:**
- Consumes: existing Alpine builder methods/state only (`totalQuantity`, `unitPrice`, `totalPrice`, option selections, roster, artwork, production, shipping).
- Produces: complete order-review sections and primary/secondary actions without duplicate calculations.

- [ ] **Step 1: Write failing regression assertions** for product/SKU/fabric/options, size quantities, roster details, artwork, production/shipping timing, pricing rows, surcharge row, Add to Cart, and Save for Later.
- [ ] **Step 2: Run test** and confirm failure.
- [ ] **Step 3: Expand review markup** into readable sections using the existing builder state and exact existing calculation methods.
- [ ] **Step 4: Make Add to Cart the primary action and Save for Later secondary**, using centralized button classes.
- [ ] **Step 5: Run regression test** and confirm pass.

### Task 7: Prototype-matched `Your Custom Order` sidebar and completion-gated cart action

**Files:**
- Modify: `resources/views/components/storefront/product/customizer/order-summary.blade.php`
- Modify: `resources/views/components/storefront/product/builder.blade.php`
- Modify: `resources/css/storefront.css`
- Test: `tests/Regression/ProductCustomOrderPrototypeAndCompletionTest.php`

**Interfaces:**
- Consumes: existing builder state and a new side-effect-free `canAddToCart(): boolean` method that mirrors current client validation prerequisites.
- Produces: prototype structure with pricing lines and sidebar submit button bound to the existing form.

- [ ] **Step 1: Write failing regression assertions** for dark header, product block, fabric, compact size chips/rows, players, artwork, production/shipping, product price, shipping, surcharge, total, Add to Cart, and Save as Quote.
- [ ] **Step 2: Add a focused JS/static test** asserting sidebar button uses `:disabled="!canAddToCart()"` and no independent total formula is introduced.
- [ ] **Step 3: Run tests** and confirm failure.
- [ ] **Step 4: Add `canAddToCart()`** by reflecting the same prerequisites used by the existing builder validation without mutating state or replacing final validation.
- [ ] **Step 5: Rebuild the sidebar markup** to match the supplied prototype and use existing pricing/shipping/surcharge functions.
- [ ] **Step 6: Wire sidebar Add to Cart to the existing form submission**, visible throughout but muted/disabled until `canAddToCart()` is true; preserve existing quote action.
- [ ] **Step 7: Run focused tests** and confirm pass.

### Task 8: Final verification and packaging

**Files:**
- Verify all changed files and build output.
- Package full project ZIP excluding dependency/cache directories.

- [ ] **Step 1: Run PHP syntax checks** on changed PHP files and Blade structural guards.
- [ ] **Step 2: Run focused feature/regression tests** from Tasks 1–7.
- [ ] **Step 3: Run existing product-detail regression suite** and report any unrelated pre-existing failures separately.
- [ ] **Step 4: Run `npm run build`** and require exit code 0 before claiming build success.
- [ ] **Step 5: Run ZIP integrity test** after packaging the complete project.
