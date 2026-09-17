# NextPlay Storefront API v1 - Milestone A

Date: 2026-09-16
Scope: Backend foundation only

## Purpose

Milestone A introduces the first versioned storefront JSON boundary inside the existing NextPlay Laravel application. It is additive: the existing Blade storefront, Blade admin, Stripe/payment routes, and FlowTrack integration routes remain in place.

## Endpoints

### `GET /api/v1/health`

Lightweight API connectivity endpoint.

Success shape:

```json
{
  "data": {
    "status": "ok",
    "api_version": "v1",
    "timestamp": "2026-09-16T06:00:00+00:00"
  },
  "meta": {},
  "request_id": "..."
}
```

### `GET /api/v1/storefront/bootstrap`

Returns safe global storefront state required by a future Vue shell/header:

- public site name/tagline/logo
- safe authenticated customer summary when the current `web` session is a customer
- cart quantity/item count
- wishlist count
- normalized header/footer navigation

The endpoint deliberately uses the existing first-party `web` session on this read-only route so the future same-origin storefront can coexist with the current Blade authentication/session behavior. Authentication migration/hardening remains a later backend phase.

Sensitive user/admin/payment/integration fields are not included.

### `GET /api/v1/home`

Returns the current homepage data through an explicit API Resource contract backed by the existing `HomePageService`.

Public fields:

- `seo`
- `slides`
- `sections`
- `categories`
- `buyer_paths`
- `featured_products`
- `latest_products`
- `latest_products_signature`
- `best_selling_products`
- `best_selling_gear_categories`
- `sports`
- `process_steps`
- `faqs`
- `navigation`
- `menus`

The API does not render or return the Blade latest-products HTML fragment used by the legacy storefront polling route.

## Request ID contract

All `/api/v1/*` requests receive an `X-Request-ID` response header and root `request_id` JSON field.

A caller-supplied `X-Request-ID` is preserved only when it matches:

```text
[A-Za-z0-9._:-]{1,100}
```

Otherwise Laravel generates a UUID. The ID is also added to Laravel log context.

## Error contract

New v1 endpoints use a stable JSON error contract.

Validation example:

```json
{
  "message": "Validation failed.",
  "errors": {
    "field": ["The field is required."]
  },
  "request_id": "..."
}
```

Other normalized statuses include 401, 403, 404, 405, 409, 429, and 500.

The custom error rendering is scoped to `/api/v1/*`; it does not change FlowTrack integration error contracts.

## FlowTrack isolation

These existing routes remain separate and unchanged:

```text
/api/integrations/flowtrack/order-items/{orderItem}/artworks/{index}
/api/integrations/flowtrack/bulk-quotes/{bulkQuote}/attachment
```

They retain the existing `VerifyFlowTrackIntegration` middleware and numeric throttle.

## Legacy coexistence

Milestone A does not change:

- `/` legacy Blade homepage
- `/homepage/latest-products`
- Blade admin routes/controllers/views
- Stripe webhooks/payment orchestration
- FlowTrack payload factories/sync managers
- catalog/cart/checkout business rules

The API and legacy storefront therefore coexist during the migration.

## Tests added

```text
tests/Feature/Api/V1/ApiFoundationTest.php
tests/Feature/Api/V1/Storefront/StorefrontBootstrapApiTest.php
tests/Feature/Api/V1/Storefront/HomepageApiTest.php
tests/Unit/Services/Storefront/StorefrontBootstrapServiceTest.php
```

Run in the normal project environment after `composer install`:

```bash
php artisan test tests/Feature/Api/V1/ApiFoundationTest.php
php artisan test tests/Feature/Api/V1/Storefront/StorefrontBootstrapApiTest.php
php artisan test tests/Feature/Api/V1/Storefront/HomepageApiTest.php
php artisan test tests/Unit/Services/Storefront/StorefrontBootstrapServiceTest.php

php artisan test \
  tests/Feature/HomepageProductCollectionsTest.php \
  tests/Feature/HomepageSliderTest.php \
  tests/Unit/HomepageSectionOrderTest.php \
  tests/Feature/AuthenticationSeparationTest.php \
  tests/Feature/Admin/FlowTrackOrderSyncAdminTest.php

php artisan test
```

## Environment note for this extracted archive

The uploaded archive did not contain `vendor/`, and the execution container did not have Composer available or network access to install it. Therefore PHPUnit/Laravel runtime tests could not be executed here. All changed/new PHP files were syntax checked with `php -l`; the commands above are the required runtime verification gate in the real working copy before deployment.
