# NextPlay Phase 7-8 Auth and Checkout API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add secure customer session authentication APIs and a JSON checkout state machine without changing legacy Blade, admin, payment, or FlowTrack behavior.

**Architecture:** Use the existing Laravel `web` session guard and CSRF/session middleware for first-party storefront APIs. Reuse `CustomerRegistrationService`, `CustomerPasswordResetService`, and `CheckoutService`; add thin API controllers, API Form Requests/Resources, and only the smallest neutral service helpers needed to prevent Blade-shaped data from leaking into the API.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL/SQLite tests, Laravel sessions/CSRF, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-16-nextplay-phase-7-8-auth-checkout-api-design.md`

## Global Constraints
- One Laravel project; no microservices and no separate frontend project.
- Laravel remains authoritative for authentication, checkout, pricing, shipping, surcharges, payments, and database access.
- Keep existing Blade customer auth/checkout and Blade admin fully operational.
- Do not introduce Sanctum/JWT in this phase.
- Do not return Eloquent models directly; APIs use explicit Resources.
- Do not return Blade HTML from APIs.
- Do not change Stripe webhook/payment return or FlowTrack behavior.
- No Vue work in these phases.

---

### Task 1: Shared auth request contracts and safe customer resource

**Files:**
- Create: `app/Http/Requests/Auth/LoginCredentialsRequest.php`
- Create: `app/Http/Requests/Auth/RegisterCustomerRequest.php`
- Create: `app/Http/Requests/Auth/ForgotCustomerPasswordRequest.php`
- Create: `app/Http/Requests/Auth/ResetCustomerPasswordRequest.php`
- Modify: existing Storefront auth requests to extend shared contracts
- Create: `app/Http/Requests/Api/V1/Auth/*`
- Create: `app/Http/Resources/Api/V1/Auth/CustomerResource.php`
- Test: `tests/Feature/Api/V1/Auth/AuthenticationApiTest.php`

**Interfaces:**
- Shared request classes provide identical validation/normalization to Blade and API.
- `CustomerResource` returns safe customer identity and verification state only.

- [ ] Write failing auth API contract tests for safe customer serialization and validation parity.
- [ ] Run focused tests and confirm route/contract failures before implementation.
- [ ] Implement shared request bases and API request subclasses.
- [ ] Implement `CustomerResource`.
- [ ] Run focused tests.

### Task 2: Session registration/login/logout/current-customer API

**Files:**
- Create: `app/Services/Auth/CustomerSessionService.php`
- Create: `app/Http/Controllers/Api/V1/Auth/RegistrationController.php`
- Create: `app/Http/Controllers/Api/V1/Auth/AuthenticatedSessionController.php`
- Create: `app/Http/Controllers/Api/V1/Auth/CurrentCustomerController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Auth/AuthenticationApiTest.php`

**Interfaces:**
- `CustomerSessionService::login(Request $request, array $credentials): User`
- `CustomerSessionService::logout(Request $request): void`
- API auth uses `web` guard and clears `admin` guard on successful customer authentication.

- [ ] Add failing tests for active login, suspended/invalid login, registration session, logout, `/me`, admin-session rejection, and absence of sensitive credentials.
- [ ] Verify failures.
- [ ] Implement service/controllers/routes using `web` middleware and existing session-version behavior.
- [ ] Run focused auth tests.

### Task 3: Password recovery and email-verification API

**Files:**
- Create: `app/Http/Controllers/Api/V1/Auth/PasswordResetController.php`
- Create: `app/Http/Controllers/Api/V1/Auth/EmailVerificationController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Auth/PasswordRecoveryApiTest.php`
- Test: `tests/Feature/Api/V1/Auth/EmailVerificationApiTest.php`

**Interfaces:**
- Forgot-password returns a generic success message regardless of account existence/status.
- Reset delegates to `CustomerPasswordResetService`.
- Verification status/resend requires customer auth; resend uses existing notification path and limiter.

- [ ] Add failing generic-response/reset/verification tests.
- [ ] Verify failures.
- [ ] Implement controllers/routes and reuse existing services/rate limiters.
- [ ] Run focused tests plus existing auth regression tests.

### Task 4: Neutral checkout state read model

**Files:**
- Modify: `app/Services/Checkout/CheckoutService.php`
- Create: `app/Http/Resources/Api/V1/Checkout/CheckoutResource.php`
- Create: `app/Http/Controllers/Api/V1/Checkout/CheckoutController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutStateApiTest.php`

**Interfaces:**
- `CheckoutService::stateData(?User $user = null): array`
- `CheckoutService::firstIncompleteStep(): ?array`
- API read endpoint returns neutral data and `409 checkout_cart_empty` for empty carts.

- [ ] Add failing state/empty-cart tests.
- [ ] Verify failures.
- [ ] Implement neutral read model and API Resource/controller.
- [ ] Keep `pageData()` legacy-compatible by delegating where safe.
- [ ] Run focused tests.

### Task 5: Checkout information/address/payment mutations with prerequisite guard

**Files:**
- Create: shared checkout request contracts under `app/Http/Requests/Checkout/`
- Modify: Storefront checkout requests to extend shared contracts
- Create: API checkout request subclasses under `app/Http/Requests/Api/V1/Checkout/`
- Create controllers under `app/Http/Controllers/Api/V1/Checkout/`
- Create: `app/Services/Checkout/CheckoutApiGuard.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutSequenceApiTest.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutAddressApiTest.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutPaymentMethodApiTest.php`

**Interfaces:**
- Guard returns/throws machine-readable `409` for missing prerequisites.
- Mutations delegate to existing `CheckoutService::store*` methods and return a fresh `CheckoutResource`.

- [ ] Add failing tests for sequence enforcement, ownership, billing=same-as-shipping, and payment ownership/no raw card fields.
- [ ] Verify failures.
- [ ] Implement shared validation contracts, API controllers, guard, routes, and throttling.
- [ ] Run focused tests.

### Task 6: Checkout review, totals parity, remote surcharge parity, security regression

**Files:**
- Create: `app/Http/Controllers/Api/V1/Checkout/CheckoutReviewController.php`
- Create: `app/Http/Requests/Api/V1/Checkout/ReviewConfirmationRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutReviewApiTest.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutTotalsParityTest.php`
- Test: `tests/Feature/Api/V1/Checkout/CheckoutRemoteAreaSurchargeTest.php`

**Interfaces:**
- Review GET requires information/shipping/billing/payment complete.
- Review PUT confirms existing review state only; it does not create an order or initiate payment.

- [ ] Add failing review/totals/surcharge/security tests.
- [ ] Verify failures.
- [ ] Implement review endpoints with existing service calculations.
- [ ] Verify focused Phase 8 tests and legacy regressions.

### Task 7: Documentation and full verification

**Files:**
- Create: `docs/backend/api-v1-phase-7-8.md`

**Interfaces:**
- Document routes, session/CSRF contract, checkout codes, and rollback behavior.

- [ ] Run `php -l` across project PHP files.
- [ ] Run Phase 7 focused tests.
- [ ] Run Phase 8 focused tests.
- [ ] Run existing authentication/password/verification/suspension regressions.
- [ ] Run existing cart/catalog/configuration/remote-area/order regressions.
- [ ] Run full `php artisan test`.
- [ ] Run `php artisan route:list --path=api/v1`.
- [ ] Package the verified project ZIP.
