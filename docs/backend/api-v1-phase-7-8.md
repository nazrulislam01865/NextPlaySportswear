# NextPlay API v1 — Phase 7 and Phase 8

## Phase 7: first-party customer session authentication

The Vue storefront is expected to run on the same Laravel storefront origin. Phase 7 therefore uses Laravel's existing encrypted `web` session cookie and CSRF middleware instead of JWT or Sanctum. No authentication token is returned for browser localStorage.

### Routes

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `GET /api/v1/auth/verification/status`
- `POST /api/v1/auth/verification/resend`

All auth routes are inside the Laravel `web` middleware group. Browser clients must send the normal Laravel session cookie and CSRF token/header on state-changing requests. For HTTPS production deployments set `SESSION_SECURE_COOKIE=true`; the application also defaults secure session cookies on when `APP_ENV=production`. `SESSION_HTTP_ONLY` defaults to true and `SESSION_SAME_SITE` defaults to `lax`.

Customer-only routes use `auth:web` plus the `customer` role middleware. The admin guard remains separate. Successful customer login/registration clears the admin guard exactly as the legacy storefront flow does.

Password recovery deliberately returns the same forgot-password success message for active, unknown, admin, and inactive email addresses. Password reset still delegates to `CustomerPasswordResetService`, including security-version invalidation.

Email verification keeps the existing signed verification-link route. API endpoints expose current verification status and resend behavior; they do not create a second verification authority.

## Phase 8: checkout state API

### Routes

- `GET /api/v1/checkout`
- `PUT /api/v1/checkout/information`
- `PUT /api/v1/checkout/shipping-address`
- `PUT /api/v1/checkout/billing-address`
- `PUT /api/v1/checkout/payment-method`
- `GET /api/v1/checkout/review`
- `PUT /api/v1/checkout/review`

All checkout routes require an authenticated, active, verified customer session. Mutations use the existing `checkout-step` rate limiter.

There is intentionally no global checkout shipping-method endpoint. Product shipping/production selections continue to come from product configuration/cart data and are priced by Laravel.

### Machine-readable conflicts

Empty cart:

```json
{
  "message": "Your cart is empty.",
  "code": "checkout_cart_empty",
  "data": {},
  "request_id": "..."
}
```

Skipped prerequisite:

```json
{
  "message": "Please complete your contact information before continuing checkout.",
  "code": "checkout_step_incomplete",
  "data": {
    "required_step": "information",
    "requested_step": "shipping"
  },
  "request_id": "..."
}
```

### Checkout read model

`CheckoutService::stateData()` is frontend-neutral and includes:

- authoritative cart summary
- neutral checkout state
- step list with completion flags
- `current_step`
- `first_incomplete_step`
- `can_review` / `can_place_order`
- saved contact
- customer-owned shipping/billing address choices
- masked saved payment-method choices when available
- available payment options
- authoritative totals, per-product shipping, production lines, and remote-area surcharge
- order idempotency key for the later place-order phase

Legacy `pageData()` delegates to the same state calculation and then maps back to existing Blade keys, keeping the legacy checkout active as rollback protection.

### Address and payment safety

Saved address IDs are constrained to the authenticated customer by the shared validation contract. New shipping/billing addresses can still be saved to the account through the existing `CheckoutService` rules. API shipping/billing writes support `Idempotency-Key` replay protection to prevent duplicate persisted addresses when clients retry the same mutation.

Payment method selection accepts method identifiers only. Raw `card_number`, `cvv`, `cvc`, `expiry`, `payment_token`, and `gateway_token` fields are explicitly prohibited. Phase 8 does not place orders or initiate payment provider handoff.

## Rollback

Removing the new `/api/v1/auth/*` and `/api/v1/checkout*` routes does not change the legacy Blade authentication/checkout routes. Stripe webhook/payment-return code, FlowTrack integration, admin Blade routes, and existing catalog/cart/wishlist APIs are unchanged.

## Verification commands

```bash
php artisan optimize:clear
php artisan test tests/Feature/Api/V1/Auth
php artisan test tests/Feature/Api/V1/Checkout
php artisan test tests/Feature/AuthenticationSeparationTest.php
php artisan test tests/Feature/CustomerEmailVerificationTest.php
php artisan test tests/Feature/CustomerPasswordResetTest.php
php artisan test tests/Feature/CustomerSuspensionTest.php
php artisan test tests/Feature/RemoteAreaSurchargeServiceTest.php
php artisan test tests/Feature/Api/V1/Cart
php artisan test tests/Feature/Api/V1/Wishlist
php artisan test
php artisan route:list --path=api/v1
```
