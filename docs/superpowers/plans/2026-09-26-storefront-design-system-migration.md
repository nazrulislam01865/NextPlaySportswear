# Storefront Design System Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate every customer-facing NextPlay storefront surface to the supplied Expose typography system and centralized navy/orange brand palette without changing storefront layouts, backend behavior, or admin styling.

**Architecture:** Keep `resources/css/storefront-theme.css` as the single source of truth for font, color, typography, and button tokens. Self-host Expose through Vite, map Tailwind aliases to the same runtime tokens, and remove active page/component-level brand/font/button identity overrides so existing storefront components inherit the shared system. Preserve existing class names as compatibility aliases where that avoids unnecessary Blade churn.

**Tech Stack:** Laravel Blade, Tailwind CSS 3, handcrafted CSS, Vite 8, PHPUnit/Laravel feature tests.

**Spec:** `docs/superpowers/specs/2026-09-26-storefront-design-system-migration-design.md`

## Global Constraints

- Primary navy is exactly `#061F44`.
- Secondary orange is exactly `#CF5D38`.
- Storefront font family is self-hosted `Expose`; no Google Fonts or external font CDN.
- Required Expose weights: 400 Regular, 500 Medium, 700 Bold, 900 Black.
- Canonical typography: Title/1 48/900/115%, Title/2 32/900/115%, Title/3 24/900/120%, Title/4 16/900/130%, Body/1 18/400/140%, Body/2 14/400/140%, Tag 16/500/140%, CTA All Caps Large 18/700/100%, CTA All Caps Regular 16/700/100%, CTA Normal Large 18/400/100%, CTA Normal Regular 16/400/100%, CTA Normal Small 14/500/100%; all letter-spacing `0`.
- `resources/css/storefront-theme.css` remains the single source of truth; do not add a parallel theme stylesheet.
- The existing `.btn` system remains the only storefront button foundation.
- Page/component CSS may control layout and width but must not redefine button brand identity, font family, or canonical theme colors.
- Preserve current responsive layouts and semantic heading markup.
- Admin CSS/layouts and backend business logic are out of scope.
- No database migration.

## Review Focus

- A legacy storefront class such as `text-brand-red` or `btn-red` must still render from the new orange/navy token mapping instead of retaining a legacy red hex.
- A page-level anchor or wrapper selector must not override `.btn` text color and make button labels invisible on white/light backgrounds.
- Error/status pages that can render outside the normal storefront layout must still use Expose and the new brand colors without depending on Google Fonts.
- Product-card badge/accent styles that currently consume `--np-color-orange` must follow the new `#CF5D38` token without reintroducing the prior badge regression.
- Mobile/responsive headings and buttons must retain existing placement and touch sizing while using the new semantic type tokens.

---

### Task 1: Self-host Expose and lock the central brand/type tokens

**Files:**
- Create: `resources/fonts/expose/Expose-Regular.woff2`
- Create: `resources/fonts/expose/Expose-Medium.woff2`
- Create: `resources/fonts/expose/Expose-Bold.woff2`
- Create: `resources/fonts/expose/Expose-Black.woff2`
- Modify: `resources/css/storefront-theme.css`
- Modify: `resources/views/components/storefront/font-assets.blade.php`
- Modify: `resources/views/components/layouts/storefront.blade.php`
- Create: `tests/Feature/StorefrontDesignSystemMigrationTest.php`

**Interfaces:**
- Consumes: WOFF2 files from the supplied `Expose_Complete.zip`.
- Produces: central CSS variables `--np-font-body`, `--np-font-heading`, brand color tokens, semantic title/body/tag/CTA tokens, and local `@font-face` definitions consumed by all later tasks.

- [ ] **Step 1: Write failing tests for the supplied brand and font foundation**

Add `StorefrontDesignSystemMigrationTest` assertions that:

```php
$this->assertStringContainsString('--np-color-primary: #061F44;', $themeCss);
$this->assertStringContainsString('--np-color-secondary: #CF5D38;', $themeCss);
$this->assertStringContainsString('--np-color-orange: #CF5D38;', $themeCss);
$this->assertStringContainsString('--np-font-body: "Expose"', $themeCss);
$this->assertStringContainsString('--np-title-1-size: 48px;', $themeCss);
$this->assertStringContainsString('--np-body-1-size: 18px;', $themeCss);
$this->assertStringContainsString('--np-cta-all-caps-regular-size: 16px;', $themeCss);
$this->assertStringNotContainsString('fonts.googleapis.com', $fontAssets);
$this->assertStringContainsString('content="#061F44"', $storefrontLayout);
```

Also assert the four required `.woff2` files exist under `resources/fonts/expose`.

- [ ] **Step 2: Run the focused test and verify it fails for the current Inter/Oswald + legacy palette**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php`

Expected: FAIL because the current tokens are legacy colors/fonts, Google Fonts are still referenced, and local Expose files are absent.

- [ ] **Step 3: Copy only the four required WOFF2 assets from the supplied font archive**

Copy Regular, Medium, Bold, and Black into `resources/fonts/expose/`. Do not add TTF/OTF/EOT/WOFF duplicates or the variable font.

- [ ] **Step 4: Replace the storefront font loader with local `@font-face` definitions**

In `resources/views/components/storefront/font-assets.blade.php`, remove Google preconnect/stylesheet links and define one `Expose` family with weights 400, 500, 700, and 900 using Vite-resolved local asset URLs and `font-display: swap`.

- [ ] **Step 5: Replace the legacy palette and typography values in `storefront-theme.css`**

Define the exact approved navy/orange tokens and semantic type tokens from Global Constraints. Keep compatibility aliases such as `--np-color-primary`, `--np-color-navy`, `--np-color-orange`, and existing generic text/card tokens, but point them at the new system. Both `--np-font-body` and `--np-font-heading` resolve to `"Expose"` with system fallback only.

- [ ] **Step 6: Update browser theme metadata**

Change `resources/views/components/layouts/storefront.blade.php` theme color from the legacy navy to `#061F44`.

- [ ] **Step 7: Run the focused design-system test**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php`

Expected: PASS for font files, local font loading, core brand tokens, canonical typography values, and theme-color metadata.

- [ ] **Step 8: Commit**

```bash
git add resources/fonts/expose resources/css/storefront-theme.css resources/views/components/storefront/font-assets.blade.php resources/views/components/layouts/storefront.blade.php tests/Feature/StorefrontDesignSystemMigrationTest.php
git commit -m "feat: centralize storefront brand and Expose typography"
```

### Task 2: Remap Tailwind and the shared button system to the approved tokens

**Files:**
- Modify: `tailwind.config.js`
- Modify: `resources/css/storefront.css`
- Modify: `tests/Feature/StorefrontDesignSystemMigrationTest.php`
- Modify: `tests/Feature/StorefrontButtonCascadeTest.php`

**Interfaces:**
- Consumes: color/type/button tokens from Task 1.
- Produces: compatibility Tailwind utilities and canonical `.btn` variants that every storefront page can safely consume.

- [ ] **Step 1: Add failing tests for Tailwind aliases and canonical button variants**

Assert that `tailwind.config.js` still maps `font-sans`/`font-display` and legacy `brand.red`/`brand.navy` aliases through CSS variables, and assert CSS contains canonical `.btn-primary`, `.btn-secondary`, `.btn-outline`, `.btn-light`, `.btn-danger`, and `.btn-link` definitions using centralized tokens rather than legacy hex values.

Add assertions that button typography resolves to the CTA tokens and that `.btn-primary` is navy/white while `.btn-secondary` is orange/white.

- [ ] **Step 2: Run the button/design-system tests and verify the new secondary/CTA expectations fail**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/StorefrontButtonCascadeTest.php`

Expected: FAIL because the existing button aliases are based on the previous primary-red system and old CTA sizing/weights.

- [ ] **Step 3: Update Tailwind aliases without creating a second palette**

Keep the current `themeColor()` runtime-variable pattern. Map compatibility names (`brand.red`, `brand.blue`, `brand.navy`, `brand.dark`) to the appropriate new semantic variables so existing Blade classes inherit the new identity without mass template renames.

- [ ] **Step 4: Normalize the shared `.btn` foundation and variants**

Update the global button foundation to use the approved Expose CTA tokens, centralized heights/padding/radius/focus/disabled/loading states, and exact navy/orange variant mapping. Keep legacy class names such as `.btn-red`, `.btn-navy`, `.btn-white`, and `.btn-product` only as aliases to the canonical variants when templates still use them.

- [ ] **Step 5: Protect button text from page-level inheritance overrides**

Preserve and extend the existing cascade rule strategy so broad page link selectors exclude `.btn`. Add a regression assertion that no known storefront broad anchor rule can override canonical `.btn` text colors.

- [ ] **Step 6: Run focused button/design-system tests**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/StorefrontButtonCascadeTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tailwind.config.js resources/css/storefront.css tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/StorefrontButtonCascadeTest.php
git commit -m "feat: map storefront buttons to centralized theme"
```

### Task 3: Apply semantic typography and colors across shared storefront CSS

**Files:**
- Modify: `resources/css/storefront.css`
- Modify: `resources/css/pagination.css`
- Modify: `tests/Feature/StorefrontDesignSystemMigrationTest.php`
- Modify: `tests/Feature/ProductCardTemplateUpdateTest.php`

**Interfaces:**
- Consumes: Task 1 semantic type/color tokens and Task 2 button variants.
- Produces: storefront-wide inherited typography/color behavior for shared headings, navigation, cards, forms, filters, pagination, wishlist/cart/checkout/auth/account sections, and reusable CTAs.

- [ ] **Step 1: Add failing source-audit tests for active storefront CSS**

Assert:

```php
$this->assertStringNotContainsString('Inter', $storefrontCss);
$this->assertStringNotContainsString('Oswald', $storefrontCss);
$this->assertStringNotContainsString('#15345d', strtolower($paginationCss));
$this->assertStringNotContainsString('#0d2545', strtolower($paginationCss));
```

Add exact checks that shared section headings map to Title/2, card/module headings map to Title/3 or Title/4 as appropriate, body copy uses Body/1 or Body/2, tags/meta use the tag/small semantic token, and pagination brand states use CSS variables.

Update the existing product-card token expectation from the old orange hex to `#CF5D38` while keeping its badge regression checks intact.

- [ ] **Step 2: Run the source-audit/product-card tests and verify they fail on legacy values**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/ProductCardTemplateUpdateTest.php`

Expected: FAIL on the old orange assertion and hard-coded pagination/legacy type values.

- [ ] **Step 3: Map shared typography selectors to semantic tokens**

Update the shared base/body, `np-section-heading__*`, page-title, product-card, navigation/control, label/helper, and reusable module heading rules to consume Task 1 semantic tokens. Preserve existing responsive layout and only use centralized `clamp()`/responsive token aliases where overflow prevention is already needed.

- [ ] **Step 4: Replace active brand hard-codes in `storefront.css` and `pagination.css` with semantic variables**

Only replace hard-coded colors that represent NextPlay brand identity. Leave semantic state colors and decorative testimonial/avatar colors unchanged as required by the spec.

- [ ] **Step 5: Verify product-card badge styling still follows the centralized orange token**

Keep the current badge behavior/geometry; only its orange token value changes from the old orange to `#CF5D38`. Do not reintroduce default/customizable badge behavior.

- [ ] **Step 6: Run focused CSS/product-card tests**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/ProductCardTemplateUpdateTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/css/storefront.css resources/css/pagination.css tests/Feature/StorefrontDesignSystemMigrationTest.php tests/Feature/ProductCardTemplateUpdateTest.php
git commit -m "feat: apply centralized storefront typography and colors"
```

### Task 4: Remove remaining storefront-level brand/font bypasses, including standalone error pages

**Files:**
- Modify as needed: `resources/views/components/storefront/**/*.blade.php`
- Modify as needed: `resources/views/storefront/**/*.blade.php`
- Modify: `resources/views/errors/error.blade.php`
- Modify as needed: `resources/views/errors/storefront-status.blade.php`
- Modify: `tests/Feature/StorefrontDesignSystemMigrationTest.php`

**Interfaces:**
- Consumes: central tokens/classes from Tasks 1-3.
- Produces: no active customer-facing Blade surface that independently defines the old brand palette, Inter/Oswald, or a conflicting button identity.

- [ ] **Step 1: Add a failing recursive storefront-source audit**

In the design-system test, scan only customer-facing storefront/component/error/pagination view roots and assert there are no active occurrences of `fonts.googleapis.com`, `Inter`, `Oswald`, `#e91d33`, `#c9182b`, `#15345d`, `#0d2545`, or `#2467b7` where they are used as storefront brand identity. Explicitly exclude approved decorative testimonial/avatar color arrays from legacy-brand checks when the same hex is decorative rather than UI chrome.

- [ ] **Step 2: Run the audit and verify it fails on the current standalone error page and any remaining bypasses**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php`

Expected: FAIL with the remaining file paths/legacy values.

- [ ] **Step 3: Convert standalone error-page brand variables to the new centralized identity**

Because `resources/views/errors/error.blade.php` may render outside the normal Vite storefront shell, keep its self-contained fallback styling but use Expose/local-safe fallback tokens and the exact new navy/orange values. Do not redesign its layout.

- [ ] **Step 4: Replace remaining page/component brand/font bypasses with shared classes or CSS variables**

For any source-audit failure, make the smallest change: use existing `font-sans`/`font-display`, `text-brand-*`, `.btn*`, or CSS variable tokens. Do not refactor unrelated component layout or JavaScript.

- [ ] **Step 5: Run the storefront-source audit again**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php`

Expected: PASS with decorative/state colors preserved and no active legacy brand/font bypasses.

- [ ] **Step 6: Run relevant storefront regression tests**

Run: `php artisan test tests/Regression tests/Feature/StorefrontContentPagesTest.php tests/Feature/WishlistPrototypeTest.php tests/Feature/HomepageProductCollectionsTest.php`

Expected: PASS; report any pre-existing unrelated failure by name instead of hiding it.

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/storefront resources/views/storefront resources/views/errors tests/Feature/StorefrontDesignSystemMigrationTest.php
git commit -m "refactor: remove storefront theme bypasses"
```

### Task 5: Build, verify compiled assets, and run final regression checks

**Files:**
- Generated/verify: `public/build/manifest.json`
- Generated/verify: `public/build/assets/*`
- Modify only if needed: `tests/Feature/StorefrontDesignSystemMigrationTest.php`

**Interfaces:**
- Consumes: completed source migration from Tasks 1-4.
- Produces: deployable compiled assets whose manifest references CSS containing Expose/new brand tokens and no stale legacy bundle.

- [ ] **Step 1: Add compiled-asset assertions**

Extend `StorefrontDesignSystemMigrationTest` to read `public/build/manifest.json`, locate the compiled `resources/css/storefront.css` asset, and assert the built CSS contains `Expose`, `#061F44`/equivalent minified lowercase form, `#CF5D38`/equivalent minified lowercase form, and canonical button selectors while excluding `Inter` and `Oswald`.

- [ ] **Step 2: Run the compiled-asset test before build and verify it fails if the checked-in bundle is stale**

Run: `php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php`

Expected: FAIL if the current build output does not match the migrated source.

- [ ] **Step 3: Install frontend dependencies and build**

Run:

```bash
npm ci
npm run build
```

Expected: Vite exits 0 and writes a manifest referencing the newly generated storefront CSS/font assets.

- [ ] **Step 4: Run the design-system and focused regression tests after build**

Run:

```bash
php artisan test tests/Feature/StorefrontDesignSystemMigrationTest.php \
  tests/Feature/StorefrontButtonCascadeTest.php \
  tests/Feature/ProductCardTemplateUpdateTest.php
```

Expected: PASS.

- [ ] **Step 5: Run the full Laravel test suite when dependencies are available**

Run: `php artisan test`

Expected: PASS. If any unrelated/pre-existing tests fail, record each failing test name and its output in the completion report.

- [ ] **Step 6: Run final source scans**

Run:

```bash
grep -RInE 'Inter|Oswald|#e91d33|#c9182b|#15345d|#0d2545|#2467b7' \
  resources/css/storefront-theme.css resources/css/storefront.css resources/css/pagination.css \
  resources/views/components/storefront resources/views/storefront resources/views/errors
```

Expected: no active storefront brand/font identity matches; any surviving match must be an explicitly reviewed decorative/state-only value allowed by the spec.

- [ ] **Step 7: Verify the manifest and font output**

Confirm `public/build/manifest.json` points at the latest storefront CSS and that all four Expose WOFF2 weights are emitted or referenced by the built output.

- [ ] **Step 8: Commit generated deployable assets if this repository tracks `public/build`**

```bash
git add public/build tests/Feature/StorefrontDesignSystemMigrationTest.php
git commit -m "build: compile centralized storefront theme"
```

If `public/build` is intentionally untracked, do not force-add it; document that production deployment must execute `npm ci && npm run build`.

## Self-Review Result

- Spec coverage: all approved color, font, typography, button, storefront-scope, responsive, accessibility, compatibility, and build-verification requirements map to Tasks 1-5.
- Step scan: each task has a failing-test step, minimal implementation step(s), verification, and commit boundary.
- Type/name consistency: all tasks consume the same `--np-*` token namespace and the existing `.btn`/Tailwind compatibility interfaces.
- Review Focus coverage: legacy aliases, button cascade visibility, standalone error pages, product-card badge token behavior, and responsive/touch behavior are each exercised by a task-level source or regression test.
- Proportion: the plan specifies exact values, files, tests, and interfaces without prescribing unrelated implementation details or page redesigns.
