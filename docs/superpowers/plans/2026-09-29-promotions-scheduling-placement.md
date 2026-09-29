# Promotions Scheduling & Placement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Sale navigation permanent, centralize campaign/banner scheduling, auto-generate stable campaign codes, and make banner placement/linkage behave predictably across Sale and Category while keeping All Products promotion-banner-free.

**Architecture:** Add one schedule authority (`PromotionScheduleService`) and one campaign-code generator (`SaleCampaignCodeGenerator`), then make the existing campaign/banner services, admin controllers, and storefront controllers delegate to them. Keep the existing `SaleCampaign`/`SaleBanner` data models and reusable `<x-storefront.sale-banner>` renderer; change only the placement set, resolution precedence, schedule evaluation, and admin inputs described in the approved spec.

**Tech Stack:** Laravel 13, PHP 8.4, Eloquent, CarbonImmutable, Blade/Alpine, existing `PublicUrl`/`PublicMedia`, PHPUnit/Pest-style Laravel tests plus existing source regression tests.

**Spec:** `docs/superpowers/specs/2026-09-29-promotions-scheduling-placement-design.md`

## Global Constraints

- Sale must always appear immediately after All Products in storefront navigation, regardless of promotion state.
- Campaign/banner status must be request-time computed; do not add cron/queue activation jobs.
- Campaign internal codes are server-generated once, unique, uppercase, stable after rename, and match `<PREFIX>-<4 ALNUM>`.
- Valid Banner placements are only `sale_top`, `sale_after_row_2`, and `category_top`.
- Retired `product_top` / `product_after_row_2` values must never render or reappear in admin.
- Linked active banners beat the Campaign-uploaded fallback; Campaign-uploaded fallback beats active global/unlinked banners.
- Sale top and repeated-after-row placements resolve independently.
- All Products must remain free of Promotions banners, including partial/AJAX refreshes.
- Keep current compact Sale banner dimensions, products, filters, pricing, pagination, theme, fonts, buttons, and upload behavior unchanged.
- Use `PublicUrl::isAllowed()` for banner destinations and fall back to `/sale` for malformed legacy values.

## Review Focus

- Exact boundary times: campaign/banner is Active at `starts_at` and remains Active through exactly `ends_at`.
- Invalid/missing timezone or weekday data must not 500; invalid timezone falls back to UTC and empty selected weekdays pause the campaign.
- A forged `internal_code` request must not override generated/stored code, including edits after rename.
- A banner inheriting a missing/deleted campaign must not render and must report Draft.
- Placement filtering must not let a `sale_top` banner leak into repeated rows, or a repeated-row banner leak into top/category positions.

---

### Task 1: Centralized Promotion Schedule Authority

**Files:**
- Create: `app/Services/Promotions/PromotionScheduleService.php`
- Modify: `app/Services/Promotions/SaleCampaignService.php`
- Modify: `app/Services/Promotions/SaleBannerService.php`
- Test: `tests/Unit/Promotions/PromotionScheduleServiceTest.php`
- Test: `tests/Feature/Promotions/SaleCampaignScheduleTest.php`

**Interfaces:**
- Consumes: `SaleCampaign`, `SaleBanner`, `CarbonImmutable`.
- Produces: `campaignStatus(SaleCampaign, ?CarbonImmutable): string`, `campaignIsActive(...): bool`, `bannerStatus(SaleBanner, ?CarbonImmutable): string`, `bannerIsActive(...): bool`.

- [ ] **Step 1: Write failing unit tests for campaign statuses**

Assert exact `Draft`, `Scheduled`, `Active`, `Paused today`, and `Ended` states, inclusive boundaries, timezone weekday conversion, invalid timezone → UTC, and repeat enabled with no weekdays → `Paused today`.

- [ ] **Step 2: Run the campaign schedule tests and verify RED**

Run: `php artisan test tests/Unit/Promotions/PromotionScheduleServiceTest.php --filter=campaign`
Expected: FAIL because `PromotionScheduleService` does not exist.

- [ ] **Step 3: Implement campaign schedule methods in `PromotionScheduleService`**

Use UTC for timestamp comparisons; use a guarded IANA timezone only for weekday evaluation.

- [ ] **Step 4: Run campaign schedule tests and verify GREEN**

Run: `php artisan test tests/Unit/Promotions/PromotionScheduleServiceTest.php --filter=campaign`
Expected: PASS.

- [ ] **Step 5: Write failing banner schedule tests**

Cover independent schedule, inherited campaign schedule including `Paused today`, inactive banner → `Draft`, and inherited banner with no campaign → `Draft`/not active.

- [ ] **Step 6: Run banner schedule tests and verify RED**

Run: `php artisan test tests/Unit/Promotions/PromotionScheduleServiceTest.php --filter=banner`
Expected: FAIL until banner schedule methods exist.

- [ ] **Step 7: Implement banner schedule methods and delegate existing services**

Inject/use `PromotionScheduleService` in `SaleCampaignService` and `SaleBannerService`; remove duplicated schedule calculations while preserving existing campaign/product grouping and banner ordering.

- [ ] **Step 8: Verify schedule consumers**

Run: `php artisan test tests/Unit/Promotions/PromotionScheduleServiceTest.php tests/Feature/Promotions/SaleCampaignScheduleTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Services/Promotions tests/Unit/Promotions tests/Feature/Promotions/SaleCampaignScheduleTest.php
git commit -m "refactor: centralize promotion schedule evaluation"
```

---

### Task 2: Stable Auto-Generated Campaign Internal Codes

**Files:**
- Create: `app/Services/Promotions/SaleCampaignCodeGenerator.php`
- Modify: `app/Http/Controllers/Admin/SaleCampaignController.php`
- Modify: `app/Http/Requests/Admin/StoreSaleCampaignRequest.php`
- Modify: `resources/views/admin/promotions/sales/create.blade.php`
- Create: `database/migrations/2026_09_29_173000_backfill_sale_campaign_internal_codes.php`
- Test: `tests/Unit/Promotions/SaleCampaignCodeGeneratorTest.php`
- Test: `tests/Feature/Admin/SaleCampaignInternalCodeTest.php`

**Interfaces:**
- Consumes: campaign name and existing `sale_campaigns.internal_code` values.
- Produces: `SaleCampaignCodeGenerator::generate(string $campaignName): string`.

- [ ] **Step 1: Write failing generator tests**

Assert `Winter Deals` prefix `WD-`, `Summer Clearance Sale` prefix `SCS-`, `Back To School Mega Sale` prefix `BTSM-`, one-word `Clearance` prefix `CL-`, uppercase four-character `A-Z0-9` suffix, and collision retry.

- [ ] **Step 2: Run generator tests and verify RED**

Run: `php artisan test tests/Unit/Promotions/SaleCampaignCodeGeneratorTest.php`
Expected: FAIL because generator is missing.

- [ ] **Step 3: Implement `SaleCampaignCodeGenerator::generate(string): string`**

Use a bounded retry loop and the database uniqueness check; throw a controlled exception after retry exhaustion.

- [ ] **Step 4: Run generator tests and verify GREEN**

Run: `php artisan test tests/Unit/Promotions/SaleCampaignCodeGeneratorTest.php`
Expected: PASS.

- [ ] **Step 5: Write failing admin persistence tests**

Assert create ignores forged `internal_code`, generates one server-side, edit preserves existing code after rename, and a legacy blank code is generated on save.

- [ ] **Step 6: Run persistence tests and verify RED**

Run: `php artisan test tests/Feature/Admin/SaleCampaignInternalCodeTest.php`
Expected: FAIL with current manual-code behavior.

- [ ] **Step 7: Wire generator into campaign create/update and remove request ownership of `internal_code`**

Generate only for new/blank code; preserve nonblank existing code.

- [ ] **Step 8: Make Internal Code read-only in Campaign admin UI**

Existing campaign shows its value; new campaign shows `Generated automatically when saved`.

- [ ] **Step 9: Add forward data migration for blank/null codes**

Backfill each blank campaign using the same generator algorithm/format without altering existing codes.

- [ ] **Step 10: Verify Campaign internal-code behavior**

Run: `php artisan test tests/Unit/Promotions/SaleCampaignCodeGeneratorTest.php tests/Feature/Admin/SaleCampaignInternalCodeTest.php`
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add app/Services/Promotions/SaleCampaignCodeGenerator.php app/Http/Controllers/Admin/SaleCampaignController.php app/Http/Requests/Admin/StoreSaleCampaignRequest.php resources/views/admin/promotions/sales/create.blade.php database/migrations/2026_09_29_173000_backfill_sale_campaign_internal_codes.php tests
git commit -m "feat: auto generate stable campaign codes"
```

---

### Task 3: Permanent Sale Navigation and Centralized Admin Status Labels

**Files:**
- Modify: `app/Services/Catalog/NavigationService.php`
- Modify: `app/Http/Controllers/Admin/SaleCampaignController.php`
- Modify: `resources/views/admin/promotions/sales/index.blade.php`
- Modify: `app/Http/Controllers/Admin/SaleBannerController.php`
- Modify: `resources/views/admin/promotions/banners/_banner-list.blade.php`
- Test: `tests/Feature/Admin/MenuManagementTest.php`
- Test: `tests/Feature/Admin/PromotionStatusDisplayTest.php`

**Interfaces:**
- Consumes: `PromotionScheduleService` from Task 1.
- Produces: always-normalized Sale navigation and controller-supplied runtime status labels.

- [ ] **Step 1: Add failing navigation tests**

Assert Sale is inserted immediately after All Products even with zero campaigns, only one Sale item exists, and configured Sale metadata is reused.

- [ ] **Step 2: Run navigation tests and verify RED**

Run: `php artisan test tests/Feature/Admin/MenuManagementTest.php --filter=sale`
Expected: FAIL because current navigation hides Sale without an active campaign.

- [ ] **Step 3: Remove runtime campaign dependency from `NavigationService::applyDynamicSaleNavigation()`**

Keep deduplication and insertion order behavior; do not alter cache semantics.

- [ ] **Step 4: Run navigation tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/MenuManagementTest.php --filter=sale`
Expected: PASS.

- [ ] **Step 5: Add failing admin status tests**

Assert Campaign and Banner list labels come from `PromotionScheduleService`, including `Paused today`.

- [ ] **Step 6: Run status tests and verify RED**

Run: `php artisan test tests/Feature/Admin/PromotionStatusDisplayTest.php`
Expected: FAIL while Blade/controller status logic is duplicated.

- [ ] **Step 7: Move list status evaluation to controllers/services**

Pass exact runtime label into views; remove schedule calculations from Blade.

- [ ] **Step 8: Verify navigation and status labels**

Run: `php artisan test tests/Feature/Admin/MenuManagementTest.php tests/Feature/Admin/PromotionStatusDisplayTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Services/Catalog/NavigationService.php app/Http/Controllers/Admin resources/views/admin/promotions tests/Feature/Admin
git commit -m "fix: keep sale navigation visible and unify promotion statuses"
```

---

### Task 4: Banner Placements, Schedule Validation, and Legacy Cleanup

**Files:**
- Modify: `app/Models/SaleBanner.php`
- Modify: `app/Http/Requests/Admin/SaveSaleBannerRequest.php`
- Modify: `resources/views/admin/promotions/banners/_editor.blade.php`
- Modify: `resources/views/admin/promotions/banners/_placement-preview.blade.php`
- Create: `database/migrations/2026_09_29_174000_normalize_sale_banner_placements.php`
- Test: `tests/Feature/Admin/SaleBannerValidationTest.php`
- Test: `tests/Feature/Promotions/SaleBannerPlacementMigrationTest.php`

**Interfaces:**
- Consumes: `PublicUrl::isAllowed()`, placement constants.
- Produces: valid placement set `sale_top`, `sale_after_row_2`, `category_top` only.

- [ ] **Step 1: Write failing placement/request validation tests**

Assert retired Product placements are rejected, at least one valid placement is required, inherited schedule requires linked campaign, independent schedule validates start/end/timezone, and unsafe destinations are rejected via `PublicUrl`.

- [ ] **Step 2: Run validation tests and verify RED**

Run: `php artisan test tests/Feature/Admin/SaleBannerValidationTest.php`
Expected: FAIL because retired placements and permissive URL validation still exist.

- [ ] **Step 3: Replace Banner placement constants and request validation**

Add `PLACEMENT_SALE_AFTER_ROW_2`; remove Product placements; use `PublicUrl::isAllowed()`.

- [ ] **Step 4: Update Banner editor labels/previews**

Expose exactly: Sale page — top, Sale page — after every second product row, Category page — top.

- [ ] **Step 5: Write failing migration test**

Assert retired placement values are removed, valid values preserved/deduplicated, and retired-only banners may end with an empty placement array.

- [ ] **Step 6: Run migration test and verify RED**

Run: `php artisan test tests/Feature/Promotions/SaleBannerPlacementMigrationTest.php`
Expected: FAIL before migration exists.

- [ ] **Step 7: Add forward migration to normalize existing `sale_banners.placements`**

Do not remap Product placements to Sale placements.

- [ ] **Step 8: Verify Banner validation and migration behavior**

Run: `php artisan test tests/Feature/Admin/SaleBannerValidationTest.php tests/Feature/Promotions/SaleBannerPlacementMigrationTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Models/SaleBanner.php app/Http/Requests/Admin/SaveSaleBannerRequest.php resources/views/admin/promotions/banners database/migrations/2026_09_29_174000_normalize_sale_banner_placements.php tests
git commit -m "feat: add sale banner placement controls"
```

---

### Task 5: Campaign-Aware Banner Resolution and Safe Payloads

**Files:**
- Modify: `app/Services/Promotions/SaleBannerService.php`
- Modify: `app/Services/Promotions/SaleCampaignService.php`
- Test: `tests/Unit/Promotions/SaleBannerServiceTest.php`
- Test: `tests/Regression/CampaignBannerStorefrontFallbackTest.php`

**Interfaces:**
- Consumes: Task 1 schedule authority, Task 4 placements, Campaign `campaign_banner` fallback payload.
- Produces: campaign/placement-specific banner resolver and safe storefront payloads.

- [ ] **Step 1: Write failing resolution-precedence tests**

For a requested campaign + placement assert precedence: active linked banner → Campaign-uploaded fallback → active global/unlinked banner → null. Assert a linked banner for Campaign A never resolves for Campaign B.

- [ ] **Step 2: Add failing independent-placement tests**

Assert `sale_top` and `sale_after_row_2` resolve independently and `category_top` does not leak into Sale.

- [ ] **Step 3: Add failing destination/payload tests**

Assert unsafe legacy destination normalizes to `/sale`, missing desktop image is ineligible, ordering remains priority → sort_order → id, and updated linked Banner fields are read fresh from the database on the next resolution.

- [ ] **Step 4: Run Banner service tests and verify RED**

Run: `php artisan test tests/Unit/Promotions/SaleBannerServiceTest.php`
Expected: FAIL because current service only returns placement-wide payloads.

- [ ] **Step 5: Implement campaign-aware placement resolver in `SaleBannerService`**

Expose a focused method such as `resolveForCampaignPlacement(?SaleCampaign $campaign, string $placement, ?array $campaignFallback = null): ?array`; keep existing category/global helpers where still used.

- [ ] **Step 6: Harden payload destination normalization**

Use `PublicUrl::isAllowed()` before returning the destination.

- [ ] **Step 7: Verify Banner resolution and Campaign fallback regression**

Run: `php artisan test tests/Unit/Promotions/SaleBannerServiceTest.php && php tests/Regression/CampaignBannerStorefrontFallbackTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Promotions tests/Unit/Promotions tests/Regression/CampaignBannerStorefrontFallbackTest.php
git commit -m "feat: resolve banners by campaign and placement"
```

---

### Task 6: Sale/Category Storefront Placement Integration and All Products Guard

**Files:**
- Modify: `app/Http/Controllers/Storefront/ProductController.php`
- Modify: `resources/views/storefront/products/sale.blade.php`
- Modify: `resources/views/storefront/products/_sale-results.blade.php`
- Verify/modify only if needed: `app/Http/Controllers/Storefront/CategoryController.php`
- Test: `tests/Feature/Storefront/SaleBannerPlacementTest.php`
- Test: `tests/Feature/Storefront/AllProductsPromotionBannerIsolationTest.php`
- Modify: `tests/Regression/SaleBannerRowPlacementTest.php`

**Interfaces:**
- Consumes: Task 5 campaign-aware resolver.
- Produces: separate `top_banner` and `after_row_banner` per campaign section.

- [ ] **Step 1: Write failing Sale placement integration tests**

Assert first visible campaign supplies its resolved `sale_top` banner above the summary/sort bar; repeated positions use only `sale_after_row_2`; selecting both placements may show the same Banner in both; Campaign fallback remains available for both when no linked placement-specific Banner exists.

- [ ] **Step 2: Run Sale placement tests and verify RED**

Run: `php artisan test tests/Feature/Storefront/SaleBannerPlacementTest.php`
Expected: FAIL because current controller supplies one `primary_banner` to both positions.

- [ ] **Step 3: Update ProductController Sale section payload**

Resolve/store independent `top_banner` and `after_row_banner`; preserve current paginator/filter/sort grouping.

- [ ] **Step 4: Update Sale Blade partials to consume independent banners**

Keep the existing responsive every-second-visual-row insertion counts and compact banner component dimensions unchanged.

- [ ] **Step 5: Write failing All Products isolation tests**

Assert normal and `X-Storefront-Partial: product-results` All Products responses do not query/render SaleBanner/Campaign promotional banners or retired placements.

- [ ] **Step 6: Run All Products isolation tests and verify RED/GREEN as appropriate**

Run: `php artisan test tests/Feature/Storefront/AllProductsPromotionBannerIsolationTest.php`
Expected: PASS only if the existing banner-free contract remains intact; if it fails, remove only the Promotions dependency causing it.

- [ ] **Step 7: Verify Category top behavior uses centralized schedule service**

Run the Category banner regression/feature coverage; keep current category targeting behavior unchanged.

- [ ] **Step 8: Run storefront banner regression matrix**

Run: `php artisan test tests/Feature/Storefront/SaleBannerPlacementTest.php tests/Feature/Storefront/AllProductsPromotionBannerIsolationTest.php && php tests/Regression/SaleBannerRowPlacementTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Storefront resources/views/storefront/products tests/Feature/Storefront tests/Regression/SaleBannerRowPlacementTest.php
git commit -m "feat: apply promotion banners by sale placement"
```

---

### Task 7: End-to-End Promotions Verification

**Files:**
- Test/verify all files changed in Tasks 1–6.
- Add/adjust source regression checks only where the archive's dependency state requires them.

**Interfaces:**
- Consumes: all previous tasks.
- Produces: deployable Promotions behavior with migrations and no unrelated storefront regression.

- [ ] **Step 1: Run focused Promotions test suite**

Run: `php artisan test tests/Unit/Promotions tests/Feature/Admin/SaleCampaignInternalCodeTest.php tests/Feature/Admin/SaleBannerValidationTest.php tests/Feature/Admin/PromotionStatusDisplayTest.php tests/Feature/Storefront/SaleBannerPlacementTest.php tests/Feature/Storefront/AllProductsPromotionBannerIsolationTest.php`
Expected: PASS.

- [ ] **Step 2: Run existing Campaign/Banner regressions**

Run the repository's current Promotion/Sale regression scripts, including `CampaignBannerStorefrontFallbackTest.php` and `SaleBannerRowPlacementTest.php`.
Expected: PASS or document only pre-existing unrelated failures with baseline evidence.

- [ ] **Step 3: Run PHP syntax checks on every changed PHP/Blade file**

Run: `find app database resources/views tests -type f \( -name '*.php' -o -name '*.blade.php' \) -print0 | xargs -0 -n1 php -l`
Expected: zero syntax errors.

- [ ] **Step 4: Run full Laravel suite**

Run: `php artisan test`
Expected: PASS. If the supplied archive has no `vendor/autoload.php`, record the environment limitation and keep all dependency-backed tests in the delivery.

- [ ] **Step 5: Run frontend/admin JavaScript syntax/build checks if dependencies are present**

Run existing project build command and `node --check` for touched standalone JS if any.
Expected: PASS, or record missing dependency limitation.

- [ ] **Step 6: Re-read spec and verify every requirement**

Confirm: always-visible Sale nav, centralized statuses, auto code, inherited/independent schedules, corrected placements, linked-banner freshness, placement precedence, Sale top/repeated independence, Category top preserved, All Products promotion-free.

- [ ] **Step 7: Package and re-extract the final ZIP, then rerun focused source/syntax checks on the extracted artifact**

Expected: packaged artifact matches verified source.

- [ ] **Step 8: Commit**

```bash
git add .
git commit -m "test: verify promotions scheduling and placement"
```
