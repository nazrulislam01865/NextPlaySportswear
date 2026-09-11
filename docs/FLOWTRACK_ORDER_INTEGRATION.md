# NextPlay -> FlowTrack Order Integration

This integration sends a complete persisted NextPlay order to FlowTrack after NextPlay successfully creates the order.

## Behavior

- NextPlay remains the source of truth for customer/order/item/customization data.
- FlowTrack receives the full order snapshot through `POST /api/integrations/nextplay/orders`.
- The existing NextPlay checkout transaction does not depend on FlowTrack availability.
- If FlowTrack is unavailable, the NextPlay order is kept and the integration error is logged.
- The existing NextPlay checkout idempotency key is also sent as the HTTP `Idempotency-Key`, so retries are safe.
- Local development defaults to immediate synchronous sending.
- Production can switch to a retrying queue worker without changing checkout code.

## Local ports used in the examples

- NextPlay: `http://127.0.0.1:8000`
- FlowTrack: `http://127.0.0.1:8001`

The two applications cannot both use port 8000 at the same time.

## 1. Configure FlowTrack locally

Add to the FlowTrack `.env`:

```dotenv
NEXTPLAY_INTEGRATION_TOKEN=PASTE_THE_SHARED_RANDOM_TOKEN_HERE
NEXTPLAY_RECEIVER_USER_ID=YOUR_ACTIVE_FLOWTRACK_USER_ID
NEXTPLAY_RECEIVER_USER_EMAIL=
NEXTPLAY_ALLOWED_ORIGINS=http://127.0.0.1:8000,http://localhost:8000
NEXTPLAY_RATE_LIMIT_PER_MINUTE=60
```

The receiver user must be active and have the FlowTrack `Orders -> Create` permission.

Then run in FlowTrack:

```bash
php artisan optimize:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

## 2. Configure NextPlay locally

Add to the NextPlay `.env`:

```dotenv
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=http://127.0.0.1:8001
FLOWTRACK_INTEGRATION_TOKEN=PASTE_THE_SAME_SHARED_RANDOM_TOKEN_HERE
FLOWTRACK_CONNECT_TIMEOUT=2
FLOWTRACK_REQUEST_TIMEOUT=10
FLOWTRACK_QUEUE_ENABLED=false
```

For local testing keep `FLOWTRACK_QUEUE_ENABLED=false`. A newly created NextPlay order will then be sent immediately, making the result easy to verify.

Then run:

```bash
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000
```

## 3. Verify the connection before placing an order

From the NextPlay project:

```bash
php artisan flowtrack:health
```

A healthy connection reports that FlowTrack is ready and shows its configured receiver user ID.

## 4. Test with an existing NextPlay order

You can send an existing order without going through checkout again:

```bash
php artisan flowtrack:sync-order NP-ORDER-NUMBER
```

A numeric database order ID can also be used:

```bash
php artisan flowtrack:sync-order 123
```

FlowTrack duplicate protection makes repeating the same command safe.

## 5. Test automatic order creation

Place a normal order through NextPlay checkout.

After the order is committed to the NextPlay database, NextPlay sends the full source order to FlowTrack. FlowTrack then creates its operational order and starts its existing workflow.

The FlowTrack call happens after the NextPlay database transaction. A FlowTrack connection failure therefore cannot roll back the NextPlay order.

Check NextPlay `storage/logs/laravel.log` for one of these events:

- `flowtrack.order_sync_succeeded`
- `flowtrack.order_sync_failed`
- `flowtrack.order_sync_queued`

## Production queue mode

After local testing succeeds, production can use queued retries:

```dotenv
FLOWTRACK_QUEUE_ENABLED=true
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
FLOWTRACK_QUEUE_TRIES=5
FLOWTRACK_QUEUE_TIMEOUT=30
FLOWTRACK_QUEUE_BACKOFF=10,60,300,900,1800
```

Run a worker that consumes the integration queue:

```bash
php artisan queue:work --queue=integrations,default
```

The job retries failed HTTP delivery. FlowTrack's idempotent receiver prevents retry attempts from creating duplicate orders.

## CORS note

The automatic NextPlay -> FlowTrack request is Laravel backend-to-backend. Server-to-server HTTP is not blocked by browser CORS. FlowTrack's restricted CORS configuration remains useful for any future browser-based integration/testing, but it is not the authentication mechanism. The shared Bearer token protects the API.

## Schema v2 complete-order payload

NextPlay now sends `schema_version=2` and preserves more than the basic `orders` / `order_items` columns. The payload includes:

- every current `orders` database attribute in `source_order_attributes`;
- every current `order_items` database attribute in each item's `source_item_attributes`;
- normalized SKU/image/size/roster/artwork/configuration/sample/fulfillment details;
- order payments, status history, shipments, change requests, return requests/attachments, refunds, credit notes and downloads in `source_related_data`.

Product images are normalized to an absolute NextPlay URL for FlowTrack. Artwork upload metadata preserves the private source storage path, original filename, MIME type and file size.

After upgrading both applications, an existing local order can be resent safely:

```bash
php artisan flowtrack:sync-order NP-260908-N2WOGJ
```

FlowTrack will refresh the source snapshot/detail tables without creating a duplicate operational order.

## Complete source snapshot contract

Payload schema v2 sends the complete persisted `orders` and `order_items` attributes plus normalized SKU/image/sizes/roster/artwork/configuration data. It also snapshots the order-management records currently available for payments, status history, shipments, changes, returns, refunds, credit notes and downloads.

FlowTrack keeps the original nested source payload in addition to normalized query tables. Re-running `php artisan flowtrack:sync-order <ORDER_NUMBER>` refreshes that source snapshot idempotently without creating a duplicate FlowTrack order.

## Complete ordered-product snapshot (schema v3, 2026-09-08)

Every newly created `order_items` row now freezes the complete product definition that existed when checkout completed. This is intentionally stored in NextPlay before any FlowTrack request is made.

New `order_items` columns:

- `product_snapshot_schema_version`
- `product_snapshot` (JSON)
- `resolved_customization` (JSON)
- `product_snapshot_captured_at`

`product_snapshot` contains the raw `products` row, complete storefront product representation, related categories/attributes/images/option groups/option values/size groups/price tiers/fabric price tables/artwork methods/production methods/shipping methods/FAQs, the submitted customization, and the resolved selected configuration. The resolved configuration stores selected option labels/definitions, sizes and definitions, roster fields/rows, artwork metadata/settings, production speed and shipping method.

`flowtrack:sync-order` backfills these columns for an older order if no snapshot exists. Because an old order never stored the historical full product definition, that one-time backfill uses the current product catalog and is marked `flowtrack_sync_backfill_current_catalog`. New orders are exact checkout-time snapshots.

### Private artwork transfer

NextPlay exposes a Bearer-token protected, read-only server-to-server endpoint for each artwork attached to an ordered item:

`GET /api/integrations/flowtrack/order-items/{orderItem}/artworks/{index}`

The path is resolved from the persisted order item. Callers cannot supply an arbitrary storage path. FlowTrack uses the same shared integration token, verifies the SHA-256 checksum, and stores its own private copy.

## Registered customer information sent with each order (schema v3 additive contract)

The order payload now includes a top-level `customer_account` object. This is an additive, backward-compatible extension of schema v3 so existing FlowTrack receivers that already accept v3 orders do not need a schema-version change merely to ignore the new field.

For a registered customer it sends only the order-management-safe profile fields:

- `is_registered`
- `source_user_id` (the NextPlay customer ID)
- `name`
- `email`
- `phone`
- `company_name`
- `preferred_sport`
- `order_contact.name`
- `order_contact.email`
- `order_contact.phone`

`order_contact` is intentionally kept separate from the current account profile because the contact used when the order was placed is part of the immutable order record.

NextPlay does **not** send customer passwords, password hashes, remember tokens, role/admin fields, session state, reset tokens, authentication versions, or other security-sensitive user attributes.

The existing top-level `shipping_address`, `billing_address`, `information`, `customer_name`, `customer_email`, and `customer_phone` fields remain unchanged.

### CORS / server-to-server note

Order synchronization uses Laravel's HTTP client from the NextPlay backend to the FlowTrack backend. Browser CORS does not gate this request and should not be used as authentication. Keep the shared Bearer token and HTTPS as the API security boundary. CORS configuration is only relevant if a browser is ever allowed to call an integration endpoint directly.
