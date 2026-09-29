# Promotions Scheduling, Campaign Codes, and Banner Placement Design

**Date:** 2026-09-29  
**Project:** NextPlay Sportswear  
**Source:** `Archive 10.zip`

## 1. Goal

Update the existing Promotions system without introducing a parallel campaign/banner engine. The result must:

- keep **Sale** visible in the storefront header at all times;
- make campaign scheduling authoritative, timezone-aware, and consistent between admin and storefront;
- automatically generate a stable internal campaign code from the campaign name;
- make banner scheduling activate/deactivate automatically at the configured time;
- make campaign-linked banners immediately reflect banner edits on the storefront;
- let admins choose where each banner is displayed;
- keep **All Products completely free of promotional Sale banners**;
- preserve the current compact Sale banner rendering, product cards, filters, pagination, campaign pricing, and unrelated storefront/admin behavior.

This is an evolution of the current `SaleCampaign`, `SaleBanner`, `SaleCampaignService`, `SaleBannerService`, and `NavigationService` architecture, not a replacement.

---

## 2. Existing Architecture Being Preserved

The current project already has:

- `App\Models\SaleCampaign`
- `App\Models\SaleBanner`
- `App\Services\Promotions\SaleCampaignService`
- `App\Services\Promotions\SaleBannerService`
- `App\Services\Catalog\NavigationService`
- `App\Http\Controllers\Admin\SaleCampaignController`
- `App\Http\Controllers\Admin\SaleBannerController`
- dedicated Campaign and Banner admin screens
- UTC campaign/banner timestamps plus a stored IANA timezone
- campaign weekday-repeat fields
- campaign-uploaded fallback banner content
- linked `SaleBanner` records
- the reusable `<x-storefront.sale-banner>` component
- Sale-page compact banner styling and row insertion logic
- `PublicUrl` and `PublicMedia`
- All Products already separated from Sale campaign-banner rendering.

Those patterns remain the source of truth.

---

## 3. Permanent Sale Navigation

### Required behavior

The storefront header must show **Sale** at all times, whether or not a campaign is active, scheduled, ended, or absent.

The order remains:

`Home → Shop by Category → All Products → Sale → Bulk Quote ...`

The same rule applies wherever the `header-primary` navigation is rendered, including mobile navigation.

### Implementation rule

`NavigationService::applyDynamicSaleNavigation()` currently removes Sale unless `SaleCampaignService::hasActiveSalePageCampaign()` returns true. That active-campaign dependency will be removed.

The normalization step will always:

1. remove duplicate existing `sale.index` items;
2. reuse a configured Sale menu item if one exists;
3. otherwise build the default Sale item;
4. insert it immediately after the `products.index` item;
5. append it if All Products is not present.

Navigation cache behavior remains unchanged. Sale visibility no longer depends on promotion runtime state.

### Empty Sale page

`/sale` remains accessible with zero active campaigns. It must render the existing normal Sale empty state rather than returning 404 or hiding the route.

---

## 4. Centralized Campaign Schedule Evaluation

### Problem being solved

Campaign activity is currently evaluated independently in:

- `SaleCampaignService::activeCampaigns()` for storefront eligibility;
- `resources/views/admin/promotions/sales/index.blade.php` for admin status labels;
- `SaleBannerService` again when a banner inherits a campaign schedule.

These copies can drift.

### New scheduling authority

Add a focused promotions schedule service, for example:

`App\Services\Promotions\PromotionScheduleService`

It will be the single authority for campaign and banner runtime status.

### Campaign statuses

The service returns one of these exact display/runtime statuses:

- `Draft`
- `Scheduled`
- `Active`
- `Paused today`
- `Ended`

### Campaign evaluation rules

Given a UTC `now` value:

1. `status !== 'live'` → `Draft`.
2. `now < starts_at` → `Scheduled`.
3. `now > ends_at` → `Ended`.
4. If `repeat_weekdays === false` → `Active` while within the date range.
5. If weekday repeat is enabled:
   - convert `now` to the campaign's selected IANA timezone;
   - compare `format('D')` against the stored `Mon`–`Sun` values;
   - matching selected day → `Active`;
   - non-selected day → `Paused today`.
6. Malformed legacy timezone values fall back to UTC rather than throwing a storefront/admin exception.
7. A malformed repeat configuration with no selected weekdays is treated as `Paused today`, not active.

The date boundaries are inclusive: a campaign is active at exactly `starts_at` and remains active through exactly `ends_at`.

### Consumers

- `SaleCampaignService::activeCampaigns()` uses `PromotionScheduleService::campaignIsActive(...)` instead of duplicating date/weekday logic.
- Campaign admin list status uses the same service.
- Campaign-linked banners use the same campaign status through the same service.

This guarantees admin and storefront agree on whether a campaign is active.

---

## 5. Campaign Internal Code Generation

### Required behavior

Internal campaign codes are no longer manually authored.

Examples:

- `Winter Deals` → `WD-12AB`
- `Summer Clearance Sale` → `SCS-7K2P`

### Code format

`<NAME-PREFIX>-<4 CHARACTER SUFFIX>`

Rules:

1. Split the campaign name on non-alphanumeric separators.
2. For two or more usable words, use the first uppercase character from each word, capped at **4 prefix characters**.
   - `Winter Deals` → `WD`
   - `Summer Clearance Sale` → `SCS`
   - `Back To School Mega Sale` → `BTSM`
3. For a one-word name, use the first two uppercase alphanumeric characters.
   - `Clearance` → `CL`
4. If a very short/degenerate name yields only one usable character, that single character is allowed as the prefix.
5. Add `-` plus a four-character uppercase alphanumeric suffix using characters `A-Z0-9`.
6. Check the existing unique `sale_campaigns.internal_code` constraint before accepting the candidate.
7. Retry on collisions. A bounded retry limit must fail loudly rather than silently creating a duplicate.

### Stability rule

The internal code is generated once and remains stable when the campaign is renamed.

- New campaign → generate during creation before first save.
- Existing campaign with a valid code → preserve it on update.
- Existing legacy campaign with a blank/null code → generate one the next time it is saved.

A data migration will also backfill currently blank/null internal codes so existing campaigns immediately satisfy the new rule.

### Admin UI

The editable Internal Code input is removed or converted to a read-only display.

For a new unsaved campaign, the UI may show `Generated automatically when saved` rather than pretending a final code already exists.

The server remains authoritative; no client-side generated code is trusted.

### Request validation

`StoreSaleCampaignRequest` stops accepting `internal_code` as editable campaign data. A forged request value must not override the server-generated/stored code.

---

## 6. Banner Schedule Evaluation

### Independent banner schedule

A banner not inheriting a campaign schedule uses its own:

- `starts_at`
- `ends_at`
- `timezone`
- `is_active`

Runtime statuses are:

- `Draft` when `is_active === false`;
- `Scheduled` before `starts_at`;
- `Active` between start and end, inclusive;
- `Ended` after `ends_at`.

A scheduled banner therefore becomes active automatically when the configured start time is reached. No cron job is required merely to change state because status is evaluated at request time.

### Inherited campaign schedule

When `inherit_campaign_schedule === true`:

1. `sale_campaign_id` is required.
2. The banner inherits the linked campaign's full runtime schedule:
   - draft/live publication state;
   - start time;
   - end time;
   - campaign timezone;
   - repeat-weekday selection.
3. Banner status mirrors the campaign runtime status:
   - campaign `Draft` → banner `Draft`;
   - campaign `Scheduled` → banner `Scheduled`;
   - campaign `Active` → banner `Active`;
   - campaign `Paused today` → banner `Paused today`;
   - campaign `Ended` → banner `Ended`.
4. The banner's own stored start/end fields are ignored while inheritance is enabled.

### Admin consistency

`SaleBannerController` status output and `SaleBannerService::isActiveNow()` both use `PromotionScheduleService`; scheduling logic is not reimplemented in Blade/controller code.

---

## 7. Safe Banner Destination Validation

The Banner request currently accepts any full URL validated only by `FILTER_VALIDATE_URL`.

For consistency with Campaign banners and existing security handling, `SaveSaleBannerRequest` will use `App\Support\PublicUrl::isAllowed()`.

Allowed:

- safe relative paths beginning with `/`, excluding protocol-relative paths;
- valid HTTP/HTTPS URLs.

Rejected:

- `javascript:`
- `data:`
- protocol-relative `//host/path`
- malformed/backslash/control-character destinations.

Existing malformed legacy destinations are normalized to `/sale` when building a storefront banner payload instead of being rendered directly.

---

## 8. Banner Placement Model

### Valid placements after this change

The Banner admin offers exactly these storefront placements:

1. `sale_top` — **Sale page: top**
2. `sale_after_row_2` — **Sale page: after every second product row**
3. `category_top` — **Category page: top**

### Removed placements

The following legacy placements are retired:

- `product_top`
- `product_after_row_2`

They must no longer appear in `SaleBanner::PLACEMENTS`, request validation, admin checkboxes, labels, previews, or storefront code.

### Legacy-data migration

A migration will normalize existing `sale_banners.placements` arrays:

- remove `product_top`;
- remove `product_after_row_2`;
- preserve existing `sale_top` and `category_top` values;
- deduplicate values;
- do **not** automatically reinterpret an All Products banner as a Sale banner.

If a legacy banner contained only retired placements, its resulting placement list may be empty. It stays non-rendering until an admin edits it and selects a valid placement. This is safer than unexpectedly surfacing an old All Products banner elsewhere.

Future saves require at least one current valid placement.

---

## 9. Sale Banner Resolution and Precedence

The Sale page needs separate banner resolution for the **top** and **after-row** placements. A banner selected for one placement must not leak into the other.

### Campaign-specific resolution

For each visible campaign section and requested placement:

1. first active `SaleBanner` explicitly linked to that campaign and containing that placement;
2. otherwise the Campaign's directly uploaded banner (`banner_image_path` + campaign banner text) as the campaign fallback;
3. otherwise first active **unlinked/global** `SaleBanner` containing that placement;
4. otherwise no banner.

This precedence keeps explicitly linked Banner records authoritative while preserving the Campaign upload workflow already built.

### Why direct Campaign banner remains a fallback for both Sale positions

The Campaign editor has no placement selector and the current approved Sale behavior uses the campaign artwork both at the campaign/top position and after every second product row. Keeping it as the fallback for both Sale placements preserves that behavior. Admins who need different artwork/visibility at either position use the Banner page placement checkboxes.

### Linked Banner update behavior

No storefront banner payload is cached across requests. When an admin edits a linked Banner's:

- desktop/mobile image;
- heading;
- alt text;
- CTA label;
- destination;
- priority;
- placement;
- schedule;

the next storefront request resolves the current saved Banner record and renders the updated values.

### Multiple eligible banners

Existing deterministic ordering remains:

1. lower `priority` first (matching current `SaleBannerService` semantics);
2. then `sort_order`;
3. then `id`.

The first eligible banner after scope filtering is used for a single campaign/placement slot.

---

## 10. Sale Page Rendering

### Top banner

The top banner remains above the Sale summary/sort bar, using the compact shared `<x-storefront.sale-banner>` component and the current uniform Sale-page banner height.

The first visible campaign section supplies its resolved `sale_top` banner.

### Repeated banner

Each campaign section separately resolves a `sale_after_row_2` banner.

That banner is inserted after every second **visual product row** using the existing responsive breakpoints/row logic already present in `_sale-results.blade.php`.

The top banner and repeated banner can therefore be different.

Example:

- Banner A linked to Campaign 1, placement `sale_top` only → only top.
- Banner B linked to Campaign 1, placement `sale_after_row_2` only → only repeated positions.
- Banner C selected for both → both positions.

The compact uniform Sale banner size remains unchanged by this scheduling/placement work.

### Pagination/filtering

Banner placement must continue to work with:

- search/filter requests;
- sort changes;
- `X-Storefront-Partial: product-results` refreshes;
- campaign-group pagination.

No separate paginator is added per campaign.

---

## 11. Global/Unlinked Banners

A `SaleBanner` with no linked campaign remains valid.

- `sale_top`: may act as the global fallback when a campaign has neither an active linked top banner nor a direct Campaign banner.
- `sale_after_row_2`: may act as the global fallback when a campaign has neither an active linked after-row banner nor a direct Campaign banner.
- `category_top`: remains the general category-top banner placement under the existing Category controller behavior.

A linked Banner is campaign-specific and is never used as another campaign's Sale banner.

---

## 12. All Products Must Stay Banner-Free

This requirement is absolute.

The All Products route/controller/partial must not:

- query `SaleBannerService` for promotional placement banners;
- render Campaign banners;
- render global Sale banners;
- render legacy `product_top` or `product_after_row_2` placements;
- inject banners during AJAX result refreshes.

`CatalogPageAppearance::productsBanner()` is the existing catalog-page appearance/banner mechanism and is separate from Promotions. This Promotions update does not reintroduce promotional campaign banners to All Products.

---

## 13. Category Banner Behavior

`category_top` remains supported because it is an existing Banner placement.

The current Category controller can continue requesting the first active `category_top` banner. Schedule evaluation will improve automatically because `SaleBannerService` uses the centralized schedule authority.

No new category-target selector is introduced in this scope.

---

## 14. Campaign Admin UI Changes

The current Campaign editor layout remains intact except for the Internal Code field behavior.

### Internal Code

- not editable;
- existing code displayed read-only when editing;
- new Campaign displays a short helper/read-only state explaining it will be generated on save;
- no manual override control.

### Schedule

The existing Schedule UI remains:

- Start date & time
- End date & time
- Time zone
- Active on weekdays
- Repeat on selected weekdays

No new scheduling UI is needed. The work is to make its persisted values and runtime evaluation authoritative and consistent.

### Campaign list status

The Campaign index stops calculating schedule state inside Blade. The controller/service supplies the centralized runtime label.

---

## 15. Banner Admin UI Changes

The existing Banner page and editor remain structurally unchanged.

### Placement options

Replace the old options with:

- Sale page — top
- Sale page — after every second product row
- Category page — top

The placement preview text/labels must use the same names.

### Schedule behavior

The existing `Inherit campaign schedule` control remains.

- ON: linked campaign required; inherited campaign schedule/status is authoritative.
- OFF: banner's own start/end/timezone are required and authoritative.

Admin list status comes from the centralized scheduling service.

### Campaign linkage

Changing `sale_campaign_id` and saving immediately changes which campaign can resolve the banner. No copying of image/text into the Campaign table occurs.

---

## 16. Database Changes

No new campaign schedule columns are needed.

Required migration work:

1. Backfill blank/null `sale_campaigns.internal_code` values with unique generated codes.
2. Normalize `sale_banners.placements` by removing retired Product-list placements.

The existing unique database index on `sale_campaigns.internal_code` remains the final collision guard.

Migrations must be reversible where practical. `down()` cannot reliably reconstruct deleted legacy placement intent, so placement normalization is treated as a forward cleanup; schema itself is unchanged.

---

## 17. Services and Responsibilities

### `PromotionScheduleService`

Single responsibility: calculate campaign/banner runtime state.

Expected public API:

- `campaignStatus(SaleCampaign $campaign, ?CarbonImmutable $nowUtc = null): string`
- `campaignIsActive(SaleCampaign $campaign, ?CarbonImmutable $nowUtc = null): bool`
- `bannerStatus(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): string`
- `bannerIsActive(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): bool`

### `SaleCampaignCodeGenerator`

Single responsibility: generate a unique stable internal Campaign code.

Expected public API:

- `generate(string $campaignName): string`

It queries `sale_campaigns.internal_code` for uniqueness and returns only a validated code matching the agreed format.

### `SaleCampaignService`

Continues to own campaign eligibility/pricing/product grouping, but delegates runtime activity to `PromotionScheduleService`.

### `SaleBannerService`

Continues to own banner eligibility/payload generation, but:

- delegates runtime activity/status to `PromotionScheduleService`;
- understands the new placement set;
- safely normalizes destination URLs;
- supports campaign-linked and global filtering required by Sale-page resolution.

### `NavigationService`

Always normalizes the Sale route into the header; no schedule dependency.

---

## 18. Error Handling and Defensive Behavior

The update must not create 500s when legacy promotion data is imperfect.

Required fallbacks:

- invalid campaign timezone → use UTC for runtime evaluation;
- invalid banner timezone → use UTC for runtime evaluation;
- malformed repeat weekdays → campaign is not active on the current day;
- inherited banner with missing/deleted campaign → `Draft`/not rendered;
- banner missing desktop image → not eligible for storefront rendering;
- unsafe legacy destination link → `/sale`;
- retired/unknown placement value → ignored;
- duplicate placement values → normalized/deduplicated;
- internal-code collision → generate another suffix;
- code-generation retry exhaustion → fail the save with a controlled exception/error rather than duplicate a code.

Existing upload rollback/cleanup behavior remains unchanged.

---

## 19. What Must Not Change

This work must not redesign or alter:

- discount calculations;
- percentage/fixed discount behavior;
- maximum discount rules;
- product/category campaign targeting;
- campaign exclusions;
- Sale badge behavior;
- product cards;
- Sale filters/sort controls;
- Sale pagination architecture;
- campaign banner image upload lifecycle;
- Banner image upload lifecycle;
- compact Sale banner visual design/height;
- Homepage promotions;
- Stripe/payment logic;
- checkout/shipping logic;
- About/Shipping/Sustainability admin modules;
- All Products promotional-banner exclusion.

No cron job, queue worker, scheduler command, page builder, additional campaign state column, or duplicate promotions engine will be introduced.

---

## 20. Test Requirements

Implementation follows TDD and adds/updates tests for the following behaviors.

### Schedule unit tests

Campaign:

- draft campaign is Draft/not active;
- future live campaign is Scheduled/not active;
- current non-repeating campaign is Active;
- ended campaign is Ended;
- repeating campaign on selected local weekday is Active;
- repeating campaign on non-selected local weekday is Paused today;
- timezone boundary uses campaign local weekday correctly;
- malformed timezone falls back safely.

Banner:

- independent future banner is Scheduled;
- independent current banner is Active;
- independent ended banner is Ended;
- inactive banner is Draft;
- inherited banner mirrors linked campaign Active/Scheduled/Paused today/Ended/Draft;
- inherited banner without campaign is not active.

### Campaign-code tests

- `Winter Deals` code matches `^WD-[A-Z0-9]{4}$`;
- one-word name uses first two characters;
- multi-word prefix is capped at four characters;
- collisions retry until unique;
- update preserves existing code after Campaign rename;
- forged `internal_code` request input is ignored;
- legacy blank code is populated.

### Navigation tests

- Sale exists with zero campaigns;
- Sale exists with scheduled-only campaigns;
- Sale exists with ended campaigns;
- Sale is directly after All Products;
- duplicate configured Sale item is normalized to one entry.

### Banner placement tests

- old Product-list placements are rejected by current validation;
- `sale_top`, `sale_after_row_2`, `category_top` are accepted;
- migration removes legacy Product-list placement values;
- legacy-only banner becomes non-rendering rather than being remapped unexpectedly.

### Sale storefront tests

- active linked `sale_top` banner overrides direct Campaign banner at top;
- active linked `sale_after_row_2` banner overrides Campaign fallback for repeated rows;
- top-only banner never appears in repeated-row slot;
- after-row-only banner never appears in top slot;
- Campaign direct banner remains fallback when no linked banner exists;
- global unlinked banner is final fallback when no linked/direct banner exists;
- scheduled banner does not appear before start and appears at start;
- ended banner disappears after end;
- linked inherited banner follows campaign weekday pause;
- editing linked banner heading/image/CTA changes the next rendered payload.

### All Products tests

- no `SaleBannerService` promotional banner is rendered on All Products;
- AJAX/partial All Products results remain banner-free;
- retired Product-list placements cannot re-enable promotional banners there.

### Existing regression suite

Run the project's relevant Promotion, Navigation, Catalog, Category, Sale, and storefront tests plus the complete Laravel test suite when dependencies are available.

---

## 21. Deployment

Deployment requires running the new data migration and clearing cached configuration/navigation/views as appropriate:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize:clear
```

No worker restart or recurring scheduler entry is required for campaign/banner activation because time-based eligibility is evaluated dynamically from stored schedule data.

---

## 22. Acceptance Criteria

The work is accepted when all of the following are true:

1. Sale is visible in header all the time.
2. `/sale` is reachable with no active campaign.
3. Campaign admin and storefront use one schedule evaluator and agree on status.
4. Future published campaigns activate automatically at their configured time.
5. Weekday repetition uses the campaign's selected timezone correctly.
6. New Campaign internal codes are server-generated in the agreed short format and cannot be manually overridden.
7. Existing Campaign codes remain stable after renaming.
8. Future banners activate automatically at their scheduled start.
9. Inherited banners follow the linked Campaign's full schedule including weekdays.
10. A linked Banner edit is reflected on the storefront on the next request.
11. Banner admin exposes only Sale top, Sale after every second row, and Category top placements.
12. Banner placement controls top and repeated Sale positions independently.
13. Campaign-uploaded banner remains a safe fallback.
14. All Products receives no promotional Campaign/Banner rendering.
15. Existing compact Sale banner visuals, discounts, products, filters, pagination, uploads, and unrelated modules remain unchanged.
