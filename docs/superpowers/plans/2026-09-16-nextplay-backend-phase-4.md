# NextPlay Backend Phase 4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Repair the reported regression baseline and expose the complete catalog/category/search/product-detail read side through `/api/v1` without changing legacy Blade, admin, payment, or FlowTrack contracts.

**Architecture:** Keep Laravel-native clean boundaries: API Form Requests validate/normalize query input, thin API controllers orchestrate existing catalog services, and API Resources own public JSON contracts. Existing Storefront services remain the authoritative read-side implementation so Blade and API results stay aligned. Additive API code is preferred; existing service changes are limited to reusable read helpers and verified regression fixes.

**Tech Stack:** Laravel 13, PHP 8.3+, Eloquent/MySQL, Laravel API Resources, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-16-nextplay-backend-clean-architecture-design.md`

## Global Constraints

- Keep one Laravel project.
- Laravel remains authoritative for all catalog and business data.
- All new storefront APIs use `/api/v1`.
- API controllers stay thin; validation belongs in Form Requests; JSON contracts use API Resources.
- Do not return Eloquent models directly.
- Preserve legacy Blade storefront/admin/payment/FlowTrack routes and behavior.
- Do not add Vue or frontend business logic in this phase.
- Avoid N+1 queries through the existing eager-loading relation sets.

---

### Task 1: Restore the regression baseline

**Files:** reported failing production/test files only.

- [ ] Trace each of the 9 reported failures to its source.
- [ ] Fix genuine production regressions with the smallest behavior-preserving change.
- [ ] Update stale tests only where the implementation intentionally changed contract (`/media` public delivery and separated product-shipping totals), adding stronger assertions for the new behavior.
- [ ] Run focused regression tests in an environment with Composer/vendor; otherwise perform syntax/static verification and document the commands for local execution.

### Task 2: Add API-specific catalog filter contracts

**Files:**
- Create `app/Http/Requests/Api/V1/Catalog/ProductIndexRequest.php`
- Create `app/Http/Requests/Api/V1/Catalog/CategoryShowRequest.php`
- Create `app/Http/Requests/Api/V1/Catalog/SearchSuggestionRequest.php`
- Test `tests/Feature/Api/V1/Catalog/CatalogFilterValidationTest.php`

- [ ] Mirror current `ProductFilterRequest`/`CategoryFilterRequest` query names and validation semantics.
- [ ] Return normalized filter arrays for services.
- [ ] Cover invalid sort/range/attribute/query inputs with JSON 422 tests.

### Task 3: Add category collection/detail APIs

**Files:**
- Create `app/Http/Controllers/Api/V1/Catalog/CategoryController.php`
- Create `app/Http/Resources/Api/V1/Catalog/CategoryResource.php`
- Create `app/Http/Resources/Api/V1/Catalog/CategoryCollectionResource.php`
- Create `app/Http/Resources/Api/V1/Catalog/CategoryDetailResource.php`
- Modify `app/Services/Storefront/CategoryCatalogService.php`
- Modify `routes/api.php`
- Test `tests/Feature/Api/V1/Catalog/CategoryApiTest.php`

- [ ] `GET /api/v1/categories` exposes product categories, sports, tags, recursive counts.
- [ ] `GET /api/v1/categories/{slug}` exposes category metadata, breadcrumbs, filters, related categories, paginated products, SEO-safe fields.
- [ ] Unknown/inactive/unreachable categories return API 404.

### Task 4: Add product collection/detail/search suggestion APIs

**Files:**
- Create `app/Http/Controllers/Api/V1/Catalog/ProductController.php`
- Create `app/Http/Controllers/Api/V1/Catalog/SearchSuggestionController.php`
- Create `app/Http/Resources/Api/V1/Catalog/ProductCardResource.php`
- Create `app/Http/Resources/Api/V1/Catalog/ProductResource.php`
- Create `app/Http/Resources/Api/V1/Catalog/ProductCollectionResource.php`
- Create `app/Http/Resources/Api/V1/Catalog/SearchSuggestionResource.php`
- Modify `app/Services/Storefront/CategoryCatalogService.php`
- Modify `routes/api.php`
- Test `tests/Feature/Api/V1/Catalog/ProductApiTest.php`
- Test `tests/Feature/Api/V1/Catalog/SearchSuggestionApiTest.php`
- Test `tests/Feature/Api/V1/Catalog/CatalogQueryPerformanceTest.php`

- [ ] `GET /api/v1/products` matches legacy filtering/sorting semantics and returns pagination/filter/active-filter metadata.
- [ ] `GET /api/v1/products/{slug}` returns complete non-sensitive product read data with related products and SEO metadata.
- [ ] `GET /api/v1/search/suggestions` returns structured products/categories; empty query returns empty arrays; route has a tighter throttle.
- [ ] Query-count tests establish a baseline for 1 vs many list rows and product detail relation loading.

### Task 5: Phase gate verification

- [ ] Run `php artisan test tests/Feature/StorefrontCatalogFiltersTest.php tests/Feature/StorefrontCategoryTest.php tests/Feature/ProductConfigurationBackendTest.php`.
- [ ] Run `php artisan test tests/Feature/Api/V1/Catalog`.
- [ ] Run full `php artisan test` and confirm zero unexplained failures.
- [ ] Run `php artisan route:list --path=api/v1` and confirm the five Phase 4 routes.
- [ ] Run syntax checks and inspect that legacy controllers/routes remain present.
