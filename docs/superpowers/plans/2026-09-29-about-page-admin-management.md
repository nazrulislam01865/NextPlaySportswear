# About Page Admin Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the existing `/about-us` page fully admin-manageable, including all text, SEO, images, icons, button labels/URLs, card content, step content, and gallery content, while keeping the seven-section storefront order and approved visual layout fixed.

**Architecture:** Add a single `about_page_settings` record with structured JSON for the seven fixed sections and SEO. `AboutPageService` owns defaults/normalization/render-ready media URLs; `AboutPageMediaService` owns only About-upload staging, replacement, cleanup, and rollback-safe deletion; a dedicated admin controller/request/view edits the fixed slots under existing RBAC. Storefront Blade components remain presentation-only and preserve the current DOM/CSS structure.

**Tech Stack:** Laravel, PHP 8.x, Eloquent JSON casts, Blade, Laravel public storage, existing `PublicMedia`, existing `AdminRbac`, PHPUnit/Laravel feature tests, Vite.

**Spec:** `docs/superpowers/specs/2026-09-29-about-page-admin-management-design.md`

## Global Constraints

- Fixed storefront section order: Hero → Introduction → What We Do → How We Work → Gallery → Made for Your Team CTA → Need Help.
- Admin cannot add, remove, reorder, or hide the seven primary sections.
- Exactly 3 What We Do cards, 4 How We Work steps, and 4 Gallery image slots are supported.
- Preserve the approved About DOM/CSS layout, centralized fonts, theme colors, buttons, shared header/footer, and responsive behavior.
- Every user-visible About-specific content value must be admin-editable; structural arrows/classes/ARIA wiring remain code-owned.
- Uploads accept JPG/JPEG, PNG, WebP, and AVIF; content images max 10 MB, icons max 2 MB; no arbitrary SVG upload.
- About-owned uploads live only below `about-page/` on the `public` disk and only those paths may be deleted by About cleanup.
- Shipped defaults in `public/images/storefront/about/` must never be deleted.
- Button URLs allow relative `/...` paths and absolute `http://` or `https://` URLs only.
- Existing admin RBAC, CSRF, `PublicMedia`, and public-media fallback routing must be reused.
- Missing/malformed settings must normalize to the current approved page defaults instead of breaking the storefront.
- No unrelated storefront/admin refactor.

## Review Focus

- Persisted JSON with missing/duplicate/unknown slot ids must still render exactly 3 service cards, 4 process steps, and 4 gallery slots in canonical order.
- Replacing an uploaded file must not delete the previous file until the database update succeeds; failed persistence must clean staged new files and preserve previous files.
- A malicious stored/requested delete path outside `about-page/` must never be deleted.
- A custom media path whose file is missing on disk must render the shipped fallback instead of a broken image.
- Relative/absolute safe button URLs must pass while `javascript:`, `data:`, protocol-relative, and malformed values fail validation.

---

### Task 1: Persisted About settings and normalized defaults

**Files:**
- Create: `database/migrations/2026_09_29_000100_create_about_page_settings_table.php`
- Create: `app/Models/AboutPageSetting.php`
- Create: `app/Services/Storefront/AboutPageService.php`
- Create: `tests/Unit/Services/Storefront/AboutPageServiceTest.php`

**Interfaces:**
- Produces: `AboutPageSetting` with array casts for `hero`, `introduction`, `what_we_do`, `how_we_work`, `gallery`, `cta`, `help`, `seo`.
- Produces: `AboutPageService::settings(): array` returning the normalized persisted/default schema.
- Produces: `AboutPageService::defaults(): array` as the sole source for the current approved About copy, stable ids, fallback icon keys, default image asset paths, and default CTA/help route destinations.
- Produces: `AboutPageService::flushCache(): void` if caching is introduced; otherwise omit caching rather than add unused infrastructure.
- Consumes: existing `App\Support\PublicMedia` for stored media URLs.

- [ ] **Step 1: Write failing service tests for exact defaults and fixed slot normalization**

Add tests named:
- `test_settings_returns_current_approved_defaults_when_row_is_missing`
- `test_settings_merges_missing_json_keys_from_defaults`
- `test_settings_keeps_exactly_three_service_slots_in_canonical_id_order`
- `test_settings_keeps_exactly_four_process_slots_in_canonical_id_order`
- `test_settings_keeps_exactly_four_gallery_slots_in_canonical_id_order`
- `test_unknown_and_duplicate_slot_ids_cannot_expand_or_reorder_fixed_slots`

Assert the default schema contains the current About values already present in `resources/views/storefront/content/about.blade.php`, including stable ids such as `custom-teamwear`, `sportswear-gear`, `bulk-orders`, `choose-product`, `personalise`, `review-details`, `place-order`, and four stable gallery slot ids.

- [ ] **Step 2: Run the service test and verify RED**

Run: `php artisan test tests/Unit/Services/Storefront/AboutPageServiceTest.php`

Expected: FAIL because `AboutPageSetting` / `AboutPageService` / table do not exist.

- [ ] **Step 3: Add the migration and model**

Migration columns: nullable JSON for each section/SEO field, `foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()`, `foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()`, and timestamps. `AboutPageSetting::$fillable` includes the JSON/user fields; `$casts` maps all eight JSON fields to `array`. Follow the storefront-branding migration pattern and call `AdminRbac::syncDefaults(false)` at the end of `up()` so the newly deployed About permissions are registered when this migration runs with the updated codebase.

- [ ] **Step 4: Implement `AboutPageService::defaults()` and `settings()`**

Use the current page as the exact default content source. Normalize persisted section arrays by stable id, not submitted array position, so malformed/legacy JSON cannot change slot count/order. Return render-ready fields that include both the persisted storage path and a resolved URL/fallback URL as needed by the views.

- [ ] **Step 5: Add missing-custom-file fallback tests**

Add `test_missing_custom_media_path_falls_back_to_shipped_default_asset_or_builtin_icon` using `Storage::fake('public')` and persisted non-existent About paths.

- [ ] **Step 6: Run the service test and verify GREEN**

Run: `php artisan test tests/Unit/Services/Storefront/AboutPageServiceTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_29_000100_create_about_page_settings_table.php app/Models/AboutPageSetting.php app/Services/Storefront/AboutPageService.php tests/Unit/Services/Storefront/AboutPageServiceTest.php
git commit -m "feat: add normalized about page settings"
```

### Task 2: Validate the complete fixed admin payload and URLs

**Files:**
- Create: `app/Http/Requests/Admin/AboutPageRequest.php`
- Create: `tests/Feature/Admin/AboutPageValidationTest.php`

**Interfaces:**
- Consumes: fixed ids/default slot contract from `AboutPageService`.
- Produces: `AboutPageRequest::validatedContent(): array` containing text/URL/remove flags only; uploaded files remain accessible from the request for media handling.
- Produces: server-side validation for exactly 3 `what_we_do.cards`, 4 `how_we_work.steps`, and 4 `gallery.items`, with canonical stable ids.

- [ ] **Step 1: Write failing validation tests**

Add tests named:
- `test_about_request_accepts_all_valid_text_fields_and_safe_relative_and_https_urls`
- `test_about_request_rejects_javascript_data_protocol_relative_and_malformed_urls`
- `test_about_request_rejects_missing_or_extra_service_process_and_gallery_slots`
- `test_about_request_rejects_changed_duplicate_or_unknown_stable_slot_ids`
- `test_about_request_rejects_invalid_image_and_icon_types_and_enforces_size_limits`
- `test_about_request_requires_alt_text_when_a_custom_upload_is_submitted`

Use exact limits from the spec: eyebrow 120, headings/titles 255, copy 1000, button labels 100, alt text 255, step number 10, SEO title 255, SEO description 500; images 10240 KB; icons 2048 KB.

- [ ] **Step 2: Run the validation test and verify RED**

Run: `php artisan test tests/Feature/Admin/AboutPageValidationTest.php`

Expected: FAIL because the request class/routes do not exist yet; if route-level invocation is not yet possible, instantiate validation through a minimal test route local to the test case.

- [ ] **Step 3: Implement `AboutPageRequest`**

Use nested Laravel validation rules plus an `after()` validator callback for fixed counts/stable ids and label/URL pairing. Do not reuse `App\Rules\SafePublicUrl` for these fields because it also permits anchors; add a private request helper `isAllowedDestination(?string $value): bool` that accepts only strings beginning with `/` (but not `//`) or valid absolute `http://` / `https://` URLs.

- [ ] **Step 4: Normalize booleans and trimmed values in `prepareForValidation()`**

Normalize all `remove_*` checkboxes to booleans and trim URLs/text fields without converting stable ids.

- [ ] **Step 5: Run the validation test and verify GREEN**

Run: `php artisan test tests/Feature/Admin/AboutPageValidationTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/Admin/AboutPageRequest.php tests/Feature/Admin/AboutPageValidationTest.php
git commit -m "feat: validate about page admin content"
```

### Task 3: Transaction-safe About media staging, replacement, removal, and cleanup

**Files:**
- Create: `app/Services/Catalog/AboutPageMediaService.php`
- Create: `tests/Unit/Services/Catalog/AboutPageMediaServiceTest.php`

**Interfaces:**
- Produces: `AboutPageMediaService::prepare(AboutPageRequest $request, array $current, array $validated): array` with exact keys `payload`, `new_paths`, and `delete_after_commit`.
- Produces: `AboutPageMediaService::commitCleanup(array $mutation): void` deleting only `delete_after_commit` paths after DB save.
- Produces: `AboutPageMediaService::rollback(array $mutation): void` deleting only `new_paths` when persistence fails.
- All deletions must pass `isAboutOwnedPath(string $path): bool`, true only for normalized paths beginning `about-page/`.

- [ ] **Step 1: Write failing media-service tests**

Add tests named:
- `test_new_uploads_use_dedicated_about_page_directories`
- `test_no_upload_keeps_existing_custom_path`
- `test_replace_stages_new_file_without_deleting_previous_file_before_commit_cleanup`
- `test_commit_cleanup_deletes_only_replaced_or_removed_about_owned_files`
- `test_rollback_deletes_only_newly_staged_files_and_preserves_previous_files`
- `test_remove_custom_media_clears_reference_and_restores_fallback_contract`
- `test_cleanup_refuses_paths_outside_about_page_root`
- `test_shipped_public_about_assets_are_never_storage_delete_candidates`

Cover introduction image, 3 service icons, 4 process icons, 4 gallery images, and help icon through the same slot-map mechanism rather than duplicated upload code.

- [ ] **Step 2: Run media-service tests and verify RED**

Run: `php artisan test tests/Unit/Services/Catalog/AboutPageMediaServiceTest.php`

Expected: FAIL because the media service does not exist.

- [ ] **Step 3: Implement slot-map-based staging and cleanup**

Storage roots must be exactly:
- `about-page/introduction`
- `about-page/what-we-do/icons`
- `about-page/how-we-work/icons`
- `about-page/gallery`
- `about-page/help/icons`

Store the new file first, update the returned payload path, record prior eligible About-owned paths for later cleanup, and never delete during `prepare()`.

- [ ] **Step 4: Run media-service tests and verify GREEN**

Run: `php artisan test tests/Unit/Services/Catalog/AboutPageMediaServiceTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Catalog/AboutPageMediaService.php tests/Unit/Services/Catalog/AboutPageMediaServiceTest.php
git commit -m "feat: add safe about page media handling"
```

### Task 4: Admin routes, RBAC, controller update transaction, and navigation

**Files:**
- Create: `app/Http/Controllers/Admin/AboutPageController.php`
- Modify: `routes/web.php`
- Modify: `app/Support/AdminRbac.php`
- Modify: `resources/views/components/layouts/admin.blade.php`
- Create: `tests/Feature/Admin/AboutPageAccessTest.php`

**Interfaces:**
- Consumes: `AboutPageService::settings()`, `AboutPageRequest::validatedContent()`, `AboutPageMediaService` mutation lifecycle.
- Produces routes: `admin.about-page.edit` (`GET /admin/about-page`) and `admin.about-page.update` (`PUT /admin/about-page`).
- Produces permissions: `about_page.view`, `about_page.manage` in module `Storefront`.
- `AboutPageController::edit(): View` supplies `about`, `canManageAboutPage`.
- `AboutPageController::update(AboutPageRequest $request): RedirectResponse` saves a single row inside `DB::transaction`, records `created_by`/`updated_by`, calls media cleanup only after successful DB transaction, rolls back staged files on exceptions, then redirects with status.

- [ ] **Step 1: Write failing RBAC/access tests**

Add tests named:
- `test_super_admin_can_open_and_update_about_page_editor`
- `test_admin_and_content_manager_receive_about_view_and_manage_defaults`
- `test_view_only_admin_can_open_editor_but_cannot_update`
- `test_admin_without_view_permission_cannot_open_editor`
- `test_about_page_sidebar_link_is_visible_only_with_view_permission`
- `test_failed_database_save_rolls_back_staged_uploads_and_preserves_previous_media`

Use the existing Role Matrix/RBAC test helpers/patterns; do not bypass `admin.permission` middleware.

- [ ] **Step 2: Run access tests and verify RED**

Run: `php artisan test tests/Feature/Admin/AboutPageAccessTest.php`

Expected: FAIL because permissions/routes/controller/sidebar entry are absent.

- [ ] **Step 3: Add permissions/default role mapping and route resolution**

Add `about_page.view` and `about_page.manage` next to other Storefront permissions with sort order between branding and homepage controls. Add both to `content_manager`; `admin` derives all non-protected permissions; `super_admin` derives all permissions. Update `firstAllowedRoute()` and `permissionForRoute()` so `GET admin.about-page.edit` requires view and `PUT admin.about-page.update` requires manage.

- [ ] **Step 4: Add admin routes and controller**

Use the existing authenticated `admin` route group. In update, obtain current normalized/persisted content, prepare staged media, perform one settings-row create/update transaction, and only then call `commitCleanup()`. Catch `Throwable`, call `rollback()`, report/log according to existing admin error conventions, and rethrow or redirect with an error without masking validation failures.

- [ ] **Step 5: Add Store sidebar entry without altering other menus**

Extend the Store-group outer permission condition to include `about_page.view`. Add one `About Page` sidebar link using `route('admin.about-page.edit')` and `request()->routeIs('admin.about-page.*')`.

- [ ] **Step 6: Run access tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/AboutPageAccessTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/AboutPageController.php routes/web.php app/Support/AdminRbac.php resources/views/components/layouts/admin.blade.php tests/Feature/Admin/AboutPageAccessTest.php
git commit -m "feat: add about page admin access"
```

### Task 5: Complete fixed-section admin editor with upload previews and remove controls

**Files:**
- Create: `resources/views/admin/about-page/edit.blade.php`
- Create: `tests/Feature/Admin/AboutPageEditorTest.php`

**Interfaces:**
- Consumes: `about` normalized settings from `AboutPageService::settings()` and `canManageAboutPage` from controller.
- Produces: one multipart `PUT` form to `admin.about-page.update` with nested field names matching `AboutPageRequest` exactly.
- Fixed stable ids are submitted as hidden fields; no add/delete/reorder controls exist.

- [ ] **Step 1: Write failing editor-render tests**

Add tests named:
- `test_editor_renders_all_seven_fixed_sections_and_seo_fields`
- `test_editor_renders_three_service_card_editors_four_process_editors_and_four_gallery_editors`
- `test_editor_contains_upload_replace_remove_and_alt_controls_for_every_required_media_slot`
- `test_editor_contains_no_section_reorder_add_delete_or_visibility_controls`
- `test_view_only_admin_sees_current_content_but_update_controls_are_disabled_or_absent`
- `test_validation_errors_preserve_old_text_input_and_current_media_previews`

Count expected upload inputs: 1 introduction + 3 service icons + 4 process icons + 4 gallery images + 1 help icon = 13.

- [ ] **Step 2: Run editor test and verify RED**

Run: `php artisan test tests/Feature/Admin/AboutPageEditorTest.php`

Expected: FAIL because the view does not exist.

- [ ] **Step 3: Implement the editor using existing admin visual primitives**

Render fixed cards in exact storefront order. For every media slot show current preview, file input, remove-custom checkbox only when a custom path exists, alt-text input, accepted formats/help text, and keep the stable slot id hidden. Do not add custom JS unless needed for existing file-preview behavior; use existing admin styling conventions.

- [ ] **Step 4: Run editor tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/AboutPageEditorTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/about-page/edit.blade.php tests/Feature/Admin/AboutPageEditorTest.php
git commit -m "feat: add about page admin editor"
```

### Task 6: Storefront uses admin-managed content without changing layout

**Files:**
- Modify: `app/Http/Controllers/Storefront/ContentPageController.php`
- Modify: `resources/views/storefront/content/about.blade.php`
- Modify: `resources/views/components/storefront/about/service-card.blade.php`
- Modify: `resources/views/components/storefront/about/process-step.blade.php`
- Modify: `resources/css/storefront.css` only if current icon wrappers do not already support uploaded `<img>` containment
- Modify: `tests/Feature/StorefrontContentPagesTest.php`
- Create: `tests/Feature/AboutPageManagedContentTest.php`

**Interfaces:**
- `ContentPageController::about(AboutPageService $aboutPage): View` passes `about` and `seo` derived from normalized settings.
- `service-card` adds props `iconUrl => null`, `iconAlt => ''`, `icon => null`; custom `<img>` wins, fallback `x-storefront.about.icon` renders otherwise.
- `process-step` adds the same custom-icon props while preserving `last` and structural chevrons.
- Blade uses only normalized `about` values for About-specific text/media; no About copy remains hardcoded except structural/non-content strings.

- [ ] **Step 1: Write failing storefront managed-content tests**

Add tests named:
- `test_storefront_renders_every_saved_about_text_section_and_seo_value`
- `test_storefront_renders_custom_intro_service_process_gallery_and_help_media_urls`
- `test_storefront_uses_builtin_icons_and_shipped_images_when_custom_media_is_removed`
- `test_storefront_defaults_render_when_about_settings_row_is_absent`
- `test_storefront_escapes_admin_managed_html_in_text_fields`
- `test_storefront_keeps_exact_fixed_section_sequence_and_card_step_gallery_counts`

Use distinct saved text for each field so a missing binding cannot pass accidentally.

- [ ] **Step 2: Run storefront tests and verify RED**

Run: `php artisan test tests/Feature/AboutPageManagedContentTest.php tests/Feature/StorefrontContentPagesTest.php`

Expected: FAIL because storefront still uses hardcoded content.

- [ ] **Step 3: Inject `AboutPageService` into `ContentPageController::about()` and pass normalized data**

Build SEO from `about['seo']`, with canonical `route('about')`, robots `index, follow`, Open Graph values matching the admin-managed title/description, and schema type `AboutPage`.

- [ ] **Step 4: Convert About Blade/components to normalized data**

Preserve existing section/class hierarchy and fixed section order. Loop the normalized 3/4/4 fixed arrays. Use custom upload URL when present; otherwise use fallback asset/icon from the service. Keep CTA/help chevrons code-owned.

- [ ] **Step 5: Add minimal icon-image containment CSS only if required**

If needed, add scoped `.np-about-*` rules so uploaded icons use `object-fit: contain`, preserve transparent whitespace, and do not change card dimensions or page spacing.

- [ ] **Step 6: Run storefront tests and verify GREEN**

Run: `php artisan test tests/Feature/AboutPageManagedContentTest.php tests/Feature/StorefrontContentPagesTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Storefront/ContentPageController.php resources/views/storefront/content/about.blade.php resources/views/components/storefront/about/service-card.blade.php resources/views/components/storefront/about/process-step.blade.php resources/css/storefront.css tests/Feature/StorefrontContentPagesTest.php tests/Feature/AboutPageManagedContentTest.php
git commit -m "feat: render admin managed about page"
```

### Task 7: End-to-end admin update coverage for every content and media slot

**Files:**
- Create: `tests/Feature/Admin/AboutPageManagementTest.php`
- Modify implementation files only where an end-to-end test exposes a defect.

**Interfaces:**
- Consumes all interfaces from Tasks 1–6.
- Produces no new production API; this task closes the spec’s full CRUD/update contract.

- [ ] **Step 1: Write the end-to-end update test for every editable field**

Create `test_manage_admin_can_update_every_about_text_url_alt_and_step_number_field` with distinct values for hero, introduction, What We Do heading + all 3 cards, How We Work heading + all 4 steps, all 4 gallery alt fields, CTA, Help, and SEO; assert persisted JSON and storefront output.

- [ ] **Step 2: Add upload coverage for all 13 media slots**

Create `test_manage_admin_can_upload_and_render_all_about_images_and_icons` using `Storage::fake('public')`; assert all files exist under the correct `about-page/` roots and storefront HTML references `PublicMedia::storedPathUrl(...)` values.

- [ ] **Step 3: Add replacement/removal and validation-failure media preservation coverage**

Add:
- `test_replacing_all_about_owned_media_deletes_only_previous_about_owned_files_after_save`
- `test_removing_custom_media_deletes_about_owned_files_and_restores_all_defaults`
- `test_validation_failure_keeps_current_persisted_media_and_files_unchanged`
- `test_invalid_url_or_file_input_does_not_mutate_database_or_media`

- [ ] **Step 4: Run the end-to-end tests and verify RED if they expose any missing behavior**

Run: `php artisan test tests/Feature/Admin/AboutPageManagementTest.php`

Expected: any failure must correspond to a missing spec behavior, not a test setup error.

- [ ] **Step 5: Implement only the minimal corrections required by failing tests**

Do not add additional About capabilities beyond the approved spec.

- [ ] **Step 6: Run the end-to-end tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/AboutPageManagementTest.php`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Admin/AboutPageManagementTest.php app resources routes database
git commit -m "test: cover complete about page management"
```

### Task 8: Full regression, build, syntax, and delivery verification

**Files:**
- No planned production changes; only fix regressions directly caused by this feature.

**Interfaces:**
- Consumes complete implementation.
- Produces verification evidence for delivery.

- [ ] **Step 1: Run all targeted About tests together**

Run:
```bash
php artisan test \
  tests/Unit/Services/Storefront/AboutPageServiceTest.php \
  tests/Unit/Services/Catalog/AboutPageMediaServiceTest.php \
  tests/Feature/Admin/AboutPageValidationTest.php \
  tests/Feature/Admin/AboutPageAccessTest.php \
  tests/Feature/Admin/AboutPageEditorTest.php \
  tests/Feature/Admin/AboutPageManagementTest.php \
  tests/Feature/AboutPageManagedContentTest.php \
  tests/Feature/StorefrontContentPagesTest.php
```

Expected: PASS, 0 failures.

- [ ] **Step 2: Run the full Laravel test suite**

Run: `php artisan test`

Expected: PASS, 0 failures. Any pre-existing unrelated failure must be reported by exact test name instead of hidden.

- [ ] **Step 3: Run PHP syntax checks on all new/modified PHP files**

Run `php -l` for the migration, model, request, controller, both services, and changed PHP support/controller files.

Expected: `No syntax errors detected` for each file.

- [ ] **Step 4: Run production frontend build**

Run: `npm run build`

Expected: exit code 0. If `resources/css/storefront.css` was unchanged, still run the build because Blade/CSS asset integration is part of delivery verification.

- [ ] **Step 5: Verify route and RBAC registration**

Run: `php artisan route:list --name=admin.about-page`

Expected: exactly the GET edit and PUT update routes with existing admin middleware stack.

- [ ] **Step 6: Verify migration is reversible in a disposable test database/environment**

Run the project’s normal test migration cycle or `php artisan migrate:fresh --env=testing` only if the repository’s test environment is configured safely for disposal.

Expected: `about_page_settings` is created without affecting existing migrations.

- [ ] **Step 7: Review final diff against the approved spec**

Confirm line-by-line that no section reorder/visibility/add/delete controls were introduced, all 13 media slots are managed, every About-specific visible text/URL/alt/SEO value is editable, and unrelated storefront/admin files were not changed without necessity.

- [ ] **Step 8: Commit final verification-only fixes if any**

```bash
git add -A
git commit -m "fix: finalize about page admin management"
```

Skip this commit if verification required no code changes.
