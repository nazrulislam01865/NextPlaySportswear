# Shipping & Delivery Page — Delivery Tab Admin Management Design

**Date:** 2026-09-29  
**Project:** NextPlay Sportswear  
**Scope:** First phase only — implement the **Delivery** tab from the supplied Order Information prototype. The other four tab labels remain visible as non-destructive placeholders until their behavior is defined later.

## 1. Goal

Replace the current generic `/shipping-delivery` storefront body with the supplied **Order Information → Delivery** prototype while preserving the project's current global header, navigation, footer, responsive storefront shell, centralized Expose typography, theme colors, buttons, and shared layout/gutter system.

The complete Delivery-tab content must be manageable from the admin panel without allowing an administrator to break the prototype's fixed structure. The page must remain usable when settings are missing, partially malformed, or reference missing media.

## 2. Binding Design Rules

1. The supplied prototype controls the **Shipping & Delivery page body**. The existing project controls the shared header/footer and global navigation; this feature must not add, remove, or redesign global header/footer items.
2. The Delivery page must use the same centralized storefront maximum width and visible page gutters used by the corrected About page and other current storefront pages. It must not introduce a narrower standalone container that creates excessive blank space on large screens.
3. The main page structure and order are fixed in code. Admin users edit content, not layout.
4. The five tabs remain in this exact order:
   - `before-you-order`
   - `artwork-customisation`
   - `after-you-order`
   - `delivery`
   - `help`
5. `delivery` is the only active/functional content tab in this phase. The other four render as accessible, non-navigation placeholders and must not invent routes or content behavior.
6. The prototype's timeline uses numbered circles, not uploaded icons. The three step numbers are editable text values, but no additional icon is introduced into those circles because doing so would change the approved design.
7. FAQ chevrons and timeline connector lines are structural UI owned by code, not uploaded content.
8. All content-specific icons visible in the prototype must be replaceable from admin with safe built-in fallbacks.
9. No unrelated refactoring or changes to checkout shipping-method logic, order shipping calculations, remote-area surcharges, tracking logic, or existing commerce services are in scope.

## 3. Existing Architecture to Reuse

The feature follows the already-shipped admin-managed About page architecture:

- dedicated Eloquent settings model with JSON section columns;
- storefront service that supplies defaults, merges persisted known shapes, protects fixed slot IDs/fallback metadata, normalizes malformed leaf values, validates persisted destinations, and resolves media safely;
- dedicated media service for staged upload/replacement/removal and post-commit cleanup;
- FormRequest validation with stable IDs and fixed collection sizes;
- admin controller with transactional save and rollback-safe media handling;
- dedicated RBAC view/manage permissions integrated into `AdminRbac`;
- dedicated admin edit screen under the Store sidebar;
- storefront Blade view composed from small page-specific components and scoped CSS;
- safe URLs through `App\Support\PublicUrl` rather than custom regular expressions.

This page must not reuse `shipping.view` / `shipping.manage`, because those permissions already mean **Shipping Methods master data**. New permission keys are required to avoid conflating page content with shipping configuration.

## 4. Storefront Route and Controller

Keep the existing public route and route name:

- `GET /shipping-delivery`
- route name: `shipping`
- controller: `App\Http\Controllers\Storefront\ContentPageController@shipping`

Change `shipping()` to inject `ShippingDeliveryPageService`, load normalized page settings, create SEO metadata from the managed SEO section, and render `storefront.content.shipping` with the settings payload.

Canonical remains `route('shipping')` and schema type should be `WebPage`.

If the settings table does not yet exist during a rolling deployment, the storefront service should return code defaults instead of causing `/shipping-delivery` to 500. Admin saving still requires the migration and should report a friendly save error if persistence is unavailable.

## 5. Fixed Storefront Structure

The page renders in the following fixed order.

### 5.1 Order Information Hero

Prototype defaults:

- Eyebrow: `NEXTPLAY SPORTSWEAR`
- Title: `ORDER INFORMATION`
- Subtitle: `A clear guide to ordering custom sportswear with NextPlay.`

The hero uses the prototype's dark navy background treatment. It does **not** introduce a hero image upload because the supplied design has no hero image.

Admin-editable:

- eyebrow
- title
- subtitle

### 5.2 Five-Tab Navigation

Five fixed slots, fixed order and fixed IDs:

1. `before-you-order` → `Before You Order`
2. `artwork-customisation` → `Artwork & Customisation`
3. `after-you-order` → `After You Order`
4. `delivery` → `Delivery`
5. `help` → `Help`

Admin-editable:

- label of each tab

Code-owned behavior:

- Delivery is active with the prototype dark navy active state and orange bottom accent.
- Other four tabs are visible but disabled/non-navigation placeholders in this phase.
- No add/remove/reorder controls.

### 5.3 Delivery Intro

Prototype defaults:

- Title: `DELIVERY`
- Subtitle: `See delivery options before checkout and follow your order after dispatch.`

Admin-editable:

- title
- subtitle

### 5.4 Top Information Cards

Exactly two fixed cards.

#### Card 1: `before-you-pay`

- Title: `BEFORE YOU PAY`
- Description: `Review the available delivery options and total cost at checkout before placing your order.`
- Built-in fallback icon: shopping cart

#### Card 2: `after-dispatch`

- Title: `AFTER DISPATCH`
- Description: `If tracking is available for your shipment, you can find the latest details in Track Your Order.`
- Built-in fallback icon: parcel/box

Admin-editable per card:

- title
- description
- icon upload / replacement / removal
- icon alt/accessibility text

Fixed:

- two cards only
- slot IDs and order
- card proportions/layout

### 5.5 How Delivery Works

Prototype heading:

- `HOW DELIVERY WORKS`

Exactly three fixed timeline steps:

#### Step 1: `confirm-address`

- Number: `1`
- Title: `Confirm your address`
- Description: `Enter a complete and accurate shipping address at checkout.`

#### Step 2: `choose-delivery-option`

- Number: `2`
- Title: `Choose an available delivery option`
- Description: `Review the delivery options and total cost shown for your order.`

#### Step 3: `follow-dispatch-updates`

- Number: `3`
- Title: `Follow updates after dispatch`
- Description: `If tracking is available, use Track Your Order to see the latest details.`

Admin-editable:

- section heading
- each step number
- each step title
- each step description

Fixed:

- exactly three steps
- IDs/order
- horizontal connector line on desktop
- numbered-circle visual treatment
- responsive stacking behavior

No uploaded step icon is added because the prototype uses number circles.

### 5.6 Delivery Notice

Prototype default text:

`Production time and delivery time are different. Any estimate should be shown for your specific order.`

Built-in fallback icon: information circle.

Admin-editable:

- notice text
- notice icon upload / replacement / removal
- icon alt/accessibility text

### 5.7 Delivery Questions

Prototype defaults:

- Heading: `DELIVERY QUESTIONS`
- Subtitle: `Find quick answers to common questions about delivery.`

Exactly four fixed FAQ slots:

1. `delivery-costs`
   - Question: `When will I see delivery costs?`
   - Answer: `The applicable delivery charges are shown before the order is placed.`
2. `change-address`
   - Question: `Can I change my delivery address?`
   - Answer: `Contact support promptly; changes depend on your order stage and whether it has been dispatched.`
3. `tracking-number`
   - Question: `Where is my tracking number?`
   - Answer: `If available, it appears after dispatch on Track Your Order.`
4. `multiple-locations`
   - Question: `Can a team order ship to multiple locations?`
   - Answer: `Ask for a bulk quote so we can review the request.`

Admin-editable:

- heading
- subtitle
- all four questions
- all four answers

Behavior:

- FAQ rows are accessible disclosure controls.
- The prototype chevron is structural and code-owned.
- The four IDs/order are fixed; admin cannot add, delete, duplicate, or reorder entries.

### 5.8 Shipping Address Checklist

Prototype defaults:

- Heading: `SHIPPING ADDRESS CHECKLIST`
- Subtitle: `Make sure your shipping address is complete and accurate to help avoid delays.`

Exactly four fixed checklist items:

#### `recipient-name`

- Title: `Recipient name`
- Description: `Use the full name of the person receiving the order.`
- Built-in fallback icon: person

#### `full-address`

- Title: `Full address`
- Description: `Include building number, street, and any additional address information (e.g. unit, suite).`
- Built-in fallback icon: document/address

#### `postcode`

- Title: `Postcode`
- Description: `Enter the correct postcode for the delivery address.`
- Built-in fallback icon: location pin

#### `contact-details`

- Title: `Contact details`
- Description: `Provide a phone number or email in case the delivery team needs to contact you.`
- Built-in fallback icon: phone

Admin-editable per item:

- title
- description
- icon upload / replacement / removal
- icon alt/accessibility text

Fixed:

- four items only
- IDs/order
- layout/divider treatment

### 5.9 Bottom Order-Status CTA

Prototype defaults:

- Heading: `CHECK YOUR ORDER STATUS`
- Description: `Track your order after dispatch to see the latest delivery updates.`
- Primary button label: `TRACK YOUR ORDER`
- Primary destination: `/track-order`
- Policy link label: `Shipping & Delivery policy`
- Policy destination: `/terms-conditions`

Admin-editable:

- heading
- description
- primary button label
- primary button destination
- policy link label
- policy link destination

Both destinations accept only safe relative application paths, safe fragments where appropriate, or absolute HTTP/HTTPS URLs through `PublicUrl`. Unsafe stored legacy/corrupt destinations must fall back to the code defaults before rendering.

### 5.10 SEO

Admin-editable:

- SEO title
- meta description

Prototype/default values should describe Shipping & Delivery accurately and use the existing storefront name configuration.

## 6. Admin Screen

Add a dedicated Store navigation item:

**Store → Shipping & Delivery Page**

Recommended admin routes:

- `GET /admin/shipping-delivery-page` → `admin.shipping-delivery-page.edit`
- `PUT /admin/shipping-delivery-page` → `admin.shipping-delivery-page.update`

The editor uses the project's existing admin form/card/button patterns and renders sections in the same fixed order as the storefront. It must include a Preview button opening `route('shipping')` in a new tab.

No admin controls for:

- section reordering
- adding/removing cards
- adding/removing steps
- adding/removing FAQs
- adding/removing checklist items
- changing the active tab
- changing page colors/fonts/layout CSS

## 7. Uploadable Media

The Delivery phase has **7 uploadable icon slots**:

1. Before You Pay icon
2. After Dispatch icon
3. Delivery notice icon
4. Recipient name icon
5. Full address icon
6. Postcode icon
7. Contact details icon

Each slot supports:

- upload
- replacement
- removal
- current preview
- alt/accessibility text
- built-in SVG/icon fallback when no custom file exists

Accepted file types:

- JPG/JPEG
- PNG
- WebP
- AVIF

Icon max file size: **2 MB per upload**.

Uploaded icon display must use `object-fit: contain` so transparent or non-square assets are not cropped.

Storage namespace:

`storage/app/public/shipping-delivery-page/...`

Only files owned by this feature may be automatically cleaned up. Removal/replacement must never delete Media Library files, branding assets, About-page uploads, or shipped built-in fallback assets.

## 8. Persistence Model

Create `App\Models\ShippingDeliveryPageSetting` backed by `shipping_delivery_page_settings`.

Recommended JSON columns:

- `hero`
- `tabs`
- `delivery_intro`
- `info_cards`
- `delivery_steps`
- `notice`
- `faqs`
- `address_checklist`
- `cta`
- `seo`
- `created_by`
- `updated_by`
- timestamps

A single settings record is sufficient; no independent CRUD table is required for each fixed slot.

The migration must be idempotent in the same style as the About migration and call `AdminRbac::syncDefaults(false)` after adding the new permission definitions.

## 9. Stable Slot IDs and Normalization

Code-owned slot IDs:

### Tabs

- `before-you-order`
- `artwork-customisation`
- `after-you-order`
- `delivery`
- `help`

### Info cards

- `before-you-pay`
- `after-dispatch`

### Delivery steps

- `confirm-address`
- `choose-delivery-option`
- `follow-dispatch-updates`

### FAQs

- `delivery-costs`
- `change-address`
- `tracking-number`
- `multiple-locations`

### Address checklist

- `recipient-name`
- `full-address`
- `postcode`
- `contact-details`

Normalization must rebuild fixed collections from these code defaults in code order, matching persisted items by ID. Unknown, duplicated, missing, reordered, or malformed persisted slots are ignored/fallen back rather than allowed to alter the storefront structure.

Stable IDs and built-in fallback-icon identifiers are code-owned and cannot be overridden by persisted JSON.

Wrong leaf types (arrays/objects where strings are expected) must resolve to defaults so malformed data cannot cause Blade escaping/type errors.

## 10. Media Resolution and Missing Files

`ShippingDeliveryPageService` resolves uploaded media paths only when they are valid Shipping & Delivery-owned storage paths and the file exists on the public disk.

If an uploaded icon is missing, malformed, points outside the allowed feature namespace, or cannot be resolved, the storefront uses its code-owned built-in fallback icon.

No arbitrary persisted path may be passed to the storage adapter before namespace/path validation.

## 11. Validation

Create `App\Http\Requests\Admin\ShippingDeliveryPageRequest`.

Validation must enforce:

- exact known section keys;
- required text fields with sensible length caps consistent with About management;
- exact collection counts: 5 tabs, 2 info cards, 3 steps, 4 FAQs, 4 checklist items;
- exact stable ID order for each collection;
- 2 MB validated image uploads for all seven icons;
- boolean removal flags;
- safe CTA destinations using `PublicUrl`, not a duplicated custom regex;
- trimming of normal text inputs while preserving stable IDs.

Admin validation errors must keep current persisted content/media unchanged. The form redisplays entered text and existing media previews.

## 12. Media Mutation Transaction

Create `App\Services\Catalog\ShippingDeliveryPageMediaService` using the About media service's staged mutation pattern.

Required flow:

1. Build a normalized content payload from validated text data.
2. Stage new uploads under the Shipping & Delivery namespace.
3. Apply explicit remove flags to payload media paths.
4. Record newly written paths for rollback.
5. Record replaced/removed old feature-owned paths for cleanup after successful DB commit.
6. Save the settings row inside a DB transaction.
7. If upload staging or DB save fails, delete newly staged files and preserve the old row/files.
8. After a successful DB commit, delete obsolete old feature-owned files.
9. If cleanup of an obsolete file fails after commit, report it but do not report the content save as failed and do not delete active media.

## 13. RBAC

Add dedicated permissions under module `Storefront`:

- `shipping_delivery_page.view`
  - label: `View Shipping & Delivery Page`
  - route: `admin.shipping-delivery-page.edit`
- `shipping_delivery_page.manage`
  - label: `Manage Shipping & Delivery Page`
  - route: `admin.shipping-delivery-page.update`

Defaults:

- Super Admin: view + manage
- Admin: view + manage (consistent with existing Admin defaults)
- Content Manager: view + manage
- Catalog Manager: no new default access unless separately granted through Role Matrix
- Order Manager / Support Agent: no new default access

Integrate the permissions into:

- `AdminRbac::permissions()`
- `defaultAllowedPermissions()` content manager set
- `firstAllowedRoute()`
- `permissionForRoute()`
- Store sidebar visibility condition and link

Do not repurpose `shipping.view` or `shipping.manage` because those continue to govern Shipping Methods master data.

## 14. Storefront Components and Styling

The page should be modular rather than one large Blade file. Page-specific components may include:

- order-information tab bar
- delivery info card
- delivery timeline step
- delivery notice
- FAQ/disclosure row
- address checklist row
- page icon renderer with custom-upload/fallback support

Component names should follow existing `resources/views/components/storefront/...` conventions.

All new storefront styles must be scoped under a Shipping & Delivery page root class so they do not affect products, cart, checkout, About, Sale, or other content pages.

Reuse centralized variables/classes for:

- Expose typography
- brand navy/ink/orange colors
- button appearance
- borders/shadows where already standardized
- shared page maximum width and responsive gutters

Do not add independent hardcoded global font or theme definitions.

Desktop should match the prototype proportions and arrangement. Tablet/mobile should stack logically while maintaining section hierarchy, readable touch targets, accessible accordions, and no horizontal overflow.

## 15. Error Handling

The storefront must degrade to defaults instead of a 500 for:

- absent settings record;
- settings table not yet present during rolling deployment;
- malformed JSON section values;
- unknown or duplicate fixed slot IDs;
- wrong text leaf types;
- missing uploaded icon files;
- invalid/malformed feature media paths;
- unsafe persisted CTA destinations.

Admin update failures must:

- preserve the currently active DB content;
- preserve active existing media;
- remove staged-but-unused new uploads;
- return to the editor with a clear page-level error;
- log/report the underlying exception.

## 16. Tests and Verification

Use TDD for implementation. Coverage must include at minimum:

### Storefront/service

- defaults render when no record exists;
- defaults render when settings table is absent;
- persisted text appears in the Delivery page;
- fixed slot order/count survives malformed/reordered persisted arrays;
- wrong leaf types fall back safely;
- unsafe persisted button/policy URLs fall back to defaults;
- missing/malformed icon paths use built-in fallback icons;
- the public route remains `/shipping-delivery` / `shipping`.

### Admin/RBAC

- authorized user can open editor;
- view-only user cannot update;
- unauthorized admin cannot view/update;
- Content Manager receives default view/manage access after RBAC synchronization;
- Shipping Methods permissions remain independent.

### Validation

- exact IDs/order/counts required for every fixed collection;
- unsafe destinations rejected;
- invalid icon type and oversized icon rejected;
- valid JPG/JPEG/PNG/WebP/AVIF uploads accepted;
- remove flags normalized correctly.

### Media behavior

- all seven upload slots can save custom icons;
- replacement deletes obsolete feature-owned icon only after commit;
- removal restores built-in fallback;
- failed save rolls back newly staged files and preserves old files;
- unrelated public-storage paths are never deleted.

### Static/layout regression

- five tabs render in fixed order with Delivery active;
- page uses the centralized storefront layout width/gutters rather than a private narrow max-width;
- 2 info cards, 3 timeline steps, 4 FAQ rows, and 4 checklist rows remain structurally fixed;
- no extra icon is added to timeline number circles;
- current shared header/footer are untouched.

### Project-level verification

Run the focused Shipping & Delivery tests, then the complete Laravel test suite and production frontend build in a dependency-complete environment. Any pre-existing unrelated failures must be reported by name rather than hidden.

## 17. Files Expected to Change or Be Added

Exact task allocation belongs in the implementation plan, but the design expects changes in these areas:

- `app/Models/ShippingDeliveryPageSetting.php`
- `app/Services/Storefront/ShippingDeliveryPageService.php`
- `app/Services/Catalog/ShippingDeliveryPageMediaService.php`
- `app/Http/Requests/Admin/ShippingDeliveryPageRequest.php`
- `app/Http/Controllers/Admin/ShippingDeliveryPageController.php`
- `app/Http/Controllers/Storefront/ContentPageController.php`
- `app/Support/AdminRbac.php`
- `database/migrations/...create_shipping_delivery_page_settings_table.php`
- `routes/web.php`
- `resources/views/admin/shipping-delivery-page/edit.blade.php`
- `resources/views/storefront/content/shipping.blade.php`
- focused storefront components under `resources/views/components/storefront/...`
- scoped storefront CSS in the existing centralized stylesheet
- focused Feature/Unit/static regression tests

## 18. Explicitly Out of Scope for This Phase

- Implementing content panels for Before You Order, Artwork & Customisation, After You Order, or Help.
- Deciding routes/behavior for those four future tabs.
- Modifying shipping methods, rates, carrier integrations, order-tracking data, remote-area surcharge logic, checkout calculations, or fulfilment APIs.
- Making the five tabs reorderable.
- Giving admin free-form CSS/theme/layout controls.
- Changing the current shared storefront header or footer to imitate differences visible in the prototype screenshot.

