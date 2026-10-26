# Remote-area flag (NextPlay admin and FlowTracker API contract)

This change adds a destination-classification flag only. **Shipping prices, tax, checkout totals, and synchronization configuration are unchanged.** It does not connect FlowTracker or create new API endpoints.

## Data

- `orders.is_remote_area`: nullable boolean. Created checkout orders use the already resolved `rural_surcharge_details` (no new lookup). Orders converted from bulk quotes copy their quote's stored classification.
- `bulk_quote_requests.is_remote_area`: nullable boolean. New storefront bulk quotes are classified once when submitted using the existing active UPS/legacy rules (`RuralAreaSurchargeService`). Incoming FlowTracker bulk quote upserts recalculate only when the postal code/country/state changes or the stored value is missing.
- Incoming FlowTracker-created orders can optionally supply `shipping_address.postal_code`, `shipping_address.country`, `shipping_address.state`, and `shipping_address.city`. If no postal code is supplied, the record is left unclassified instead of guessing from a free-text address.
- `true`: matched a configured active destination remote-area rule (regardless of fee amount); `false`: checked and not matched; `null`: historical/unclassifiable record awaiting usable postal data.
- Admin pages read the stored value directly; **no postal-code lookup is performed per list row**.

## Existing records

Run once during deployment, **after migration**:

```bash
php artisan migrate --force
php artisan nextplay:backfill-remote-area-flags
```

The command classifies legacy orders and bulk quotes in batches of 200, caches repeated destination matches within a batch, and updates flags in bulk without triggering outbound sync observers. Existing orders that already recorded a positive remote surcharge are marked remote without another matching lookup. Legacy rows without enough destination information remain `null`. The command is safe to rerun for newly classifiable records.

## Future FlowTracker integration

The existing NextPlay outbound payloads are extended (no new endpoints):

- Order: `FlowTrackOrderPayloadFactory::make()` sends top-level `is_remote_area` (`boolean|null`), alongside existing `shipping_address` and `rural_surcharge_total`.
- Inquiry: `FlowTrackInquiryPayloadFactory::make()` sends top-level `is_remote_area` (`boolean|null`), alongside `postal_code`, `country`, and `state_province`.

Example:

```json
{
  "postal_code": "99601",
  "is_remote_area": true
}
```

These fields become visible in FlowTracker only when its receiver/storage/UI subsequently supports them. **No FlowTracker-side code or connection is installed by this patch.** Keep the existing endpoints, authentication and sync toggles unchanged.

## Verification scope

- PHP syntax checks for changed classes and migration.
- Regression test added: `tests/Feature/RemoteAreaFlagTest.php` (historical classification, admin flag, additive inquiry API field).
- Laravel automated tests require installed Composer dependencies and a configured test database; neither is included in the source archive.
