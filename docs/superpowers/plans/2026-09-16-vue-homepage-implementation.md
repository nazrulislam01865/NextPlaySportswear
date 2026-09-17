# NextPlay Vue Homepage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the new screenshot-matched homepage in Vue 3 inside the existing Laravel project and connect it to existing `/api/v1` backend contracts.

**Architecture:** A thin Blade host mounts Vue only for `/`. Vue uses a centralized Axios client and feature API modules. Existing legacy Blade storefront/admin remains intact for all non-homepage pages.

**Tech Stack:** Laravel 13, Vite, Vue 3, TypeScript, Pinia, Axios, VueUse, Lucide Vue, Swiper, CSS design tokens.

**Spec:** `docs/superpowers/specs/2026-09-16-vue-homepage-design.md`

## Global Constraints
- One Laravel project only.
- No Inertia.
- No Vue Router in this homepage phase.
- Laravel remains authoritative for products, pricing, cart, wishlist, auth and all backend behavior.
- Preserve all existing non-homepage storefront routes and admin.
- No intentional visual redesign from the supplied reference.

---

### Task 1: Frontend toolchain and contract tests
**Files:** `package.json`, `vite.config.js`, `tsconfig.json`, `tests/frontend/homepage-architecture.test.mjs`
- [ ] Add Vue/tooling dependency declarations without removing legacy dependencies.
- [ ] Add Vue plugin and storefront Vue Vite entry while retaining legacy/admin entries.
- [ ] Add TypeScript config.
- [ ] Add static architecture test proving centralized API usage and no inline component HTTP calls.

### Task 2: API/client/store foundation
**Files:** `resources/js/storefront/api/*`, `stores/storefront.store.ts`, `features/home/api/home.api.ts`, `features/home/composables/useHome.ts`
- [ ] Add `/api/v1` Axios client with JSON/CSRF/session/error normalization.
- [ ] Add endpoint constants.
- [ ] Add bootstrap store for site/customer/cart/wishlist/navigation.
- [ ] Add home feature API/composable.

### Task 3: Centralized design system
**Files:** `resources/js/storefront/styles/*`, `components/ui/*`
- [ ] Add design tokens and exact-reference-oriented typography/layout constants.
- [ ] Add reusable AppContainer, AppButton, AppSectionHeader, AppSkeleton.

### Task 4: Shared storefront components
**Files:** `components/common/*`, `components/layout/*`
- [ ] Build ProductCard, ProductCarousel, CarouselControls, ImageCategoryTile, PriceDisplay.
- [ ] Build UtilityBar, StorefrontHeader, desktop/mobile navigation, footer.

### Task 5: Homepage sections
**Files:** `features/home/components/*`, `features/home/pages/HomePage.vue`, `App.vue`, `main.ts`
- [ ] Reproduce all screenshot sections in order.
- [ ] Use one `/api/v1/home` load and pass data down via props.
- [ ] Use Swiper for hero/product carousels.
- [ ] Preserve existing legacy URLs for outbound links.

### Task 6: Laravel host/cutover
**Files:** `app/Http/Controllers/Storefront/VueHomeController.php`, `resources/views/storefront-vue/home.blade.php`, `routes/web.php`
- [ ] Make `/` mount Vue.
- [ ] Keep old Blade homepage code untouched and unlinked.
- [ ] Preserve `/homepage/latest-products` legacy endpoint.

### Task 7: Verification
- [ ] Run static Node architecture tests.
- [ ] Run PHP syntax checks.
- [ ] Run Vite build if dependencies are available.
- [ ] Zip full updated project.
