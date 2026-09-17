# NextPlay Backend Milestone A Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish the first production-safe `/api/v1` backend boundary for NextPlay with request tracing, consistent JSON errors, API Resources/Form Request conventions, storefront bootstrap data, and homepage JSON while preserving every existing Blade/admin/payment/FlowTrack route and behavior.

**Architecture:** Additive Laravel-native Clean Architecture. New API controllers stay thin, read operations delegate to existing application services or the new focused `StorefrontBootstrapService`, and API Resources own JSON serialization. Legacy Blade controllers continue calling their existing services so Milestone A is rollback-safe and does not force any storefront cutover.

**Tech Stack:** Laravel 13.8, PHP 8.3+, PHPUnit 12, Eloquent, existing MySQL/SQLite test configuration, Laravel API Resources, Form Requests, middleware, session authentication.

**Spec:** `docs/superpowers/specs/2026-09-16-nextplay-backend-clean-architecture-design.md`

## Global Constraints

- Keep one Laravel project.
- Keep Laravel 13 / PHP 8.3+ / MySQL.
- Do not create a separate backend or frontend Laravel project.
- Do not introduce microservices.
- Keep existing Blade admin unchanged unless a shared service extraction requires an internal compatibility-preserving change.
- Keep existing Blade storefront routes operational during migration.
- Add new storefront APIs under `/api/v1`.
- Keep FlowTrack integration APIs separate from storefront APIs.
- Keep Stripe/payment authority, webhooks, verification, and reconciliation in Laravel.
- Do not install or migrate Vue in this milestone.
- Do not modify existing FlowTrack route names, paths, middleware, payloads, or controllers.
- Do not return rendered Blade HTML from new storefront API endpoints.
- Do not return raw Eloquent models from new storefront API endpoints.
- All new API responses include a request/correlation ID.

---

## File Structure for Milestone A

**Create**
- `app/Http/Middleware/ApiRequestId.php` — generate/propagate a safe request ID and add it to logs/responses.
- `app/Support/Api/RequestId.php` — one place to retrieve the current request ID from request attributes.
- `app/Http/Requests/Api/V1/ApiFormRequest.php` — API-specific Form Request convention for future writes.
- `app/Http/Resources/Api/V1/ApiResource.php` — base Resource that appends `request_id`.
- `app/Http/Resources/Api/V1/HealthResource.php` — health contract.
- `app/Http/Resources/Api/V1/Storefront/StorefrontBootstrapResource.php` — bootstrap contract.
- `app/Http/Resources/Api/V1/Storefront/HomepageResource.php` — homepage contract and navigation normalization.
- `app/Http/Controllers/Api/V1/HealthController.php` — thin health endpoint.
- `app/Http/Controllers/Api/V1/Storefront/StorefrontBootstrapController.php` — thin bootstrap endpoint.
- `app/Http/Controllers/Api/V1/Storefront/HomeController.php` — thin homepage endpoint.
- `app/Services/Storefront/StorefrontBootstrapService.php` — aggregate safe global storefront state.
- `tests/Feature/Api/V1/ApiFoundationTest.php` — route/error/request-ID contracts.
- `tests/Feature/Api/V1/Storefront/StorefrontBootstrapApiTest.php` — guest/auth bootstrap contract.
- `tests/Feature/Api/V1/Storefront/HomepageApiTest.php` — homepage Resource contract and no-HTML guarantee.
- `docs/backend/api-v1-milestone-a.md` — endpoint and contract notes for future Vue work.

**Modify**
- `routes/api.php` — append `/api/v1` group without touching the existing FlowTrack group.
- `bootstrap/app.php` — register JSON exception rendering for `/api/v1/*` only; keep existing API JSON preference intact.
- `app/Providers/AppServiceProvider.php` — add named public API limiter; no existing limiter changes.

---

### Task 1: Lock the API foundation with failing characterization tests

**Files:**
- Create: `tests/Feature/Api/V1/ApiFoundationTest.php`
- Test: `tests/Feature/Api/V1/ApiFoundationTest.php`

**Interfaces:**
- Consumes: existing Laravel application bootstrapping and `routes/api.php`.
- Produces: contract expectations for `GET /api/v1/health`, request IDs, API-only 404/405/422 JSON errors, and unchanged FlowTrack route availability.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Api\V1;

use App\Http\Middleware\ApiRequestId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    public function test_health_endpoint_uses_versioned_contract_and_request_id(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure(['data' => ['status', 'api_version', 'timestamp'], 'request_id']);
    }

    public function test_valid_incoming_request_id_is_preserved(): void
    {
        $response = $this->withHeader('X-Request-ID', 'np-test-123')->getJson('/api/v1/health');

        $response->assertHeader('X-Request-ID', 'np-test-123')
            ->assertJsonPath('request_id', 'np-test-123');
    }

    public function test_v1_not_found_errors_use_the_common_contract(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message', 'request_id']);
    }

    public function test_v1_method_not_allowed_errors_use_the_common_contract(): void
    {
        $this->postJson('/api/v1/health')
            ->assertStatus(405)
            ->assertJsonStructure(['message', 'request_id']);
    }

    public function test_v1_validation_errors_use_the_common_contract(): void
    {
        Route::middleware(['api', ApiRequestId::class])
            ->post('/api/v1/testing/validation', function (Request $request) {
                $request->validate(['name' => ['required', 'string']]);
                return response()->json(['ok' => true]);
            });

        $this->postJson('/api/v1/testing/validation', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['message', 'errors' => ['name'], 'request_id']);
    }

    public function test_existing_flowtrack_routes_are_not_moved_under_v1(): void
    {
        $this->assertTrue(Route::has('api.flowtrack.order-items.artwork'));
        $this->assertTrue(Route::has('api.flowtrack.bulk-quotes.attachment'));
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails before implementation**

Run:
```bash
php artisan test tests/Feature/Api/V1/ApiFoundationTest.php
```
Expected before code exists: failures for missing `/api/v1/health`, missing request ID middleware, and default exception shapes.

- [ ] **Step 3: Record any environment-only blocker without changing application behavior**

If `vendor/` is absent, install locked PHP dependencies with:
```bash
composer install --no-interaction --prefer-dist
```
Do not modify `composer.json` or upgrade packages.

---

### Task 2: Implement request tracing, health endpoint, throttling, and API-only exception contracts

**Files:**
- Create: `app/Support/Api/RequestId.php`
- Create: `app/Http/Middleware/ApiRequestId.php`
- Create: `app/Http/Resources/Api/V1/ApiResource.php`
- Create: `app/Http/Resources/Api/V1/HealthResource.php`
- Create: `app/Http/Controllers/Api/V1/HealthController.php`
- Modify: `routes/api.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Api/V1/ApiFoundationTest.php`

**Interfaces:**
- Produces: `RequestId::from(Request): string`, `ApiRequestId` middleware, `GET /api/v1/health`, named limiter `storefront-api-public`.
- Produces API error shapes: `{message, errors?, request_id}` for `/api/v1/*` only.

- [ ] **Step 1: Implement request ID support**

```php
namespace App\Support\Api;

use Illuminate\Http\Request;

final class RequestId
{
    public const ATTRIBUTE = 'api_request_id';
    public const HEADER = 'X-Request-ID';

    public static function from(Request $request): string
    {
        return (string) $request->attributes->get(self::ATTRIBUTE, '');
    }
}
```

Middleware requirements:
- Accept incoming `X-Request-ID` only when it matches `/^[A-Za-z0-9._:-]{1,100}$/`.
- Otherwise generate `(string) Str::uuid()`.
- Store it in the request attribute `api_request_id`.
- Call `Log::withContext(['request_id' => $requestId])`.
- Attach the same `X-Request-ID` to the response.

- [ ] **Step 2: Implement the base API Resource and health contract**

`ApiResource::with()` returns:
```php
return ['request_id' => RequestId::from($request)];
```

`HealthResource::toArray()` returns:
```php
return [
    'status' => 'ok',
    'api_version' => 'v1',
    'timestamp' => now()->toIso8601String(),
];
```

- [ ] **Step 3: Add versioned routes without altering FlowTrack routes**

Append beneath the existing FlowTrack group:
```php
Route::prefix('v1')
    ->middleware([ApiRequestId::class, 'throttle:storefront-api-public'])
    ->group(function (): void {
        Route::get('/health', HealthController::class)->name('api.v1.health');
    });
```

- [ ] **Step 4: Add the public API limiter**

In `AppServiceProvider::boot()`:
```php
RateLimiter::for('storefront-api-public', function (Request $request): Limit {
    return Limit::perMinute(120)->by('storefront-api-public:'.($request->ip() ?: 'unknown'));
});
```

Do not change existing named limiters.

- [ ] **Step 5: Add `/api/v1/*` exception renderers**

In `bootstrap/app.php`, keep `shouldRenderJsonWhen()` and add render closures scoped by:
```php
if (! $request->is('api/v1/*')) {
    return null;
}
```

Required mappings:
- `ValidationException` -> 422 `{message: "Validation failed.", errors, request_id}`
- `AuthenticationException` -> 401 `{message: "Unauthenticated.", request_id}`
- `AuthorizationException` -> 403 `{message: "Forbidden.", request_id}`
- `ModelNotFoundException|NotFoundHttpException` -> 404 `{message: "Resource not found.", request_id}`
- `MethodNotAllowedHttpException` -> 405 `{message: "Method not allowed.", request_id}`
- other `HttpExceptionInterface` -> its status code with non-sensitive standard message and `request_id`
- other `Throwable` -> 500 `{message: "Server error.", request_id}` when `app.debug=false`; in debug keep a concise API message without SQL/secrets/stack trace in the JSON body.

- [ ] **Step 6: Run foundation tests**

Run:
```bash
php artisan test tests/Feature/Api/V1/ApiFoundationTest.php
```
Expected: PASS.

- [ ] **Step 7: Verify route coexistence**

Run:
```bash
php artisan route:list --path=api
```
Expected: original `/api/integrations/flowtrack/*` routes remain and `/api/v1/health` is additive.

---

### Task 3: Establish API Form Request and Resource conventions

**Files:**
- Create: `app/Http/Requests/Api/V1/ApiFormRequest.php`
- Modify: `tests/Feature/Api/V1/ApiFoundationTest.php`

**Interfaces:**
- Produces: base `ApiFormRequest` for future API mutation requests.

- [ ] **Step 1: Add a test proving API validation exceptions use the common renderer**

Use the test-only validation route in `ApiFoundationTest` and keep its assertions on `message`, `errors`, and `request_id`.

- [ ] **Step 2: Create the API Form Request base**

```php
namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
```

Concrete write requests will extend this class in later phases and override authorization when ownership/permissions are required.

- [ ] **Step 3: Re-run foundation tests**

Run:
```bash
php artisan test tests/Feature/Api/V1/ApiFoundationTest.php
```
Expected: PASS.

---

### Task 4: Add storefront bootstrap service and endpoint without moving header business logic into the controller

**Files:**
- Create: `app/Services/Storefront/StorefrontBootstrapService.php`
- Create: `app/Http/Resources/Api/V1/Storefront/StorefrontBootstrapResource.php`
- Create: `app/Http/Controllers/Api/V1/Storefront/StorefrontBootstrapController.php`
- Create: `tests/Feature/Api/V1/Storefront/StorefrontBootstrapApiTest.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: `CartService::headerSummary(int): array`, `WishlistHeaderService::summary(?User, int): array`, `NavigationService::storefrontMenus(): array`.
- Produces: `StorefrontBootstrapService::get(?User $customer): array`.
- Produces: `GET /api/v1/storefront/bootstrap`.

- [ ] **Step 1: Write failing guest/authenticated API tests**

Tests must mock `StorefrontBootstrapService` so endpoint contract tests do not depend on database fixtures.

Guest expected payload:
```php
[
    'site' => ['name' => 'NextPlay Sportswear', 'tagline' => '...', 'logo' => '/images/logo.png'],
    'customer' => null,
    'cart' => ['quantity' => 0, 'total_items' => 0],
    'wishlist' => ['total_items' => 0],
    'navigation' => [],
]
```

Authenticated expected payload includes only safe identity fields:
```php
'customer' => [
    'id' => 10,
    'name' => 'Customer Name',
    'email' => 'customer@example.com',
    'email_verified' => true,
]
```

Assertions:
- root `data` and `request_id` exist.
- no `password`, `remember_token`, admin role/permission data, FlowTrack data, or payment metadata are present.
- endpoint response contains no `<html` or rendered Blade fragment.

- [ ] **Step 2: Implement `StorefrontBootstrapService`**

```php
public function get(?User $customer): array
{
    $customer = $customer?->isCustomer() ? $customer : null;
    $cart = $this->cart->headerSummary(4);
    $wishlist = $this->wishlist->summary($customer, 4);

    return [
        'site' => [
            'name' => (string) config('storefront.name'),
            'tagline' => (string) config('storefront.tagline'),
            'logo' => (string) config('storefront.logo'),
        ],
        'customer' => $customer ? [
            'id' => (int) $customer->getKey(),
            'name' => (string) $customer->name,
            'email' => (string) $customer->email,
            'email_verified' => $customer->hasVerifiedEmail(),
        ] : null,
        'cart' => [
            'quantity' => (int) ($cart['quantity'] ?? 0),
            'total_items' => (int) ($cart['total_items'] ?? 0),
        ],
        'wishlist' => [
            'total_items' => (int) ($wishlist['total_items'] ?? 0),
        ],
        'navigation' => $this->navigation->storefrontMenus(),
    ];
}
```

The service may return current navigation view models; the Resource owns their public serialization.

- [ ] **Step 3: Implement `StorefrontBootstrapResource`**

Normalize each navigation item to:
```php
[
    'label' => $item->label,
    'type' => $item->link_type,
    'category_slug' => $item->category,
    'url' => $item->resolvedUrl(),
    'target' => $item->target,
    'css_class' => $item->css_class,
    'children' => [...],
]
```

Do not expose internal `route_name` as a required frontend contract.

- [ ] **Step 4: Implement the thin controller**

```php
public function __invoke(Request $request, StorefrontBootstrapService $bootstrap): StorefrontBootstrapResource
{
    $user = $request->user('web');

    return new StorefrontBootstrapResource(
        $bootstrap->get($user instanceof User ? $user : null)
    );
}
```

- [ ] **Step 5: Register the bootstrap route with session access**

Inside the `/api/v1` group:
```php
Route::get('/storefront/bootstrap', StorefrontBootstrapController::class)
    ->middleware('web')
    ->name('api.v1.storefront.bootstrap');
```

This is read-only and allows the future same-origin Vue storefront to receive current customer/cart session state. API authentication hardening remains Phase 7.

- [ ] **Step 6: Run bootstrap tests**

Run:
```bash
php artisan test tests/Feature/Api/V1/Storefront/StorefrontBootstrapApiTest.php
```
Expected: PASS.

---

### Task 5: Add homepage JSON Resource/endpoint while preserving the existing Blade homepage

**Files:**
- Create: `app/Http/Resources/Api/V1/Storefront/HomepageResource.php`
- Create: `app/Http/Controllers/Api/V1/Storefront/HomeController.php`
- Create: `tests/Feature/Api/V1/Storefront/HomepageApiTest.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: existing `HomePageService::getHomePageData(): array` unchanged.
- Produces: `GET /api/v1/home` with a frontend-neutral Resource contract.

- [ ] **Step 1: Write failing API contract tests**

Mock `HomePageService::getHomePageData()` with representative data containing:
- `seo`
- `slides`
- `homeSections`
- `categories`
- `buyerPaths`
- `featuredProducts`
- `latestProducts`
- `latestProductsSignature`
- `bestSellingProducts`
- `bestSellingGearCategories`
- `sports`
- `processSteps`
- `faqs`
- `navigation`
- `storefrontMenus`

Assertions:
- root `data` and `request_id` exist.
- expected homepage sections survive serialization.
- navigation is converted to arrays with public URLs and nested children.
- response body does not contain Blade HTML (`<html`, `<x-`, `item_html`).

- [ ] **Step 2: Implement `HomepageResource`**

Keep existing safe scalar/array homepage data and normalize navigation collections recursively. Return explicit fields rather than `return $this->resource;` so adding a new internal key to `HomePageService` cannot leak into the public API accidentally.

Required top-level `data` fields:
```text
seo
slides
sections
categories
buyer_paths
featured_products
latest_products
latest_products_signature
best_selling_products
best_selling_gear_categories
sports
process_steps
faqs
navigation
menus
```

- [ ] **Step 3: Implement thin controller**

```php
public function __invoke(HomePageService $homePage): HomepageResource
{
    return new HomepageResource($homePage->getHomePageData());
}
```

- [ ] **Step 4: Register route**

```php
Route::get('/home', HomeController::class)->name('api.v1.home');
```

Do not modify `/` or `/homepage/latest-products` legacy routes.

- [ ] **Step 5: Run homepage API tests and existing homepage regressions**

Run:
```bash
php artisan test \
  tests/Feature/Api/V1/Storefront/HomepageApiTest.php \
  tests/Feature/HomepageProductCollectionsTest.php \
  tests/Feature/HomepageSliderTest.php \
  tests/Unit/HomepageSectionOrderTest.php
```
Expected: PASS.

---

### Task 6: Regression verification and Milestone A documentation

**Files:**
- Create: `docs/backend/api-v1-milestone-a.md`
- Modify only test/code files above if verification exposes a Milestone A regression.

**Interfaces:**
- Produces: documented API contract and verification evidence.

- [ ] **Step 1: Document the implemented endpoints**

Document:
```text
GET /api/v1/health
GET /api/v1/storefront/bootstrap
GET /api/v1/home
```

Include:
- standard request ID behavior.
- success envelope.
- error envelope.
- bootstrap session note.
- confirmation that `/api/integrations/flowtrack/*` remains separate.
- confirmation that legacy homepage remains the production Blade route.

- [ ] **Step 2: Run focused security/integration regressions**

Run:
```bash
php artisan test \
  tests/Feature/AuthenticationSeparationTest.php \
  tests/Feature/Admin/FlowTrackOrderSyncAdminTest.php
```
Expected: PASS.

- [ ] **Step 3: Run the full suite**

Run:
```bash
php artisan test
```
Expected: zero unexplained failures. If pre-existing unrelated failures exist, record exact failing tests and do not hide them.

- [ ] **Step 4: Verify routes and static syntax**

Run:
```bash
php artisan route:list --path=api
php -l routes/api.php
php -l bootstrap/app.php
find app/Http/Controllers/Api/V1 app/Http/Resources/Api/V1 app/Http/Requests/Api/V1 app/Http/Middleware app/Services/Storefront -name '*.php' -print0 | xargs -0 -n1 php -l
```
Expected: no duplicate route names, no syntax errors.

- [ ] **Step 5: Confirm Milestone A exit criteria**

The milestone is complete only when:
- `/api/v1/health`, `/api/v1/storefront/bootstrap`, and `/api/v1/home` work.
- all three return request IDs.
- `/api/v1/*` errors use the common JSON contract.
- no API response returns Blade HTML.
- no existing Blade route is changed.
- admin/Stripe/FlowTrack routes are unchanged.
- focused and full regression suites are green or any pre-existing environmental blocker is explicitly reported.
