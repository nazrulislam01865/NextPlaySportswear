# NextPlay Homepage Admin CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Realign the existing Laravel homepage CMS so the current Vue 3 homepage is fully manageable section-by-section from the Blade admin without duplicating catalog/product business data.

**Architecture:** Keep the existing `homepage_sections` and `homepage_slides` foundations, add section-specific `settings` plus safe item-level media handling, retire obsolete section definitions, and expose the new nine-section contract through `/api/v1/home`. The Vue homepage keeps its approved layout/order and receives section configuration as props; product feeds remain Laravel-owned and product-card content is never duplicated into CMS JSON.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL, Blade admin, Laravel Form Requests/API Resources/Services, Vue 3, TypeScript, Vite, existing Tailwind admin CSS, public-disk media storage.

**Spec:** `docs/superpowers/specs/2026-09-18-nextplay-homepage-admin-cms-design.md`

## Global Constraints

- Keep NextPlay as ONE Laravel project; do not create a separate frontend project or microservice.
- Laravel remains the source of truth for products, pricing, catalog, cart, checkout, payments, orders, customers, FlowTrack integration, and database access.
- New storefront API behavior stays under `/api/v1`; controllers stay thin, validation stays in Form Requests, and API JSON stays frontend-neutral.
- Vue must not access MySQL directly and must not contain authoritative business logic.
- Keep the existing Blade admin; only Homepage Controls receives the new scoped admin visual system in this phase.
- Preserve the approved Vue homepage layout, typography, dimensions, colors, spacing, hover behavior, header, mega menu, footer, and product-card design.
- Product cards remain catalog-driven; Homepage Controls must not own product prices, SKU, badges, inventory, images, or customization data.
- Every non-product image that appears in the nine homepage sections must be uploadable/replacable from admin where the design contains an image.
- Existing `homepage_slides` data is preserved and managed from the Hero Banner workflow.
- Legacy homepage rows are retired, not physically deleted.
- Reuse existing services and media patterns; do not process uploaded files directly in controllers.
- Use TDD for every behavior change and run focused tests before each task commit.

---

## File Map

### Database and domain configuration
- Create `database/migrations/2026_09_18_160000_add_settings_to_homepage_sections.php` — add reversible `settings` JSON column.
- Create `database/migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php` — seed/migrate new section rows from reusable legacy values without deleting old rows.
- Modify `app/Models/HomepageSection.php` — persist/cast `settings`.
- Modify `app/Support/HomepageSectionRegistry.php` — exactly nine active definitions, defaults, retired keys, merge rules.

### Validation and media
- Modify `app/Http/Requests/Admin/HomepageSectionRequest.php` — section-aware rules, settings normalization, stable item IDs, image request fields.
- Modify `app/Services/Catalog/HomepageSectionMediaService.php` — section media plus item-level upload/replacement/removal.

### Admin controller and UI
- Modify `app/Http/Controllers/Admin/HomepageSectionController.php` — dashboard summaries, section-aware editor data, media-aware update flow.
- Modify `resources/views/components/layouts/admin.blade.php` — one Homepage Controls navigation entry; remove generated section submenu and duplicate slider entry.
- Replace `resources/views/admin/homepage-sections/index.blade.php` — section dashboard.
- Replace `resources/views/admin/homepage-sections/edit.blade.php` — small orchestrator only.
- Create `resources/views/admin/homepage-sections/partials/_hero.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_audience.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_shop-by-sport.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_new-arrivals.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_shop-by-category.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_best-choices.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_season-sale.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_make-it-yours.blade.php`.
- Create `resources/views/admin/homepage-sections/partials/_design-process.blade.php`.
- Create `resources/views/components/admin/homepage/section-panel.blade.php`.
- Create `resources/views/components/admin/homepage/text-field.blade.php`.
- Create `resources/views/components/admin/homepage/link-field.blade.php`.
- Create `resources/views/components/admin/homepage/media-field.blade.php`.
- Create `resources/views/components/admin/homepage/visibility-field.blade.php`.
- Create `resources/views/components/admin/homepage/item-card.blade.php`.
- Create `resources/views/components/admin/homepage/save-bar.blade.php`.
- Create `resources/css/admin/homepage.css`.
- Modify `resources/css/admin.css` — import the scoped Homepage Controls stylesheet.
- Modify `resources/views/admin/homepage-slides/index.blade.php` and `_form.blade.php` only enough to inherit the scoped homepage admin surface when entered from Hero Banner.

### Storefront API/services
- Modify `app/Services/Storefront/HomepageSectionService.php` — new cache version and exact nine-section output.
- Modify `app/Services/Storefront/HomePageService.php` — keep product/category/sport feeds, remove obsolete old-home payloads once tests prove unused, use CMS process items.
- Modify `app/Http/Resources/Api/V1/Storefront/HomepageResource.php` — new explicit public contract.

### Vue storefront
- Modify `resources/js/storefront/features/home/types/home.types.ts` — typed section/item/settings contracts.
- Create `resources/js/storefront/features/home/utils/homeSection.ts` — small normalizers/fallback helpers.
- Modify `resources/js/storefront/features/home/pages/HomePage.vue` — section visibility and configuration wiring.
- Modify all nine existing homepage section components listed in the spec to remove hardcoded editable content.
- Modify `resources/js/storefront/features/home/components/ProcessStepCard.vue` — accept CMS image/alt rather than placeholder-only behavior.

### Tests
- Modify `tests/Unit/HomepageSectionOrderTest.php`.
- Create `tests/Feature/Admin/HomepageSectionAdminTest.php`.
- Create `tests/Feature/Admin/HomepageSectionMediaTest.php`.
- Modify `tests/Feature/Api/V1/Storefront/HomepageApiTest.php`.
- Modify `tests/Feature/HomepageProductCollectionsTest.php` only if old payload assertions require cleanup.
- Modify `tests/Feature/HomepageSliderTest.php` for Hero Banner integration/navigation coverage.
- Create `tests/frontend/homepage-cms-contract.test.mjs`.
- Keep all existing homepage fidelity tests unchanged except assertions that explicitly encode retired section names/contracts.

---

### Task 1: Add `settings` persistence to homepage sections

**Files:**
- Create: `database/migrations/2026_09_18_160000_add_settings_to_homepage_sections.php`
- Modify: `app/Models/HomepageSection.php`
- Test: `tests/Feature/Admin/HomepageSectionAdminTest.php`

**Interfaces:**
- Consumes: existing `homepage_sections` table and `HomepageSection` model.
- Produces: nullable `settings` JSON persisted as `array|null` through `HomepageSection::$casts`.

- [ ] **Step 1: Write the failing persistence test**

Create the test file with `RefreshDatabase` and this test first:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_section_settings_are_persisted_as_an_array(): void
    {
        $section = HomepageSection::query()->create([
            'key' => 'best_choices',
            'name' => 'Best Choices For You',
            'settings' => [
                'tabs' => [
                    'featured' => ['label' => 'FEATURED', 'enabled' => true],
                ],
            ],
            'is_active' => true,
            'sort_order' => 60,
        ]);

        $section->refresh();

        $this->assertSame('FEATURED', $section->settings['tabs']['featured']['label']);
        $this->assertTrue($section->settings['tabs']['featured']['enabled']);
    }
}
```

- [ ] **Step 2: Run the focused test and confirm failure**

Run:

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter=settings_are_persisted
```

Expected: FAIL because the `settings` column does not exist.

- [ ] **Step 3: Add the reversible migration**

Create:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->json('settings')->nullable()->after('items');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
};
```

- [ ] **Step 4: Update the model**

Add `settings` to `$fillable` and `casts()`:

```php
protected $fillable = [
    'key', 'name', 'eyebrow', 'title', 'description', 'primary_label',
    'primary_url', 'secondary_label', 'secondary_url', 'image_path', 'image_url',
    'image_alt', 'mobile_image_path', 'mobile_image_url', 'mobile_image_alt',
    'hero_slides', 'items', 'settings', 'is_active', 'sort_order', 'created_by', 'updated_by',
];

protected function casts(): array
{
    return [
        'hero_slides' => 'array',
        'items' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
```

- [ ] **Step 5: Run the focused test**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter=settings_are_persisted
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_18_160000_add_settings_to_homepage_sections.php app/Models/HomepageSection.php tests/Feature/Admin/HomepageSectionAdminTest.php
git commit -m "feat: add homepage section settings storage"
```

---

### Task 2: Replace the legacy registry with the nine approved homepage sections

**Files:**
- Modify: `app/Support/HomepageSectionRegistry.php`
- Modify: `tests/Unit/HomepageSectionOrderTest.php`

**Interfaces:**
- Consumes: `HomepageSection` rows and the `settings` support from Task 1.
- Produces: `HomepageSectionRegistry::orderedDefinitions()` with exactly `hero`, `audience`, `shop_by_sport`, `new_arrivals`, `shop_by_category`, `best_choices`, `season_sale`, `make_it_yours`, `design_process`.

- [ ] **Step 1: Replace the order test with the approved contract**

Use these assertions:

```php
public function test_registry_contains_exactly_the_approved_vue_homepage_sections_in_order(): void
{
    $this->assertSame([
        'hero',
        'audience',
        'shop_by_sport',
        'new_arrivals',
        'shop_by_category',
        'best_choices',
        'season_sale',
        'make_it_yours',
        'design_process',
    ], array_column(HomepageSectionRegistry::orderedDefinitions(), 'key'));
}

public function test_legacy_homepage_sections_are_retired(): void
{
    foreach ([
        'slider', 'categories', 'buyer_paths', 'process', 'featured_products',
        'latest_products', 'best_selling_products', 'best_selling_gear',
        'why_choose', 'testimonials', 'faq', 'customization_options', 'support',
        'final_cta', 'popular_categories', 'use_cases', 'design_jersey', 'bulk_order',
    ] as $key) {
        $this->assertTrue(HomepageSectionRegistry::isRetired($key), $key.' must be retired');
    }
}
```

- [ ] **Step 2: Run the unit test and confirm failure**

```bash
php artisan test tests/Unit/HomepageSectionOrderTest.php
```

Expected: FAIL because legacy definitions still exist and ordering differs.

- [ ] **Step 3: Replace `RETIRED_KEYS` with the complete legacy set**

Use:

```php
private const RETIRED_KEYS = [
    'slider',
    'categories',
    'buyer_paths',
    'process',
    'featured_products',
    'latest_products',
    'best_selling_products',
    'best_selling_gear',
    'why_choose',
    'testimonials',
    'faq',
    'customization_options',
    'support',
    'final_cta',
    'popular_categories',
    'use_cases',
    'design_jersey',
    'bulk_order',
];
```

- [ ] **Step 4: Replace `definitions()` with the nine exact definitions**

The definition payloads must have these fixed sort orders and defaults:

```php
[
    'key' => 'hero',
    'name' => 'Hero Banner',
    'component' => 'hero',
    'sort_order' => 10,
    'fields' => ['publishing'],
],
[
    'key' => 'audience',
    'name' => 'Audience Tiles',
    'component' => 'audience',
    'sort_order' => 20,
    'items' => [
        ['id' => 'men', 'title' => 'MEN', 'url' => '/products?q=men', 'image_alt' => 'Men sportswear'],
        ['id' => 'women', 'title' => 'WOMEN', 'url' => '/products?q=women', 'image_alt' => 'Women sportswear'],
        ['id' => 'kids', 'title' => 'KIDS', 'url' => '/products?q=kids', 'image_alt' => 'Kids sportswear'],
    ],
    'fields' => ['items', 'publishing'],
    'item_fields' => ['id', 'title', 'url', 'image_url', 'image_alt'],
],
[
    'key' => 'shop_by_sport',
    'name' => 'Shop By Sport',
    'component' => 'shop_by_sport',
    'sort_order' => 30,
    'title' => 'SHOP BY SPORT',
    'settings' => [
        'default_sport_id' => null,
        'quick_links' => [
            ['id' => 'jersey', 'label' => 'JERSEY', 'url' => '/products?q=jersey'],
            ['id' => 'bottoms', 'label' => 'BOTTOMS', 'url' => '/products?q=bottoms'],
            ['id' => 'uniform-kits', 'label' => 'UNIFORM KITS', 'url' => '/products?q=uniform'],
            ['id' => 'accessories', 'label' => 'ACCESSORIES', 'url' => '/products?q=accessories'],
        ],
    ],
    'fields' => ['text', 'items', 'settings', 'publishing'],
    'item_fields' => ['id', 'category_id', 'title', 'url', 'image_url', 'image_alt'],
],
[
    'key' => 'new_arrivals',
    'name' => 'New Arrivals',
    'component' => 'new_arrivals',
    'sort_order' => 40,
    'title' => 'NEW ARRIVALS',
    'fields' => ['text', 'publishing'],
],
[
    'key' => 'shop_by_category',
    'name' => 'Shop By Category',
    'component' => 'shop_by_category',
    'sort_order' => 50,
    'title' => 'SHOP BY CATEGORY',
    'items' => [],
    'fields' => ['text', 'items', 'publishing'],
    'item_fields' => ['id', 'category_id', 'title', 'url', 'image_url', 'image_alt'],
],
[
    'key' => 'best_choices',
    'name' => 'Best Choices For You',
    'component' => 'best_choices',
    'sort_order' => 60,
    'title' => 'BEST CHOICES FOR YOU',
    'settings' => [
        'tabs' => [
            'featured' => ['label' => 'FEATURED', 'enabled' => true],
            'popular' => ['label' => 'POPULAR', 'enabled' => true],
            'trending' => ['label' => 'TRENDING', 'enabled' => true],
        ],
    ],
    'fields' => ['text', 'settings', 'publishing'],
],
[
    'key' => 'season_sale',
    'name' => 'Season Sale',
    'component' => 'season_sale',
    'sort_order' => 70,
    'title' => 'SEASON SALE',
    'description' => 'UP TO 20% OFF',
    'primary_label' => 'SHOP SALE',
    'primary_url' => '/products',
    'image_alt' => 'NextPlay season sale',
    'fields' => ['text', 'buttons', 'media', 'publishing'],
],
[
    'key' => 'make_it_yours',
    'name' => 'Make It Yours',
    'component' => 'make_it_yours',
    'sort_order' => 80,
    'title' => 'MAKE IT YOURS',
    'primary_label' => 'Explore All',
    'primary_url' => '/products',
    'fields' => ['text', 'buttons', 'publishing'],
],
[
    'key' => 'design_process',
    'name' => 'Design Process',
    'component' => 'design_process',
    'sort_order' => 90,
    'title' => 'HOW TO DESIGN A T-SHIRT USING NEXTPLAY',
    'items' => [
        ['id' => 'choose-product', 'title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
        ['id' => 'share-details', 'title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
        ['id' => 'review-mockup', 'title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
        ['id' => 'confirm-order', 'title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
        ['id' => 'production-shipping', 'title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
    ],
    'fields' => ['text', 'items', 'publishing'],
    'item_fields' => ['id', 'title', 'description', 'image_url', 'image_alt'],
],
```

Preserve the existing `ensureRows()`, `payloadForStorage()`, `definition()`, `orderedDefinitions()` and `mergeForView()` method names; extend them to include `settings` and item `image_path` data.

- [ ] **Step 5: Ensure `payloadForStorage()` and `mergeForView()` include `settings`**

The common merge must include:

```php
'settings' => is_array($section?->settings)
    ? array_replace_recursive((array) ($definition['settings'] ?? []), $section->settings)
    : (array) ($definition['settings'] ?? []),
```

For items, merge by stable `id` where a definition has defaults; do not merge by array index.

- [ ] **Step 6: Run the registry tests**

```bash
php artisan test tests/Unit/HomepageSectionOrderTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Support/HomepageSectionRegistry.php tests/Unit/HomepageSectionOrderTest.php
git commit -m "refactor: align homepage section registry with vue design"
```

---

### Task 3: Migrate reusable legacy homepage data into the new keys

**Files:**
- Create: `database/migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php`
- Extend test: `tests/Feature/Admin/HomepageSectionAdminTest.php`

**Interfaces:**
- Consumes: legacy rows `categories`, `process`, `latest_products`, `slider`; new registry defaults from Task 2.
- Produces: non-destructive new rows for `shop_by_category`, `design_process`, `new_arrivals`, `hero`; old rows remain untouched.

- [ ] **Step 1: Write a failing migration behavior test**

Add a test that seeds legacy rows, runs the migration logic by invoking the migration class via `up()`, and asserts values were copied only into empty target rows. The assertions must include:

```php
$this->assertSame('Legacy Latest Heading', HomepageSection::where('key', 'new_arrivals')->value('title'));
$this->assertSame('Legacy Categories Heading', HomepageSection::where('key', 'shop_by_category')->value('title'));
$this->assertSame('Legacy Process Heading', HomepageSection::where('key', 'design_process')->value('title'));
$this->assertDatabaseHas('homepage_sections', ['key' => 'categories']);
$this->assertDatabaseHas('homepage_sections', ['key' => 'process']);
```

Also add a second case where `new_arrivals` already has `title = 'Admin Custom New Arrivals'` and verify migration does not overwrite it.

- [ ] **Step 2: Run the migration test and confirm failure**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter=legacy
```

Expected: FAIL because no realignment migration exists.

- [ ] **Step 3: Implement the migration using query builder only**

Inside `up()`:

1. Return early if `homepage_sections` does not exist.
2. Read old rows by key.
3. For each new registry definition, insert a row only if missing.
4. Copy reusable values only when the target field is null/blank or the target row was just created.
5. Copy `categories.items` -> `shop_by_category.items`.
6. Copy `process.items` -> `design_process.items`, injecting stable IDs from the five default IDs when absent.
7. Copy `latest_products.title` and `latest_products.is_active` -> `new_arrivals` when the target is not customized.
8. Copy `slider.is_active` -> `hero.is_active` when `hero` is new/unmodified.
9. Never delete legacy rows.

Use `json_encode()` for JSON columns when using `DB::table()`.

`down()` must not delete or overwrite content; it can be a no-op because the migration is a data-preserving compatibility migration and the schema rollback is handled by Task 1.

- [ ] **Step 4: Run the focused test**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter=legacy
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_18_160100_realign_homepage_sections_for_vue_design.php tests/Feature/Admin/HomepageSectionAdminTest.php
git commit -m "feat: migrate legacy homepage settings to new sections"
```

---

### Task 4: Make homepage section validation section-aware

**Files:**
- Modify: `app/Http/Requests/Admin/HomepageSectionRequest.php`
- Extend test: `tests/Feature/Admin/HomepageSectionAdminTest.php`

**Interfaces:**
- Consumes: registry `fields`, `item_fields`, and `settings` definitions.
- Produces: `HomepageSectionRequest::payload(): array` containing clean common fields, `items`, `settings`, fixed registry sort order, and no request-only upload/remove keys.

- [ ] **Step 1: Add failing validation tests**

Add tests for these exact rules:

```text
Audience: exactly 3 items and IDs men/women/kids.
Design Process: maximum 5 items.
Shop By Sport: duplicate category_id rejected.
Shop By Category: duplicate category_id rejected.
Best Choices: at least one of featured/popular/trending enabled.
CTA label requires URL for Season Sale and Make It Yours.
Item image upload accepts jpg/jpeg/png/webp/avif up to 10240 KB.
```

Use `UploadedFile::fake()->image('tile.webp', 1200, 1200)` for item-image validation.

- [ ] **Step 2: Run focused admin request tests**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter=validation
```

Expected: FAIL for the new section-specific rules.

- [ ] **Step 3: Extend common rules with stable item/media/settings fields**

Add rules:

```php
'items.*.id' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
'items.*.image_path' => ['nullable', 'string', 'max:2048'],
'items.*.image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240'],
'items.*.remove_image' => ['nullable', 'boolean'],
'settings' => ['nullable', 'array'],
```

Keep `SafePublicUrl` on every URL field, including `settings.quick_links.*.url` if those are posted directly.

- [ ] **Step 4: Add section-specific rules in `withValidator()`**

Implement key-specific checks:

```php
match ($key) {
    'audience' => $this->validateAudience($validator),
    'shop_by_sport' => $this->validateCategoryItems($validator, 'sport'),
    'shop_by_category' => $this->validateCategoryItems($validator, 'category'),
    'best_choices' => $this->validateBestChoices($validator),
    'design_process' => $this->validateDesignProcess($validator),
    default => null,
};
```

Create private methods with exact behavior:

```php
private function validateAudience(Validator $validator): void
{
    $items = collect((array) $this->input('items', []));
    if ($items->count() !== 3 || $items->pluck('id')->sort()->values()->all() !== ['kids', 'men', 'women']) {
        $validator->errors()->add('items', 'Audience Tiles must contain MEN, WOMEN, and KIDS exactly once.');
    }
}

private function validateDesignProcess(Validator $validator): void
{
    if (count((array) $this->input('items', [])) > 5) {
        $validator->errors()->add('items', 'Design Process supports up to five steps.');
    }
}

private function validateBestChoices(Validator $validator): void
{
    $tabs = (array) $this->input('settings.tabs', []);
    $enabled = collect(['featured', 'popular', 'trending'])
        ->contains(fn (string $key): bool => filter_var($tabs[$key]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN));

    if (! $enabled) {
        $validator->errors()->add('settings.tabs', 'Keep at least one Best Choices tab enabled.');
    }
}
```

- [ ] **Step 5: Normalize payload without request-only fields**

`payload()` must:

```php
$data['items'] = $this->cleanItems((array) $this->input('items', []));
$data['settings'] = $this->cleanSettings((string) $this->route('key'), (array) $this->input('settings', []));
$data['is_active'] = $this->boolean('is_active');
$data['sort_order'] = (int) (HomepageSectionRegistry::definition((string) $this->route('key'))['sort_order'] ?? 0);
```

`cleanItems()` must preserve `id`, `image_path`, `category_id`, and cleaned display fields, but must not persist `image_file` or `remove_image`.

`cleanSettings()` must return only:
- `shop_by_sport.default_sport_id` and exactly four `quick_links` rows with `id`, `label`, `url`.
- `best_choices.tabs.featured|popular|trending` each with `label`, `enabled`.
- empty array for other sections.

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/Admin/HomepageSectionRequest.php tests/Feature/Admin/HomepageSectionAdminTest.php
git commit -m "feat: validate new homepage section contracts"
```

---

### Task 5: Add safe item-level homepage media handling

**Files:**
- Modify: `app/Services/Catalog/HomepageSectionMediaService.php`
- Create: `tests/Feature/Admin/HomepageSectionMediaTest.php`

**Interfaces:**
- Consumes: validated request items with stable `id`, existing section `items`, item `image_file`, `image_url`, `remove_image`.
- Produces: persisted section items with `image_path`/`image_url` and safe cleanup of owned public-disk files.

- [ ] **Step 1: Write four failing media tests**

Use `Storage::fake('public')` and create tests for:

1. Upload item image stores under `homepage/sections/audience/items/men/...` and persists path.
2. Replacing upload deletes previous local file.
3. Setting external `image_url` deletes previous local file and clears `image_path`.
4. Removing an item deletes only the removed item's local image; reordering existing IDs does not delete their files.

Example setup:

```php
Storage::fake('public');

$section = HomepageSection::query()->create([
    'key' => 'audience',
    'name' => 'Audience Tiles',
    'items' => [
        ['id' => 'men', 'title' => 'MEN', 'image_path' => 'homepage/sections/audience/items/men/old.webp'],
        ['id' => 'women', 'title' => 'WOMEN'],
        ['id' => 'kids', 'title' => 'KIDS'],
    ],
    'is_active' => true,
    'sort_order' => 20,
]);

Storage::disk('public')->put('homepage/sections/audience/items/men/old.webp', 'old');
```

- [ ] **Step 2: Run and confirm failure**

```bash
php artisan test tests/Feature/Admin/HomepageSectionMediaTest.php
```

Expected: FAIL because item media is not currently processed.

- [ ] **Step 3: Split the service into explicit responsibilities**

Keep the public entrypoint:

```php
public function sync(HomepageSection $section, Request $request): void
{
    $this->syncSectionMedia($section, $request);
    $this->syncItemMedia($section, $request);
    $section->save();
}
```

`syncSectionMedia()` contains existing section desktop/mobile behavior.

`syncItemMedia()` must:

```php
private function syncItemMedia(HomepageSection $section, Request $request): void
{
    $existing = collect(is_array($section->items) ? $section->items : [])
        ->filter(fn ($item): bool => is_array($item) && filled($item['id'] ?? null))
        ->keyBy(fn (array $item): string => (string) $item['id']);

    $submitted = collect((array) $request->input('items', []))
        ->filter(fn ($item): bool => is_array($item) && filled($item['id'] ?? null));

    $saved = [];
    $seen = [];

    foreach ($submitted as $index => $row) {
        $id = (string) $row['id'];
        $seen[$id] = true;
        $old = (array) ($existing->get($id) ?? []);
        $path = trim((string) ($old['image_path'] ?? '')) ?: null;
        $url = trim((string) ($row['image_url'] ?? $old['image_url'] ?? '')) ?: null;

        if ($request->boolean("items.$index.remove_image")) {
            $this->deleteOwnedItemPath($section, $id, $path);
            $path = null;
            $url = null;
        }

        if ($upload = $request->file("items.$index.image_file")) {
            $this->deleteOwnedItemPath($section, $id, $path);
            $path = $upload->store("homepage/sections/{$section->key}/items/{$id}", 'public');
            $url = null;
        } elseif (filled($row['image_url'] ?? null)) {
            $this->deleteOwnedItemPath($section, $id, $path);
            $path = null;
            $url = trim((string) $row['image_url']);
        }

        $saved[] = array_filter(array_merge($old, $row, [
            'id' => $id,
            'image_path' => $path,
            'image_url' => $url,
        ]), fn ($value): bool => $value !== null && $value !== '');
    }

    $existing->reject(fn (array $item, string $id): bool => isset($seen[$id]))
        ->each(fn (array $item, string $id) => $this->deleteOwnedItemPath($section, $id, $item['image_path'] ?? null));

    $section->items = array_values($saved);
}
```

`deleteOwnedItemPath()` must only delete paths that start with:

```php
$prefix = "homepage/sections/{$section->key}/items/{$itemId}/";
```

and only via `Storage::disk('public')`.

- [ ] **Step 4: Remove legacy section-level `hero_slides` handling from this service**

The new Hero uses `HomepageSlideMediaService`; do not manage section-row `hero_slides` anymore. Preserve the column for rollback compatibility but stop mutating it in `HomepageSectionMediaService`.

- [ ] **Step 5: Run media tests**

```bash
php artisan test tests/Feature/Admin/HomepageSectionMediaTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Catalog/HomepageSectionMediaService.php tests/Feature/Admin/HomepageSectionMediaTest.php
git commit -m "feat: manage homepage item images safely"
```

---

### Task 6: Build the Homepage Control Center and remove obsolete admin navigation

**Files:**
- Modify: `app/Http/Controllers/Admin/HomepageSectionController.php`
- Modify: `resources/views/components/layouts/admin.blade.php`
- Replace: `resources/views/admin/homepage-sections/index.blade.php`
- Replace: `resources/views/admin/homepage-sections/edit.blade.php`
- Create all nine section partials under `resources/views/admin/homepage-sections/partials/`
- Create reusable Blade components under `resources/views/components/admin/homepage/`
- Extend test: `tests/Feature/Admin/HomepageSectionAdminTest.php`

**Interfaces:**
- Consumes: registry definitions, category options, section rows, existing slide permissions/routes.
- Produces: `/admin/homepage` dashboard with exactly nine cards and section-specific editors; Hero card exposes `route('admin.homepage-slides.index')` when permitted.

- [ ] **Step 1: Write failing admin navigation/dashboard tests**

Create tests that authenticate an admin and assert:

```php
$response = $this->actingAs($admin, 'admin')->get(route('admin.homepage.sections.index'));
$response->assertOk()
    ->assertSee('Hero Banner')
    ->assertSee('Audience Tiles')
    ->assertSee('Shop By Sport')
    ->assertSee('New Arrivals')
    ->assertSee('Shop By Category')
    ->assertSee('Best Choices For You')
    ->assertSee('Season Sale')
    ->assertSee('Make It Yours')
    ->assertSee('Design Process')
    ->assertDontSee('Buyer Paths')
    ->assertDontSee('Why Choose Us')
    ->assertDontSee('Testimonials')
    ->assertDontSee('FAQ');
```

Add a layout assertion that the Store sidebar contains `Homepage Controls` and not a generated list of section names or a standalone `Homepage Slider` entry.

- [ ] **Step 2: Run the tests and confirm failure**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php --filter="dashboard|sidebar"
```

Expected: FAIL against the old menu/editor.

- [ ] **Step 3: Simplify the sidebar to one entry**

Replace the existing Homepage group block with:

```blade
@if($canAdmin('homepage_sections.view'))
    <x-admin.sidebar-link
        :href="route('admin.homepage.sections.index')"
        :active="request()->routeIs('admin.homepage.*') || request()->routeIs('admin.homepage-slides.*')"
        icon="▧"
    >Homepage Controls</x-admin.sidebar-link>
@elseif($canAdmin('homepage_slides.view'))
    <x-admin.sidebar-link
        :href="route('admin.homepage-slides.index')"
        :active="request()->routeIs('admin.homepage-slides.*')"
        icon="▧"
    >Homepage Controls</x-admin.sidebar-link>
@endif
```

Do not render `HomepageSectionRegistry::orderedDefinitions()` in the sidebar.

- [ ] **Step 4: Extend controller dashboard data**

`index()` should pass each merged section plus:

```php
[
    'item_count' => count((array) ($merged['items'] ?? [])),
    'thumbnail' => $merged['image'] ?? collect((array) ($merged['items'] ?? []))->pluck('image')->filter()->first(),
    'slide_count' => $key === 'hero' ? HomepageSlide::query()->count() : null,
]
```

Inject/use `HomepageSlide` only for the dashboard count; do not load slides per card.

`edit()` must additionally pass:

```php
'slideCount' => $key === 'hero' ? HomepageSlide::query()->count() : null,
'canManageSlides' => auth('admin')->user()?->canAdmin('homepage_slides.view') ?? false,
```

- [ ] **Step 5: Replace `index.blade.php` with a section dashboard**

The page root must be:

```blade
<div class="np-home-admin">
```

Each section card must render section order, name, visibility status, item count, thumbnail, and `Edit Section`. Hero additionally renders `Manage Slides` only when `canManageSlides` is true.

- [ ] **Step 6: Replace `edit.blade.php` with a section-partial orchestrator**

The form must remain:

```blade
<form method="POST" action="{{ route('admin.homepage.sections.update', $section->key) }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    @include('admin.homepage-sections.partials._'.str_replace('_', '-', $section->key))
    <x-admin.homepage.save-bar />
</form>
```

Use an explicit map instead of dynamic user-controlled paths:

```php
$partial = match ($section->key) {
    'hero' => 'admin.homepage-sections.partials._hero',
    'audience' => 'admin.homepage-sections.partials._audience',
    'shop_by_sport' => 'admin.homepage-sections.partials._shop-by-sport',
    'new_arrivals' => 'admin.homepage-sections.partials._new-arrivals',
    'shop_by_category' => 'admin.homepage-sections.partials._shop-by-category',
    'best_choices' => 'admin.homepage-sections.partials._best-choices',
    'season_sale' => 'admin.homepage-sections.partials._season-sale',
    'make_it_yours' => 'admin.homepage-sections.partials._make-it-yours',
    'design_process' => 'admin.homepage-sections.partials._design-process',
    default => abort(404),
};
```

- [ ] **Step 7: Build section partials with only relevant controls**

Required fields by partial:

```text
_hero: visibility + Manage Slides card/link only.
_audience: 3 stable item cards; title, URL, image upload/URL/alt.
_shop-by-sport: heading, sport item cards with category select/overrides/media, default sport select, four quick link label+URL rows, visibility.
_new-arrivals: heading + visibility.
_shop-by-category: heading, category tile item cards with category select/overrides/media, visibility.
_best-choices: heading, three tab rows label+enabled, visibility.
_season-sale: title, subtitle, CTA label+URL, desktop media, mobile media, alt, visibility.
_make-it-yours: heading, Explore All label+URL, visibility.
_design-process: heading, up to 5 stable step cards with title, description, image upload/URL/alt, visibility.
```

Do not render sort-order inputs in any editor.

- [ ] **Step 8: Update controller save flow ordering**

Because item media needs the original stored paths, call media sync before persisting the cleaned request items, or pass both cleaned payload and request into a service that merges correctly. The concrete controller sequence must be:

```php
DB::transaction(function () use ($request, $homepageSection): void {
    $payload = $request->payload();
    $payload['updated_by'] = $request->user()->id;

    $homepageSection->fill(collect($payload)->except('items')->all());
    $homepageSection->save();

    $this->media->sync($homepageSection, $request);

    $homepageSection->settings = $payload['settings'] ?? [];
    $homepageSection->is_active = (bool) $payload['is_active'];
    $homepageSection->sort_order = (int) $payload['sort_order'];
    $homepageSection->updated_by = $request->user()->id;
    $homepageSection->save();
});
```

The media service is responsible for building persisted `items` so uploaded paths are not overwritten by a later `update($payload)`.

- [ ] **Step 9: Run admin tests**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php tests/Feature/Admin/HomepageSectionMediaTest.php
```

Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/Admin/HomepageSectionController.php resources/views/components/layouts/admin.blade.php resources/views/admin/homepage-sections resources/views/components/admin/homepage tests/Feature/Admin/HomepageSectionAdminTest.php
git commit -m "feat: rebuild homepage controls for vue storefront"
```

---

### Task 7: Add the scoped NextPlay admin-home design system

**Files:**
- Create: `resources/css/admin/homepage.css`
- Modify: `resources/css/admin.css`
- Modify: Homepage Controls Blade components created in Task 6 only where class hooks are needed.
- Create: `tests/ui/homepage-admin-theme-contract.test.mjs`

**Interfaces:**
- Consumes: `.np-home-admin` root from Task 6.
- Produces: scoped Expose/navy/orange design tokens that cannot leak into unrelated admin pages.

- [ ] **Step 1: Write the failing UI contract test**

Create a Node test that reads `resources/css/admin/homepage.css` and asserts these exact tokens:

```js
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../resources/css/admin/homepage.css', import.meta.url), 'utf8');

for (const token of [
  '--np-admin-home-navy: #061f44;',
  '--np-admin-home-orange: #cf5d38;',
  '--np-admin-home-orange-hover: #b94f2f;',
  '--np-admin-home-bg: #f4f6f8;',
  '--np-admin-home-surface: #ffffff;',
  '--np-admin-home-muted: #677386;',
  '--np-admin-home-border: #e1e6eb;',
  "--np-admin-home-font: 'Expose', ui-sans-serif, system-ui, sans-serif;",
]) {
  assert.ok(css.includes(token), `Missing homepage admin token: ${token}`);
}

assert.ok(css.includes('.np-home-admin {'));
console.log('Homepage admin theme contract passed.');
```

- [ ] **Step 2: Run and confirm failure**

```bash
node tests/ui/homepage-admin-theme-contract.test.mjs
```

Expected: FAIL because the stylesheet does not exist.

- [ ] **Step 3: Create the scoped stylesheet**

Start with:

```css
.np-home-admin {
  --np-admin-home-navy: #061f44;
  --np-admin-home-orange: #cf5d38;
  --np-admin-home-orange-hover: #b94f2f;
  --np-admin-home-bg: #f4f6f8;
  --np-admin-home-surface: #ffffff;
  --np-admin-home-muted: #677386;
  --np-admin-home-border: #e1e6eb;
  --np-admin-home-font: 'Expose', ui-sans-serif, system-ui, sans-serif;

  font-family: var(--np-admin-home-font);
  color: var(--np-admin-home-navy);
}

.np-home-admin .np-home-admin__panel {
  border: 1px solid var(--np-admin-home-border);
  background: var(--np-admin-home-surface);
}

.np-home-admin .np-home-admin__primary {
  background: var(--np-admin-home-orange);
  color: #fff;
}

.np-home-admin .np-home-admin__primary:hover {
  background: var(--np-admin-home-orange-hover);
}

.np-home-admin .np-home-admin__muted {
  color: var(--np-admin-home-muted);
}
```

Add focused classes for section cards, inputs, upload previews, item cards, visibility badges, and sticky save bar. Keep every selector under `.np-home-admin`.

- [ ] **Step 4: Import from `admin.css`**

Immediately after pagination import:

```css
@import './homepage.css';
```

Use the correct relative path if the file is placed under `resources/css/admin/homepage.css`:

```css
@import './admin/homepage.css';
```

- [ ] **Step 5: Run UI contract test**

```bash
node tests/ui/homepage-admin-theme-contract.test.mjs
```

Expected: PASS.

- [ ] **Step 6: Run CSS/Vite build if dependencies are installed**

```bash
npm run build
```

Expected: exit 0. If `node_modules` is absent in an archive-only environment, record that fact and rely on the Node contract tests until execution in the real repo.

- [ ] **Step 7: Commit**

```bash
git add resources/css/admin.css resources/css/admin/homepage.css resources/views/admin/homepage-sections resources/views/components/admin/homepage tests/ui/homepage-admin-theme-contract.test.mjs
git commit -m "style: add scoped nextplay homepage admin theme"
```

---

### Task 8: Update the homepage API/service contract and cache behavior

**Files:**
- Modify: `app/Services/Storefront/HomepageSectionService.php`
- Modify: `app/Services/Storefront/HomePageService.php`
- Modify: `app/Http/Resources/Api/V1/Storefront/HomepageResource.php`
- Modify: `tests/Feature/Api/V1/Storefront/HomepageApiTest.php`
- Modify: `tests/Feature/HomepageProductCollectionsTest.php` only where old payload keys are asserted.

**Interfaces:**
- Consumes: exact nine-section registry output, catalog product/category/sport services, existing hero slide service.
- Produces: `/api/v1/home` with `slides`, `sections`, `categories`, `featured_products`, `latest_products`, `best_selling_products`, `sports`, navigation and menus; removes legacy `buyer_paths`, `best_selling_gear_categories`, `process_steps`, `faqs` from the public contract once Vue no longer needs them.

- [ ] **Step 1: Rewrite the API test payload to the new explicit contract**

Use a section sample containing settings and item image:

```php
'homeSections' => [[
    'key' => 'season_sale',
    'component' => 'season_sale',
    'title' => 'SEASON SALE',
    'description' => 'UP TO 20% OFF',
    'settings' => [],
    'items' => [],
    'image' => '/storage/homepage/sections/season_sale/banner.webp',
    'is_active' => true,
]],
```

Assert:

```php
$response->assertJsonPath('data.sections.0.key', 'season_sale')
    ->assertJsonPath('data.sections.0.title', 'SEASON SALE')
    ->assertJsonPath('data.sections.0.image', '/storage/homepage/sections/season_sale/banner.webp');

foreach (['buyer_paths', 'best_selling_gear_categories', 'process_steps', 'faqs'] as $legacyKey) {
    $this->assertArrayNotHasKey($legacyKey, $response->json('data'));
}
```

Keep product-feed assertions to prove they remain unchanged.

- [ ] **Step 2: Run the API test and confirm failure**

```bash
php artisan test tests/Feature/Api/V1/Storefront/HomepageApiTest.php
```

Expected: FAIL because the resource still exposes legacy keys.

- [ ] **Step 3: Bump the section cache key**

Change:

```php
private const CACHE_KEY = 'storefront.homepage-sections.v7';
```

- [ ] **Step 4: Simplify `HomepageResource` to the public keys still consumed**

Return exactly:

```php
return [
    'seo' => (array) ($this->resource['seo'] ?? []),
    'slides' => array_values((array) ($this->resource['slides'] ?? [])),
    'sections' => array_values((array) ($this->resource['homeSections'] ?? [])),
    'categories' => array_values((array) ($this->resource['categories'] ?? [])),
    'featured_products' => array_values((array) ($this->resource['featuredProducts'] ?? [])),
    'latest_products' => array_values((array) ($this->resource['latestProducts'] ?? [])),
    'latest_products_signature' => (string) ($this->resource['latestProductsSignature'] ?? ''),
    'best_selling_products' => array_values((array) ($this->resource['bestSellingProducts'] ?? [])),
    'sports' => array_values((array) ($this->resource['sports'] ?? [])),
    'navigation' => $this->navigationItems($this->resource['navigation'] ?? [], $request),
    'menus' => $this->menus($this->resource['storefrontMenus'] ?? [], $request),
];
```

- [ ] **Step 5: Simplify `HomePageService` data composition**

Keep service keys matching the resource above. Remove buyer paths, old FAQ, old best-selling-gear category payload, and hardcoded `processSteps`; Design Process now comes from `sections.design_process.items`.

Do not change `featuredProducts`, `latestProducts`, `bestSellingProducts`, `categories`, `sports`, navigation, or menus logic.

- [ ] **Step 6: Ensure media URLs are resolved in `HomepageSectionRegistry::mergeForView()`**

Every item returned to Vue should expose public `image`, not require raw storage path handling. Add `use Illuminate\Support\Facades\Storage;` and this private helper to the registry:

```php
private static function publicImage(?string $path, ?string $url): ?string
{
    $path = trim((string) $path);
    if ($path !== '') {
        return Storage::disk('public')->url($path);
    }

    $url = trim((string) $url);

    return $url !== '' ? $url : null;
}
```

When mapping each section item in `mergeForView()`, set:

```php
$item['image'] = self::publicImage(
    isset($item['image_path']) ? (string) $item['image_path'] : null,
    isset($item['image_url']) ? (string) $item['image_url'] : null,
);
```

Use the same helper for section desktop/mobile image resolution where those fields are returned as `image` and `mobile_image`.

- [ ] **Step 7: Run API and product collection tests**

```bash
php artisan test tests/Feature/Api/V1/Storefront/HomepageApiTest.php tests/Feature/HomepageProductCollectionsTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Storefront/HomepageSectionService.php app/Services/Storefront/HomePageService.php app/Http/Resources/Api/V1/Storefront/HomepageResource.php app/Support/HomepageSectionRegistry.php tests/Feature/Api/V1/Storefront/HomepageApiTest.php tests/Feature/HomepageProductCollectionsTest.php
git commit -m "refactor: expose vue homepage cms contract"
```

---

### Task 9: Add typed homepage CMS contracts and normalization helpers in Vue

**Files:**
- Modify: `resources/js/storefront/features/home/types/home.types.ts`
- Create: `resources/js/storefront/features/home/utils/homeSection.ts`
- Modify: `resources/js/storefront/features/home/composables/useHome.ts`
- Create: `tests/frontend/homepage-cms-contract.test.mjs`

**Interfaces:**
- Consumes: `/api/v1/home` sections from Task 8.
- Produces: typed helpers `sectionIsActive()`, `sectionTitle()`, `sectionItems<T>()`, `sectionSettings<T>()` used by all homepage components.

- [ ] **Step 1: Write failing frontend contract assertions**

The test must assert `home.types.ts` includes `settings?: Record<string, unknown>` and that `HomePageData` no longer declares `buyer_paths`, `process_steps`, `faqs`, or `best_selling_gear_categories`.

It must assert `homeSection.ts` exports:

```text
sectionIsActive
sectionTitle
sectionItems
sectionSettings
```

- [ ] **Step 2: Run and confirm failure**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
```

Expected: FAIL until types/helper exist.

- [ ] **Step 3: Define the typed interfaces**

Use:

```ts
export interface HomeSectionItem {
  id?: string;
  title?: string;
  description?: string;
  url?: string;
  label?: string;
  category_id?: number;
  image?: string | null;
  image_url?: string | null;
  image_alt?: string | null;
}

export interface HomeAudienceItem extends HomeSectionItem {
  id: 'men' | 'women' | 'kids';
  title: string;
  url: string;
}

export interface HomeSportItem extends HomeSectionItem {
  id: string;
  category_id?: number;
}

export interface HomeCategoryItem extends HomeSectionItem {
  id: string;
  category_id?: number;
}

export interface HomeDesignProcessItem extends HomeSectionItem {
  id: string;
  title: string;
  description: string;
}

export interface HomeBestChoicesSettings {
  tabs: {
    featured: { label: string; enabled: boolean };
    popular: { label: string; enabled: boolean };
    trending: { label: string; enabled: boolean };
  };
}

export interface HomeShopBySportSettings {
  default_sport_id?: number | null;
  quick_links: Array<{ id: string; label: string; url: string }>;
}
```

Extend `HomeSection` with:

```ts
settings?: Record<string, unknown>;
is_active?: boolean;
items?: HomeSectionItem[];
```

Remove obsolete `HomeFaq` and obsolete payload properties from `HomePageData`.

- [ ] **Step 4: Add normalization helpers**

Create:

```ts
import type { HomeSection, HomeSectionItem } from '../types/home.types';

export function sectionIsActive(section?: HomeSection): boolean {
  return section?.is_active !== false;
}

export function sectionTitle(section: HomeSection | undefined, fallback: string): string {
  const value = String(section?.title ?? '').trim();
  return value || fallback;
}

export function sectionItems<T extends HomeSectionItem>(section?: HomeSection): T[] {
  return Array.isArray(section?.items) ? section!.items as T[] : [];
}

export function sectionSettings<T extends Record<string, unknown>>(section: HomeSection | undefined, fallback: T): T {
  return { ...fallback, ...((section?.settings ?? {}) as Partial<T>) } as T;
}
```

- [ ] **Step 5: Keep `useHome()` as query state only**

No Pinia changes. Preserve `sectionsByKey` and return shape.

- [ ] **Step 6: Run frontend contract test and typecheck**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
npm run typecheck
```

Expected: PASS in a dependency-installed repo.

- [ ] **Step 7: Commit**

```bash
git add resources/js/storefront/features/home/types/home.types.ts resources/js/storefront/features/home/utils/homeSection.ts resources/js/storefront/features/home/composables/useHome.ts tests/frontend/homepage-cms-contract.test.mjs
git commit -m "feat: type homepage cms configuration"
```

---

### Task 10: Wire Hero, Audience, Shop By Sport, New Arrivals, and Shop By Category to CMS data

**Files:**
- Modify: `resources/js/storefront/features/home/pages/HomePage.vue`
- Modify: `resources/js/storefront/features/home/components/HomeHero.vue`
- Modify: `resources/js/storefront/features/home/components/AudienceTiles.vue`
- Modify: `resources/js/storefront/features/home/components/ShopBySport.vue`
- Modify: `resources/js/storefront/features/home/components/NewArrivals.vue`
- Modify: `resources/js/storefront/features/home/components/ShopByCategory.vue`
- Extend: `tests/frontend/homepage-cms-contract.test.mjs`

**Interfaces:**
- Consumes: typed `HomeSection` data/helpers from Task 9, existing product/category/sport props.
- Produces: no hardcoded editable content for these five sections; inactive sections do not render.

- [ ] **Step 1: Add failing static-contract assertions**

Assert the following literals are no longer the sole source of rendered content:

```text
AudienceTiles.vue: hardcoded MEN/WOMEN/KIDS array removed.
ShopBySport.vue: hardcoded four quick-links array removed.
NewArrivals.vue: title prop uses CMS fallback instead of literal-only title.
ShopByCategory.vue: title and tile definitions come from section items.
HomePage.vue: each section component receives `section=` and is gated by `sectionIsActive`.
```

- [ ] **Step 2: Run and confirm failure**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
```

Expected: FAIL.

- [ ] **Step 3: Update component prop contracts**

Use these exact additional props:

```ts
section?: HomeSection;
```

for Audience, Shop By Sport, New Arrivals, Shop By Category. `HomeHero` already accepts section; use it only for visibility while slides remain authoritative.

- [ ] **Step 4: Audience normalization**

Build tiles from CMS first, then exact approved fallbacks:

```ts
const fallbackAudience: HomeAudienceItem[] = [
  { id: 'men', title: 'MEN', url: '/products?q=men' },
  { id: 'women', title: 'WOMEN', url: '/products?q=women' },
  { id: 'kids', title: 'KIDS', url: '/products?q=kids' },
];

const tiles = computed(() => {
  const configured = sectionItems<HomeAudienceItem>(props.section);
  return fallbackAudience.map((fallback) => configured.find((item) => item.id === fallback.id) ?? fallback);
});
```

Use item `image` override first, category fallback second, current static fallback last.

- [ ] **Step 5: Shop By Sport normalization**

Read heading with `sectionTitle(section, 'SHOP BY SPORT')`.
Read settings with:

```ts
const defaultSettings: HomeShopBySportSettings = {
  default_sport_id: null,
  quick_links: [
    { id: 'jersey', label: 'JERSEY', url: '/products?q=jersey' },
    { id: 'bottoms', label: 'BOTTOMS', url: '/products?q=bottoms' },
    { id: 'uniform-kits', label: 'UNIFORM KITS', url: '/products?q=uniform' },
    { id: 'accessories', label: 'ACCESSORIES', url: '/products?q=accessories' },
  ],
};
```

Map configured sport `category_id` to `props.sports`; apply optional title/url/image overrides.

- [ ] **Step 6: New Arrivals and Shop By Category**

New Arrivals keeps products exactly as-is and only makes the section title configurable.

Shop By Category maps configured `category_id` rows against `props.categories`, preserving configured item order and applying optional `title`, `url`, `image`, `image_alt` overrides. If CMS has no valid items, use the current category fallback logic.

- [ ] **Step 7: Gate rendering in `HomePage.vue` without changing order**

Add:

```ts
const section = (key: string) => home.sectionsByKey.value[key];
```

Render in the fixed approved order with `v-if="sectionIsActive(section('...'))"`. Pass the corresponding section prop to each component.

- [ ] **Step 8: Run frontend tests**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
npm run typecheck
```

Then run the existing homepage fidelity tests:

```bash
for f in tests/frontend/homepage-*.test.mjs tests/ui/*.mjs; do node "$f"; done
```

Expected: no new visual-contract failures.

- [ ] **Step 9: Commit**

```bash
git add resources/js/storefront/features/home/pages/HomePage.vue resources/js/storefront/features/home/components/HomeHero.vue resources/js/storefront/features/home/components/AudienceTiles.vue resources/js/storefront/features/home/components/ShopBySport.vue resources/js/storefront/features/home/components/NewArrivals.vue resources/js/storefront/features/home/components/ShopByCategory.vue tests/frontend/homepage-cms-contract.test.mjs
git commit -m "feat: connect primary homepage sections to cms"
```

---

### Task 11: Wire Best Choices, Season Sale, Make It Yours, and Design Process to CMS data

**Files:**
- Modify: `resources/js/storefront/features/home/components/BestChoices.vue`
- Modify: `resources/js/storefront/features/home/components/SeasonSaleBanner.vue`
- Modify: `resources/js/storefront/features/home/components/MakeItYours.vue`
- Modify: `resources/js/storefront/features/home/components/DesignProcess.vue`
- Modify: `resources/js/storefront/features/home/components/ProcessStepCard.vue`
- Modify: `resources/js/storefront/features/home/pages/HomePage.vue`
- Extend: `tests/frontend/homepage-cms-contract.test.mjs`

**Interfaces:**
- Consumes: `HomeBestChoicesSettings`, CMS Season Sale media/text, CMS Design Process items.
- Produces: no hardcoded editable text/images in the remaining four sections; product cards still use catalog feeds.

- [ ] **Step 1: Add failing static contract checks**

Assert:

```text
BestChoices.vue does not hardcode rendered FEATURED/POPULAR/TRENDING labels without CMS fallback logic.
SeasonSaleBanner.vue no longer selects `slides[1]` as its image source.
MakeItYours.vue uses section title/link props.
DesignProcess.vue does not consume `process_steps` payload and uses section.items.
ProcessStepCard.vue accepts configured image/alt.
```

- [ ] **Step 2: Run and confirm failure**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
```

Expected: FAIL.

- [ ] **Step 3: Best Choices settings**

Add `section?: HomeSection` and derive:

```ts
const settings = computed(() => sectionSettings<HomeBestChoicesSettings>(props.section, {
  tabs: {
    featured: { label: 'FEATURED', enabled: true },
    popular: { label: 'POPULAR', enabled: true },
    trending: { label: 'TRENDING', enabled: true },
  },
}));

const enabledTabs = computed(() => (['featured', 'popular', 'trending'] as const)
  .filter((key) => settings.value.tabs[key]?.enabled));
```

If the current selected tab becomes disabled, select the first enabled tab. Keep product-feed mapping: featured -> featured, popular -> best selling, trending -> latest feed passed as a new `trending` prop.

- [ ] **Step 4: Season Sale must be fully section-owned**

Change props to only:

```ts
defineProps<{ section?: HomeSection }>();
```

Derive:

```ts
const background = computed(() => props.section?.image || '/storage/storefront/home/hero-slide-real-team-gear.webp');
const mobileBackground = computed(() => props.section?.mobile_image || background.value);
```

Render `sectionTitle(section, 'SEASON SALE')`, description fallback `UP TO 20% OFF`, primary label fallback `SHOP SALE`, primary URL fallback `/products`, and configured alt fallback `Season sale`.

Do not read hero slides.

- [ ] **Step 5: Make It Yours**

Add `section?: HomeSection`; keep product filtering unchanged. Use CMS/fallback values for title, Explore All label, Explore All URL.

- [ ] **Step 6: Design Process and step images**

Change `DesignProcess` props to:

```ts
defineProps<{ section?: HomeSection; previewProduct?: StorefrontProduct | null }>();
```

Build steps from `sectionItems<HomeDesignProcessItem>(section)` with the five exact registry fallback steps. Pass each item image and alt to `ProcessStepCard`.

`ProcessStepCard` must add props:

```ts
image?: string | null;
imageAlt?: string | null;
```

Rendering priority:
1. configured CMS image,
2. first-step product preview if no configured image,
3. existing visual placeholder fallback.

- [ ] **Step 7: Update `HomePage.vue` props**

Pass:

```vue
<BestChoices :section="section('best_choices')" :featured="featuredProducts" :best-selling="bestSellingProducts" :trending="latestProducts" ... />
<SeasonSaleBanner :section="section('season_sale')" />
<MakeItYours :section="section('make_it_yours')" ... />
<DesignProcess :section="section('design_process')" :preview-product="latestProducts[0] || null" />
```

Remove `home.data.value.process_steps` usage completely.

- [ ] **Step 8: Run frontend contract/type/fidelity tests**

```bash
node tests/frontend/homepage-cms-contract.test.mjs
npm run typecheck
for f in tests/frontend/homepage-*.test.mjs tests/ui/*.mjs; do node "$f"; done
```

Expected: no new failures.

- [ ] **Step 9: Commit**

```bash
git add resources/js/storefront/features/home/components/BestChoices.vue resources/js/storefront/features/home/components/SeasonSaleBanner.vue resources/js/storefront/features/home/components/MakeItYours.vue resources/js/storefront/features/home/components/DesignProcess.vue resources/js/storefront/features/home/components/ProcessStepCard.vue resources/js/storefront/features/home/pages/HomePage.vue tests/frontend/homepage-cms-contract.test.mjs
git commit -m "feat: connect remaining homepage sections to cms"
```

---

### Task 12: Integrate Hero slide management into Homepage Controls without duplicating navigation

**Files:**
- Modify: `resources/views/admin/homepage-sections/partials/_hero.blade.php`
- Modify: `resources/views/admin/homepage-slides/index.blade.php`
- Modify: `resources/views/admin/homepage-slides/_form.blade.php`
- Modify: `app/Http/Controllers/Admin/HomepageSlideController.php` only if redirect targets need Homepage Controls breadcrumbs.
- Modify: `tests/Feature/HomepageSliderTest.php`

**Interfaces:**
- Consumes: existing `HomepageSlideController`, `HomepageSlideMediaService`, permissions and routes.
- Produces: Hero Banner editor as the single admin entry point, while existing slide CRUD remains intact.

- [ ] **Step 1: Add failing navigation/integration tests**

Assert an authorized admin visiting Hero Banner sees `Manage Slides`, and the slide index includes a `Back to Hero Banner` link to:

```php
route('admin.homepage.sections.edit', 'hero')
```

Assert the standalone sidebar label `Homepage Slider` is absent.

- [ ] **Step 2: Run and confirm failure**

```bash
php artisan test tests/Feature/HomepageSliderTest.php
```

Expected: FAIL on the new admin navigation assertions.

- [ ] **Step 3: Add Hero Banner manage-slides card**

In `_hero.blade.php`, render slide count and:

```blade
@if($canManageSlides)
    <a class="np-home-admin__primary" href="{{ route('admin.homepage-slides.index') }}">Manage Slides</a>
@endif
```

Do not reproduce slide CRUD inside the section editor.

- [ ] **Step 4: Add Homepage Controls breadcrumb/back link to slide screens**

Use the existing slide CRUD views, wrapped with the scoped `.np-home-admin` class so only these pages inherit the new Homepage Controls theme when managed as hero content.

- [ ] **Step 5: Ensure all slide mutations continue to flush slider cache**

Verify existing create/update/delete/toggle methods call `HomepageSliderService::flushCache()`; if any mutation does not, add it and cover it with a test.

- [ ] **Step 6: Run slider tests**

```bash
php artisan test tests/Feature/HomepageSliderTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/views/admin/homepage-sections/partials/_hero.blade.php resources/views/admin/homepage-slides app/Http/Controllers/Admin/HomepageSlideController.php tests/Feature/HomepageSliderTest.php
git commit -m "feat: manage hero slides from homepage controls"
```

---

### Task 13: Verify cache invalidation after section and media changes

**Files:**
- Modify: `app/Http/Controllers/Admin/HomepageSectionController.php` — keep `flushCache()` after successful updates and do not move it inside the transaction.
- Extend: `tests/Feature/Admin/HomepageSectionAdminTest.php`
- Extend: `tests/Feature/Admin/HomepageSectionMediaTest.php`

**Interfaces:**
- Consumes: `HomepageSectionController::update()`, `HomepageSectionService::flushCache()` and hero slide cache behavior.
- Produces: immediate storefront visibility of admin changes without waiting for cache expiry.

- [ ] **Step 1: Add failing cache tests**

Test sequence:

```php
$service = app(HomepageSectionService::class);
$before = $service->sections();

$this->actingAs($admin, 'admin')->patch(route('admin.homepage.sections.update', 'new_arrivals'), [
    'title' => 'LATEST TEAM GEAR',
    'is_active' => '1',
]);

$after = app(HomepageSectionService::class)->sections();
$this->assertSame('LATEST TEAM GEAR', collect($after)->firstWhere('key', 'new_arrivals')['title']);
```

Also cover an item image change and confirm the new image URL is returned after the update.

- [ ] **Step 2: Run and confirm failure if cache is stale**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php tests/Feature/Admin/HomepageSectionMediaTest.php --filter=cache
```

- [ ] **Step 3: Ensure `flushCache()` runs after the transaction and runtime cache is cleared**

Keep:

```php
$this->sections->flushCache();
```

outside the transaction after a successful update. The cache key is already bumped to v7 in Task 8.

- [ ] **Step 4: Run cache tests**

```bash
php artisan test tests/Feature/Admin/HomepageSectionAdminTest.php tests/Feature/Admin/HomepageSectionMediaTest.php --filter=cache
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Storefront/HomepageSectionService.php app/Http/Controllers/Admin/HomepageSectionController.php tests/Feature/Admin/HomepageSectionAdminTest.php tests/Feature/Admin/HomepageSectionMediaTest.php
git commit -m "test: verify homepage cms cache invalidation"
```

---

### Task 14: Full regression, compatibility, and rollout verification

**Files:**
- Modify only failing tests that encode intentionally retired old homepage contracts; do not weaken visual-fidelity tests.
- Review all files changed by Tasks 1-13.

**Interfaces:**
- Consumes: completed backend, admin, API and Vue implementation.
- Produces: deployable homepage CMS realignment with no new unrelated regressions.

- [ ] **Step 1: Run migrations on a test database**

```bash
php artisan migrate:fresh --env=testing
```

Expected: all migrations complete successfully, including `settings` and realignment migrations.

- [ ] **Step 2: Run focused backend suites**

```bash
php artisan test \
  tests/Unit/HomepageSectionOrderTest.php \
  tests/Feature/Admin/HomepageSectionAdminTest.php \
  tests/Feature/Admin/HomepageSectionMediaTest.php \
  tests/Feature/Api/V1/Storefront/HomepageApiTest.php \
  tests/Feature/HomepageSliderTest.php \
  tests/Feature/HomepageProductCollectionsTest.php \
  tests/Feature/VueHomepageCutoverTest.php
```

Expected: PASS.

- [ ] **Step 3: Run all frontend contract and visual tests**

```bash
for f in tests/frontend/*.mjs tests/ui/*.mjs; do
  node "$f" || exit 1
done
```

Expected: PASS except any known pre-existing failures reproduced unchanged in the approved base archive; document exact filenames if such failures remain.

- [ ] **Step 4: Run TypeScript and production build**

```bash
npm run typecheck
npm run build
```

Expected: exit 0 in the dependency-installed project.

- [ ] **Step 5: Run the PHP suite**

```bash
php artisan test
```

Expected: no new failures caused by Homepage Controls. If unrelated pre-existing failures exist, reproduce them on the untouched base before classifying them as pre-existing.

- [ ] **Step 6: Manual admin acceptance checklist**

Verify in browser:

```text
/admin/homepage shows exactly 9 section cards.
No Buyer Paths/Why Choose Us/Testimonials/FAQ/legacy product-section controls appear.
No standalone Homepage Slider sidebar entry appears.
Hero Banner -> Manage Slides opens existing slide manager.
Audience MEN/WOMEN/KIDS text, links and images can be changed.
Shop By Sport heading, selected sports, default sport, quick-link labels/URLs and images can be changed.
New Arrivals heading can be changed; products remain catalog-driven.
Shop By Category heading/tiles/images can be changed.
Best Choices heading/tab labels/visibility can be changed; products remain catalog-driven.
Season Sale text/CTA/desktop/mobile image can be changed.
Make It Yours heading/Explore All link can be changed; products remain catalog-driven.
Design Process heading, 5 step texts and images can be changed.
Disabling any section removes it from the Vue homepage without changing section order.
Replacing/removing item images cleans only owned files.
Unrelated admin pages retain their old styling.
```

- [ ] **Step 7: Manual storefront fidelity checklist**

Verify desktop/tablet/mobile for:

```text
No homepage dimensions changed unexpectedly.
Product cards still use Product catalog images/data.
Header, mega menu, footer are unchanged.
Existing hover states are unchanged.
Homepage typography/colors/spacing match the approved current design.
Season Sale no longer changes when hero slide 2 changes.
Design Process uses configured images when provided and safe fallbacks otherwise.
```

- [ ] **Step 8: Commit final compatibility cleanup**

```bash
git add app database resources routes tests
git commit -m "test: complete homepage cms regression coverage"
```

---

## Self-Review Results

- **Spec coverage:** All 23 spec sections are represented: nine-section registry, legacy retirement/migration, `settings`, item media, validation, admin dashboard/editors, scoped admin design system, API contract, HomePageService simplification, Vue wiring, type safety, cache invalidation, permissions/hero integration, compatibility, and tests.
- **Scope:** The plan does not convert unrelated admin pages, global header/footer content management, product data, checkout, FlowTrack, or the legacy storefront.
- **Type consistency:** `HomeSection.settings`, `HomeSectionItem`, `HomeBestChoicesSettings`, and `HomeShopBySportSettings` are defined in Task 9 before component consumers in Tasks 10-11.
- **Media consistency:** item ownership uses stable string IDs and `homepage/sections/{section-key}/items/{item-id}/...` across request, service, API, and Vue tasks.
- **No destructive legacy cleanup:** retired rows remain in the database; only admin/API visibility changes.
- **No product duplication:** all product carousel sections continue to consume Laravel catalog feeds.
