# NextPlay Backend Clean Architecture Design

Date: 2026-09-16
Status: Approved architecture direction — Approach B (Laravel-native Clean Architecture)
Scope: Backend only

## 1. Purpose

Refactor the existing NextPlay Laravel backend incrementally so it has clearer separation of concerns, lower coupling, stronger testability, and a stable API-first boundary for the future Vue 3 storefront, while preserving current behavior.

This is not a rewrite. The existing Laravel application remains the single project and the single source of truth for business rules and persistence.

## 2. Non-negotiable project constraints

- Keep one Laravel project.
- Keep Laravel 13 / PHP 8.3+ / MySQL.
- Do not create a separate backend or frontend Laravel project.
- Do not introduce microservices.
- Keep the existing Blade admin unchanged unless a shared service extraction requires an internal compatibility-preserving change.
- Keep existing Blade storefront routes operational during migration.
- Add new storefront APIs under `/api/v1`.
- Keep FlowTrack integration APIs separate from storefront APIs.
- Keep Stripe/payment authority, webhooks, verification, and reconciliation in Laravel.
- Vue must never become authoritative for pricing, discounts, shipping, surcharge, checkout, payments, or orders.
- Do not remove legacy code until replacement behavior is verified.

## 3. Architecture decision

Use Laravel-native Clean Architecture rather than a textbook framework-independent rewrite.

Primary dependency flow:

```text
Client / future Vue
        |
        v
/api/v1 routes
        |
        v
Controller
        |
        v
Form Request
        |
        v
Application/Domain Service
        |
        +--> Eloquent Models / DB
        +--> Supporting Services
        +--> External Contracts
        |
        v
API Resource
        |
        v
JSON
```

Legacy Blade controllers may temporarily call the same services while API controllers are introduced.

## 4. Target backend structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Api/V1/
│   │   │   ├── Storefront/
│   │   │   ├── Catalog/
│   │   │   ├── Auth/
│   │   │   ├── Cart/
│   │   │   ├── Wishlist/
│   │   │   ├── Checkout/
│   │   │   ├── Account/
│   │   │   ├── Orders/
│   │   │   ├── Returns/
│   │   │   └── BulkQuote/
│   │   ├── Storefront/          # legacy during migration
│   │   ├── Payments/
│   │   └── Webhooks/
│   ├── Requests/
│   │   ├── Api/V1/
│   │   ├── Admin/
│   │   └── Storefront/
│   ├── Resources/
│   │   └── Api/V1/
│   └── Middleware/
├── Data/
│   ├── Catalog/
│   ├── Cart/
│   ├── Checkout/
│   ├── Orders/
│   └── Shipping/
├── Contracts/
│   ├── Payments/
│   ├── Integrations/
│   ├── Storage/
│   └── EmailService.php
├── Models/
├── Services/
│   ├── Catalog/
│   ├── Storefront/
│   ├── Cart/
│   ├── Wishlist/
│   ├── Checkout/
│   ├── Shipping/
│   ├── Discounts/
│   ├── Order/
│   ├── Payments/
│   ├── Auth/
│   ├── BulkQuote/
│   └── Integrations/FlowTrack/
├── Payments/
├── Jobs/
├── Events/
├── Listeners/
├── Policies/
├── Exceptions/
└── Support/
```

## 5. Layer responsibilities

### 5.1 HTTP controllers

Responsibilities:
- Receive HTTP requests.
- Call validated request objects.
- Delegate to a service/use-case class.
- Return API Resources or proper HTTP responses.

Controllers must not contain:
- Eloquent query chains except trivial route-model binding concerns.
- Pricing calculations.
- Shipping calculations.
- Coupon rules.
- Checkout sequencing.
- Payment state decisions.
- FlowTrack payload construction.

API controllers should remain intentionally thin.

### 5.2 Form Requests

Responsibilities:
- Authorization for the operation where appropriate.
- Input validation.
- Safe input normalization.

Services must not depend on `Illuminate\Http\Request`.

### 5.3 API Resources

Responsibilities:
- Own the public JSON contract.
- Prevent raw Eloquent/internal-field leakage.
- Normalize money, dates, booleans, media, pagination, and nested representations.

Resources must not contain business rules.

### 5.4 Services

Services own business/application behavior.

Laravel remains authoritative for:
- Catalog rules.
- Product configuration.
- Quantity pricing.
- Discounts/coupons.
- Cart calculation.
- Shipping methods.
- Remote-area surcharge.
- Checkout sequencing.
- Order placement.
- Payment orchestration.
- Account workflows.
- Returns/refunds/exchanges.
- Bulk quotes.
- FlowTrack synchronization orchestration.

Services should accept arrays, models, DTOs, scalars, and value objects rather than HTTP request/response classes.

### 5.5 Data/DTO classes

DTOs are introduced only for complex mutation contracts where they materially improve clarity/testability.

Examples:
- `CheckoutInformationData`
- `AddressData`
- `PlaceOrderData`
- `ProductConfigurationData`

Do not create DTOs for every trivial value.

### 5.6 Models

Keep Eloquent as the persistence model.

Do not introduce repository interfaces for every model. Repositories/contracts should be added only when a meaningful replaceable boundary exists.

### 5.7 External contracts

Interfaces are appropriate for replaceable/external systems such as:
- Payment gateways.
- FlowTrack client transport.
- Email transport.
- Storage abstractions when necessary.

## 6. Large-service decomposition strategy

The current project has broad services that should be decomposed incrementally without breaking public behavior.

### 6.1 ProductCatalogService

Target collaborators:
- `ProductQueryService`
- `ProductDetailService`
- `ProductConfigurationService`
- `ProductPricingService`
- existing `ProductCatalogCacheService`

`ProductCatalogService` remains as a compatibility facade initially so legacy Blade controllers continue working.

### 6.2 CartService

Target collaborators:
- `CartPersistenceService`
- `CartConfigurationService`
- `CartPricingService`
- `CouponService`
- `ProductPricingService`

Existing public methods remain available until callers are migrated.

### 6.3 CheckoutService

Target collaborators:
- `CheckoutStateService`
- `CheckoutAddressService`
- `CheckoutPricingService`
- `ShippingMethodService`
- `PaymentMethodService`
- `CheckoutOrderService`

`CheckoutService` remains a compatibility facade during migration.

## 7. Controllers requiring business-logic extraction

Priority candidates identified during review include:
- `ProductWishlistController` -> `WishlistService`
- `ProductActivityController` -> `ProductActivityService`
- account address controller logic -> `CustomerAddressService`
- account order-center logic -> `CustomerOrderService`
- newsletter logic -> `NewsletterSubscriptionService`
- contact logic -> `ContactMessageService`

Extraction will be done only when protected by characterization/regression tests.

## 8. API contract rules

All new storefront APIs live under `/api/v1`.

Success envelope:

```json
{
  "data": {},
  "meta": {},
  "request_id": "..."
}
```

Validation errors:

```json
{
  "message": "Validation failed.",
  "errors": {},
  "request_id": "..."
}
```

General rules:
- ISO 8601 dates with timezone.
- Explicit money representation.
- No local filesystem paths.
- No secrets/integration credentials.
- No raw Eloquent responses.
- No Blade HTML from storefront API endpoints.
- Stable field names once consumed by Vue.

## 9. Migration execution pattern

For every backend domain:

1. Characterize existing behavior.
2. Add regression tests around critical behavior.
3. Identify controller/service coupling.
4. Extract business logic into focused service classes.
5. Keep legacy controller behavior calling the service.
6. Add API-specific Form Request.
7. Add API controller.
8. Add API Resource.
9. Add API/contract tests.
10. Run existing regression tests.
11. Verify admin, Blade, Stripe, and FlowTrack behavior.
12. Commit as an isolated rollback-safe change.

## 10. Migration phases

### Phase 0 — Safety baseline
- Route inventory.
- Service ownership map.
- Run/test baseline.
- Add missing characterization tests for critical behavior.

### Phase 1 — API foundation
- `/api/v1` namespace.
- JSON API error handling.
- Request ID/correlation middleware.
- `GET /api/v1/health`.

### Phase 2 — Contract layer
- API Resources.
- API Form Requests.
- JSON conventions.
- Contract tests.

### Phase 3 — Storefront bootstrap + homepage
- `GET /api/v1/storefront/bootstrap`
- `GET /api/v1/home`
- preserve existing homepage Blade flow.

### Phase 4 — Catalog/search
- Categories.
- Product lists/details.
- Filters/search suggestions.

### Phase 5 — Product configuration + authoritative price preview
- Configuration schema.
- Validation.
- server-calculated price preview.

### Phase 6 — Cart + wishlist
- Data-first APIs.
- authoritative totals.
- preserve session/customer behavior.

### Phase 7 — Customer authentication
- first-party session/cookie API flow.
- preserve admin/customer auth separation.

### Phase 8 — Checkout/shipping
- expose checkout state machine as JSON.
- keep totals/shipping/surcharge authoritative in Laravel.

### Phase 9 — Orders/payments
- idempotent order placement.
- backend payment orchestration.
- customer-safe status resources.

### Phase 10 — Account/after-sales
- account, addresses, orders, returns, downloads.

### Phase 11 — Bulk quote/content utilities
- preserve internal FlowTrack inquiry sync.

### Phase 12 — Security/performance/observability
- throttling.
- policies.
- caching boundaries.
- request tracing.
- N+1/query review.

### Phase 13 — Backend readiness gate
- verify API coverage and legacy coexistence before Vue cutover.

## 11. First implementation milestone

Milestone A is the first implementation target.

Deliverables:
- Existing regression baseline recorded.
- `/api/v1` route foundation.
- API request ID middleware.
- API JSON exception/error contract.
- `GET /api/v1/health`.
- initial Resource/Request conventions.
- `StorefrontBootstrapService`.
- `GET /api/v1/storefront/bootstrap`.
- `GET /api/v1/home` using existing homepage/domain services.
- no Blade storefront route changes.
- no admin behavior changes.
- no Stripe route/behavior changes.
- no FlowTrack route/payload changes.

## 12. Testing strategy

### Unit tests
Protect pure calculation/business behavior.

Areas:
- pricing.
- quantity pricing.
- remote surcharge.
- configuration validation.
- checkout state transitions.

### Feature/API tests
Protect:
- routes.
- validation.
- auth.
- serialization.
- controller/service orchestration.

### Regression tests
Protect legacy:
- homepage.
- catalog.
- cart.
- checkout.
- authentication separation.
- admin.
- order management.

### Integration tests
Protect:
- Stripe handoff/webhooks.
- FlowTrack order synchronization.
- FlowTrack bulk quote synchronization.

### Security tests
Protect:
- customer ownership.
- admin/customer guard separation.
- throttle behavior.
- sensitive-field exposure.

### Performance tests/checks
Measure representative query count/latency for:
- homepage.
- product list.
- product detail.
- cart.
- checkout.

## 13. Regression requirements

The refactor is accepted only if equivalent inputs preserve equivalent behavior for:
- product pricing.
- configuration validation.
- cart totals.
- coupon rules.
- shipping calculations.
- remote-area surcharge.
- checkout prerequisites.
- order snapshots.
- payment handoff.
- Stripe reconciliation.
- FlowTrack order payload/sync.
- FlowTrack bulk quote inquiry sync.

## 14. Explicit non-goals

During the backend refactor do not:
- install/migrate Vue yet.
- create another Laravel app.
- introduce microservices.
- migrate admin to Vue.
- redesign storefront UI.
- replace MySQL.
- replace Stripe.
- replace FlowTrack.
- switch to JWT just because an API exists.
- move pricing/business logic to frontend.
- delete legacy Blade/Alpine code.
- rename/remove existing storefront URLs prematurely.
- combine architectural refactor with unrelated feature changes.

## 15. Exit criteria for backend clean-architecture migration

The backend migration is ready for Vue consumption when:
- `/api/v1` is stable and versioned.
- API controllers are thin.
- write APIs use Form Requests.
- public JSON uses Resources.
- no storefront API returns raw Eloquent or Blade HTML.
- service boundaries own business rules.
- Laravel remains authoritative for all financial/order behavior.
- customer/admin auth separation remains intact.
- ownership is enforced server-side.
- Stripe remains backend-owned.
- FlowTrack behavior remains intact.
- critical regression/integration tests pass.
- legacy Blade storefront can coexist with the API during page-by-page migration.
