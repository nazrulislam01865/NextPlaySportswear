# NextPlay Backend Phase 5 + 6 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add authoritative product configuration/price-preview APIs and data-first cart/wishlist APIs while preserving all existing Blade/admin/payment/FlowTrack behavior.

**Architecture:** Keep Laravel as the source of truth. Reuse `ProductCatalogService`, `CartService`, `CouponService`, and the existing persistence models. Add thin `/api/v1` controllers, API Form Requests, explicit Resources, focused application services, and a shared configuration/pricing path so preview and cart calculations cannot diverge.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL/SQLite tests, Laravel session/database cart persistence, Laravel API Resources.

**Spec:** `docs/superpowers/specs/2026-09-16-nextplay-backend-clean-architecture-design.md`

## Global Constraints

- Keep one Laravel project; do not introduce another backend or microservices.
- Existing Blade storefront/admin routes remain operational during migration.
- Vue never owns authoritative pricing, discount, shipping, surcharge, checkout, payment, or order logic.
- New storefront APIs remain under `/api/v1`; FlowTrack integration routes stay separate.
- Do not return raw Eloquent models or Blade HTML from new API responses.
- Reuse existing services and existing persistence behavior; avoid duplicate business logic.
- Frontend-supplied price/totals are always ignored.
- Uploaded artwork remains validated/stored by Laravel.

---

### Task 1: Lock Phase 5 behavior with regression tests

**Files:**
- Create: `tests/Feature/Api/V1/Catalog/ProductConfigurationApiTest.php`
- Create: `tests/Feature/Api/V1/Catalog/ProductPricePreviewTest.php`
- Modify: `tests/Feature/ProductConfigurationBackendTest.php`

**Interfaces:**
- Produces expectations for `GET /api/v1/products/{slug}/configuration`, `POST /api/v1/products/{slug}/price-preview`, and `CartService::previewItem(array): array`.

- [ ] Add tests proving normalized configuration data includes option groups, size groups, roster, artwork, production/shipping, sample, price/fabric tables, and profile metadata.
- [ ] Add explicit Bag, Lanyard, Quarter Zip, and Training Vest fixtures.
- [ ] Add preview-versus-final-cart pricing parity test.
- [ ] Add tests rejecting invalid customer option IDs, duplicate checkbox values, invalid fulfillment choices, and generic size quantities for dedicated-size profiles.
- [ ] Add a test proving submitted price/totals never affect Laravel-calculated output.

### Task 2: Add normalized configuration + authoritative preview application boundary

**Files:**
- Create: `app/Services/Catalog/ProductConfigurationService.php`
- Create: `app/Http/Controllers/Api/V1/Catalog/ProductConfigurationController.php`
- Create: `app/Http/Requests/Api/V1/Catalog/ProductPricePreviewRequest.php`
- Create: `app/Http/Resources/Api/V1/Catalog/ProductConfigurationResource.php`
- Create: `app/Http/Resources/Api/V1/Catalog/ProductPricePreviewResource.php`
- Modify: `app/Services/Storefront/ProductCatalogService.php`
- Modify: `app/Services/Cart/CartService.php`
- Modify: `routes/api.php`

**Interfaces:**
- `ProductConfigurationService::configuration(string $slug): ?array`
- `ProductConfigurationService::preview(string $slug, array $payload): ?array`
- `CartService::previewItem(array $payload): array`

- [ ] Expose explicit normalized configuration data without raw models/internal fields.
- [ ] Add `master_type` metadata to product option groups.
- [ ] Prevent generic size-group exposure for profiles that use dedicated customization size options.
- [ ] Route preview through the same CartService sanitization and repricing methods used by final add-to-cart.
- [ ] Validate submitted configuration strictly rather than silently accepting invalid customer selections.
- [ ] Skip only required artwork-presence validation during price preview; keep all pricing/config validation authoritative.

### Task 3: Extract shared cart input and artwork handling

**Files:**
- Create: `app/Http/Requests/Concerns/HasCartItemRules.php`
- Create: `app/Services/Cart/CartArtworkService.php`
- Modify: `app/Http/Requests/Storefront/AddCartItemRequest.php`
- Modify: `app/Http/Requests/Storefront/UpdateCartItemOptionsRequest.php`
- Modify: `app/Http/Controllers/Storefront/CartController.php`

**Interfaces:**
- `HasCartItemRules::cartItemRules(bool $includeRetainedArtwork = false): array`
- `CartArtworkService::prepare(Request $request, array $product, array $existingArtwork = []): array`

- [ ] Move only request-rule definitions into a reusable concern so API and Blade validate the same shape.
- [ ] Extract existing artwork validation/storage unchanged into `CartArtworkService`.
- [ ] Keep the legacy controller behavior and redirects unchanged while delegating artwork preparation to the new service.

### Task 4: Implement data-first cart API with idempotent add

**Files:**
- Create: `app/Http/Controllers/Api/V1/Cart/CartController.php`
- Create: `app/Http/Controllers/Api/V1/Cart/CartItemController.php`
- Create: `app/Http/Controllers/Api/V1/Cart/CouponController.php`
- Create: `app/Http/Requests/Api/V1/Cart/CartItemStoreRequest.php`
- Create: `app/Http/Requests/Api/V1/Cart/CartItemUpdateRequest.php`
- Create: `app/Http/Requests/Api/V1/Cart/CartItemOptionsRequest.php`
- Create: `app/Http/Requests/Api/V1/Cart/CouponApplyRequest.php`
- Create: `app/Http/Resources/Api/V1/Cart/CartResource.php`
- Create: `app/Http/Resources/Api/V1/Cart/CartItemResource.php`
- Create: `app/Services/Cart/CartMutationIdempotencyService.php`
- Modify: `routes/api.php`

**Interfaces:**
- Cart mutations return the complete recalculated `CartResource`.
- `CartMutationIdempotencyService::replay(Request,string,array): ?array`
- `CartMutationIdempotencyService::remember(Request,string,array,array): void`

- [ ] Add GET/add/update/options/remove/coupon endpoints with `web` middleware for session-aware guest carts.
- [ ] Never return `item_html` or rendered Blade fragments.
- [ ] Add optional `Idempotency-Key` replay protection to add-item before any artwork storage occurs.
- [ ] Recalculate every response through `CartService`; never use browser totals.
- [ ] Return invalid/expired coupon errors with the existing CouponService message and HTTP 422.

### Task 5: Move wishlist persistence behind a service and expose API

**Files:**
- Create: `app/Services/Wishlist/WishlistService.php`
- Create: `app/Http/Controllers/Api/V1/Wishlist/WishlistController.php`
- Create: `app/Http/Requests/Api/V1/Wishlist/WishlistStoreRequest.php`
- Create: `app/Http/Resources/Api/V1/Wishlist/WishlistResource.php`
- Modify: `app/Http/Controllers/Storefront/ProductWishlistController.php`
- Modify: `routes/api.php`

**Interfaces:**
- `WishlistService::summary(?User $user): array`
- `WishlistService::add(?User $user, int $productId): array`
- `WishlistService::remove(?User $user, int $productId): array`

- [ ] Authenticated customers continue using `product_wishlists` ownership.
- [ ] API guests get session-scoped wishlist IDs so add/remove works before Phase 7 auth migration.
- [ ] Preserve favorites counters transactionally for authenticated wishlist mutations.
- [ ] Legacy controller delegates persistence to `WishlistService` without changing Blade/local-storage behavior.

### Task 6: Add Phase 6 feature/regression coverage

**Files:**
- Create: `tests/Feature/Api/V1/Cart/CartApiTest.php`
- Create: `tests/Feature/Api/V1/Cart/CartCouponApiTest.php`
- Create: `tests/Feature/Api/V1/Wishlist/WishlistApiTest.php`

**Interfaces:**
- Covers all Phase 6 endpoints and response contracts.

- [ ] Test guest cart persistence across requests and authoritative totals.
- [ ] Test authenticated cart ownership behavior using existing cart persistence.
- [ ] Test idempotency-key replay does not double-add quantity.
- [ ] Test configuration survives add/options/update cycles.
- [ ] Test coupon valid/invalid/expired behavior against `CouponService`.
- [ ] Test guest wishlist session behavior and authenticated database ownership.
- [ ] Assert cart/wishlist JSON contains no `item_html` or Blade markup.

### Task 7: Documentation and verification gate

**Files:**
- Create: `docs/backend/api-v1-phase-5-6.md`
- Modify only if required by verified regressions: existing implementation files.

**Interfaces:**
- Documents normalized configuration schema, preview request/response, cart/wishlist contracts, idempotency semantics, and rollback routes.

- [ ] Run focused Phase 5 tests.
- [ ] Run focused Phase 6 tests.
- [ ] Run `ProductConfigurationBackendTest`, legacy cart/wishlist regression coverage, Phase 4 catalog tests, auth/order tests, and full suite.
- [ ] Run `php artisan route:list --path=api/v1` and verify FlowTrack routes remain outside v1.
- [ ] Run PHP syntax validation and package the verified project.
