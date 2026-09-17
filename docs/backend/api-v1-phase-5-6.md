# NextPlay Storefront API v1 — Phase 5 + Phase 6

Date: 2026-09-16

## Scope

This milestone adds product configuration/authoritative price preview plus cart and wishlist APIs. Laravel remains authoritative for all configuration validation, quantity rules, pricing, discounts, shipping and persisted cart state. Existing Blade storefront/admin/payment/FlowTrack behavior remains available during coexistence.

## Phase 5 endpoints

### GET `/api/v1/products/{slug}/configuration`

Returns a normalized configuration contract containing:

- product identity/profile/currency
- minimum/maximum/inventory constraints
- option groups and allowed values
- master-data type identifiers (`master_type`) for profile-specific controls
- generic size groups only for profiles that support them
- roster fields/settings
- artwork upload rules and artwork methods
- sample settings
- production and shipping methods
- quantity/base/fabric price tables
- the accepted configuration request shape

Dedicated-size profiles (including Training Vest and other profiles listed by `ProductSizing::DEDICATED_CUSTOMIZATION_SIZE_PROFILES`) do not expose the generic Size Options flow at the same time.

### POST `/api/v1/products/{slug}/price-preview`

Example JSON body:

```json
{
  "quantity": 12,
  "configuration": {
    "selections": {
      "fabric": "mesh"
    },
    "multi_selections": {
      "imprint": ["front"]
    },
    "inputs": {},
    "quantities": {
      "adult:m": 12
    },
    "production_speed": "standard",
    "shipping_method": "air",
    "roster_enabled": false,
    "roster": [],
    "sample_requested": false
  }
}
```

The route slug is authoritative. Browser-supplied fields such as `unit_price`, `line_total`, `customization_total`, `shipping_unit_price` or `total` are never used as pricing inputs.

Preview calls `CartService::previewItem()`, which uses the same sanitization, option validation, quantity validation, fabric price tables, production pricing, customization pricing and product-shipping calculation used by final cart insertion. Required artwork presence is the only final-cart validation intentionally deferred during a price preview because preview does not upload files.

Invalid option IDs, duplicated checkbox values, unavailable production/shipping methods, incompatible input types, unknown generic size keys and generic sizes for dedicated-size profiles return HTTP 422.

## Phase 6 cart endpoints

All cart endpoints use the Laravel `web` middleware so guest session carts and authenticated customer carts preserve the existing persistence model.

- `GET /api/v1/cart`
- `POST /api/v1/cart/items`
- `PATCH /api/v1/cart/items/{item}`
- `PATCH /api/v1/cart/items/{item}/options`
- `DELETE /api/v1/cart/items/{item}`
- `POST /api/v1/cart/coupon`
- `DELETE /api/v1/cart/coupon`

Every mutation returns the complete server-recalculated cart. API responses never include the legacy `item_html` Blade fragment.

### Idempotency

`POST /api/v1/cart/items` and cart option replacement accept an optional `Idempotency-Key` header.

- allowed format: 8–128 characters, letters/numbers plus `. _ : -`
- result is scoped to the current customer/session and operation
- same key + same payload replays the prior cart result without a second mutation
- same key + different payload returns HTTP 409
- replay window: 15 minutes
- replay occurs before artwork storage, preventing duplicate uploads on retried add/update requests

### Artwork

Artwork files remain private on the Laravel local disk and follow the existing product upload limits/types. API cart items do not expose storage paths. They return an opaque `retention_token` plus the existing authorized artwork-view URL. Option-edit requests may submit `retained_artwork_tokens[]` to keep existing files.

## Phase 6 wishlist endpoints

- `GET /api/v1/wishlist`
- `POST /api/v1/wishlist/items` body: `{ "product_id": 123 }`
- `DELETE /api/v1/wishlist/items/{product}`

Authenticated customers continue using the `product_wishlists` table and customer ownership. API guests use session-scoped product IDs so Vue can provide data-first add/remove behavior before the Phase 7 authentication migration. Existing Blade guest local-storage behavior is not removed.

Wishlist favorites counters are updated transactionally only for authenticated database wishlist records.

## Rollback

The new `/api/v1` routes can be removed independently. Existing Blade `ProductController`, `CartController`, `ProductWishlistController`, admin routes, payment routes and FlowTrack integration routes remain operational and continue using the same core services.

## Verification commands

```bash
php artisan optimize:clear

php artisan test tests/Feature/Api/V1/Catalog/ProductConfigurationApiTest.php
php artisan test tests/Feature/Api/V1/Catalog/ProductPricePreviewTest.php
php artisan test tests/Feature/Api/V1/Cart
php artisan test tests/Feature/Api/V1/Wishlist

php artisan test tests/Feature/ProductConfigurationBackendTest.php
php artisan test tests/Feature/StorefrontCatalogFiltersTest.php
php artisan test tests/Feature/StorefrontCategoryTest.php
php artisan test tests/Feature/AuthenticationSeparationTest.php
php artisan test tests/Feature/OrderManagementTest.php

php artisan route:list --path=api/v1
php artisan test
```
