# NextPlay Phase 7-8 Auth and Checkout API Design

## Goal
Add first-party customer session authentication and checkout APIs for the future Vue storefront while keeping Laravel authoritative, preserving Blade/admin/payment/FlowTrack behavior, and reusing existing services.

## Phase 7: Authentication

### Architecture
- Keep existing `web` customer and `admin` session guards. Do not add JWT or Sanctum in this phase.
- API auth routes run through Laravel `web` middleware so they use the same encrypted session cookie and CSRF protections as Blade.
- Customer-only API routes use `auth:web`, `customer`, `not.admin`, and the existing `EnforceCustomerSessionVersion` web middleware behavior.
- Existing registration/password-reset/email-verification services remain authoritative.
- API responses expose safe customer identity only; never expose session IDs, remember tokens, password hashes, CSRF secrets, bearer tokens, `auth_session_version`, or admin authorization details.

### API surface
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `GET /api/v1/auth/verification/status`
- `POST /api/v1/auth/verification/resend`

### Behavior
- Registration reuses `CustomerRegistrationService`, logs the new customer into the web guard, clears the admin guard, regenerates the session, and dispatches the existing verification notification path.
- Login only authenticates active `role=customer` accounts, preserves suspended-account behavior, clears the admin guard, regenerates the session, and updates `last_login_at`.
- Forgot-password responses remain generic for unknown/admin/inactive accounts.
- Reset uses `CustomerPasswordResetService`; security-version invalidation remains unchanged.
- Verification status is read-only JSON. Resend reuses the existing notification mechanism and rate limiter.
- Existing Blade auth routes remain untouched.

## Phase 8: Checkout

### Architecture
- `CheckoutService` remains authoritative for state sequencing, address persistence, per-product shipping, rural/remote surcharge, payment-method selection, totals, and review state.
- Add a neutral read model `stateData(?User $user)` for API/Blade coexistence; `pageData()` may delegate to it while retaining legacy keys.
- API controllers never duplicate checkout formulas and never return Blade HTML or route names as required frontend state.
- No global shipping-method selection endpoint is introduced; configured product shipping/production selections continue to flow from cart into checkout.
- Order placement/payment initiation remains outside Phase 8.

### API surface
- `GET /api/v1/checkout`
- `PUT /api/v1/checkout/information`
- `PUT /api/v1/checkout/shipping-address`
- `PUT /api/v1/checkout/billing-address`
- `PUT /api/v1/checkout/payment-method`
- `GET /api/v1/checkout/review`
- `PUT /api/v1/checkout/review`

### Checkout state contract
Return neutral data containing:
- cart summary
- checkout state
- ordered steps with completion booleans
- first incomplete step
- current capability flags such as `can_review`
- saved contact
- saved shipping/billing addresses
- safe saved payment-method display data
- available payment options
- authoritative checkout summary including remote surcharge
- order idempotency key for future place-order phase

### Sequencing and errors
- Empty cart returns `409` with code `checkout_cart_empty`.
- Attempting a later mutation before prerequisites returns `409` with code `checkout_step_incomplete`, including `required_step` and `requested_step`.
- Validation remains `422` under the existing API error contract.
- Checkout mutations use the existing `checkout-step` throttle.
- Address/payment ownership remains enforced using customer-scoped validation and service-level checks.
- No raw card number, CVV, gateway secret, or raw provider token is accepted or returned.

## Safety and rollback
- Legacy Blade auth and checkout controllers/routes remain active.
- Admin guard behavior, Stripe webhooks/payment return, FlowTrack integration, catalog/cart/wishlist APIs remain unchanged.
- No Vue code is added.
- New API routes can be removed independently without changing the existing Blade production flows.

## Verification gates
Phase 7 requires API auth/session tests plus existing `AuthenticationSeparationTest`, `CustomerEmailVerificationTest`, `CustomerPasswordResetTest`, and `CustomerSuspensionTest` green.
Phase 8 requires sequencing, empty-cart, address ownership, surcharge parity, totals parity, safe-payment contract, and legacy checkout regression coverage, followed by the full PHPUnit suite.
