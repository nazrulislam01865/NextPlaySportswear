# API v1 Phase 4 — Catalog, Categories, Search and Product Detail

Date: 2026-09-16

## Scope

Phase 4 exposes the existing NextPlay catalog read side through frontend-neutral JSON while the legacy Blade catalog remains active. Laravel remains authoritative for category reachability, product visibility, filtering, sorting, prices, inventory/backorder state, product configuration read data, SEO metadata and related products.

## Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/categories` | Reachable product categories, sports, category tags and recursive product counts |
| GET | `/api/v1/categories/{slug}` | Category detail, breadcrumbs, filters, paginated products, CMS blocks, FAQ and SEO data |
| GET | `/api/v1/products` | Paginated product catalog with the same filter/sort semantics as the Blade catalog |
| GET | `/api/v1/products/{slug}` | Complete non-sensitive product read model and related products |
| GET | `/api/v1/search/suggestions` | Structured product + category suggestions |

All endpoints use the Phase 1 response conventions and include `request_id`. Search suggestions additionally use the `catalog-search-suggestions` limiter (60 requests/minute per IP) inside the existing public API limiter.

## Filter contract

The API and Blade catalog now share presentation-neutral base request contracts:

- `App\Http\Requests\Catalog\ProductCatalogFilterRequest`
- `App\Http\Requests\Catalog\CategoryCatalogFilterRequest`

The public API retains the existing query names:

- `q`
- `tag` (product collection only)
- `categories` (product collection only)
- `subcategory` (category detail only)
- `sports`
- `product_types`
- `colors`
- `materials`
- `artwork_methods`
- `attributes[attribute-slug][]`
- `min_price`
- `max_price`
- `moq`
- `customization`
- `availability`
- `min_rating`
- `sort`

Supported sort values remain `featured`, `best-selling`, `newest`, `price-low`, `price-high`, `rating-high`, and `name-asc`.

## Clean architecture boundary

```
/api/v1
  -> API Form Request
  -> thin Catalog controller
  -> CatalogReadService
  -> existing ProductCatalogService / CategoryCatalogService / ProductCatalogCacheService
  -> explicit API Resource
  -> JSON
```

`CatalogReadService` is an application/read-model orchestrator only. It does not duplicate pricing, catalog visibility, category reachability, filtering or sorting rules.

## Regression repairs made before Phase 4

The reported baseline failures were traced individually:

1. Training Vest size registry label restored to `Size Options`.
2. Public-media tests now assert the intentional `/media/*` delivery contract rather than the legacy `/storage/*` contract.
3. Safe HTML sanitization now preserves a quoted `target="_blank"` and adds `rel="noopener noreferrer"`.
4. Bulk quote admin page title restored to `Requested Bulk Quotes`.
5. Customer sessions are evaluated by the admin-hiding middleware before Laravel's authentication redirect middleware, preserving the expected 404 separation.
6. Private order downloads now declare the actual streamed response type returned by Laravel storage downloads.
7. Product configuration regression coverage now treats product shipping as its existing separate charge category rather than folding it into customization unit price.
8. Artwork guideline copy restored to `Preferred Files`.

## Phase 4 tests added

- `tests/Feature/Api/V1/Catalog/CatalogFilterValidationTest.php`
- `tests/Feature/Api/V1/Catalog/CategoryApiTest.php`
- `tests/Feature/Api/V1/Catalog/ProductApiTest.php`
- `tests/Feature/Api/V1/Catalog/SearchSuggestionApiTest.php`
- `tests/Feature/Api/V1/Catalog/CatalogQueryPerformanceTest.php`

The performance tests compare a one-row product listing with a ten-row listing to catch row-proportional relation queries and place a bounded query-count guard around product detail.

## Verification commands

The supplied archive does not include `vendor/`, so the Laravel runtime tests must be executed in the normal project environment after `composer install` (or with the project's existing vendor directory):

```bash
php artisan optimize:clear

php artisan test tests/Unit/JerseyCustomizationTypeTest.php
php artisan test tests/Unit/PublicMediaTest.php
php artisan test tests/Unit/SafeHtmlServiceTest.php
php artisan test tests/Feature/Admin/BulkQuoteRequestAdminTest.php
php artisan test tests/Feature/AuthenticationSeparationTest.php
php artisan test tests/Feature/OrderManagementTest.php
php artisan test tests/Feature/ProductConfigurationBackendTest.php
php artisan test tests/Feature/StorefrontContentPagesTest.php

php artisan test tests/Feature/Api/V1/Catalog
php artisan test tests/Feature/StorefrontCatalogFiltersTest.php
php artisan test tests/Feature/StorefrontCategoryTest.php
php artisan test tests/Feature/ProductConfigurationBackendTest.php

php artisan route:list --path=api/v1
php artisan test
```

## Phase gate

Do not begin Phase 5 until the commands above pass in the real Laravel environment, including the query-count tests. Static PHP validation in the artifact is not a substitute for this runtime gate.
