# NextPlay Storefront Design System Migration

Date: 2026-09-26

## Goal

Migrate the entire customer-facing storefront to the supplied NextPlay visual system while preserving the existing centralized architecture. The storefront must use the supplied Expose type family and the approved navy/orange brand palette everywhere through shared tokens and reusable component styles rather than page-specific overrides.

Admin styling and backend business logic are out of scope unless a storefront dependency requires a metadata update such as the browser theme color.

## Approved Brand Foundation

### Brand colors

- Primary navy: `#061F44`
- Secondary orange: `#CF5D38`
- White/off-white, neutral ink, muted text, border, success, warning, and error colors remain semantic supporting tokens.

The existing red/blue storefront palette is replaced at the token layer. Legacy token names used by existing Tailwind classes may remain as aliases temporarily so templates do not require unnecessary churn, but active storefront styling must resolve to the new navy/orange system.

### Font family

Use the supplied self-hosted `Expose` family for all storefront typography. Required WOFF2 files:

- Expose-Regular — 400
- Expose-Medium — 500
- Expose-Bold — 700
- Expose-Black — 900

The storefront must not depend on Google Fonts. Existing admin font loading is not part of this migration.

## Central Typography Scale

All values use `letter-spacing: 0`.

| Semantic token | Size | Weight | Line height |
| --- | ---: | ---: | ---: |
| Tag | 16px | 500 | 140% |
| Title/1 | 48px | 900 | 115% |
| Title/2 | 32px | 900 | 115% |
| Title/3 | 24px | 900 | 120% |
| Title/4 | 16px | 900 | 130% |
| Body/1 | 18px | 400 | 140% |
| Body/2 | 14px | 400 | 140% |
| CTA/All Caps/Large | 18px | 700 | 100% |
| CTA/All Caps/Regular | 16px | 700 | 100% |
| CTA/Normal/Large | 18px | 400 | 100% |
| CTA/Normal/Regular | 16px | 400 | 100% |
| CTA/Normal/Small | 14px | 500 | 100% |

Responsive headings may use `clamp()` only where needed to prevent overflow, but the desktop target values and semantic hierarchy above remain canonical.

## Token Architecture

`resources/css/storefront-theme.css` remains the single source of truth for storefront design tokens.

The migration will centralize:

- brand palette
- semantic text colors
- Expose font family
- font weights
- title/body/tag/CTA scales
- button height, padding, radius, borders, icon gap, focus ring, transitions, loading/disabled states
- semantic action colors

`tailwind.config.js` continues to map Tailwind storefront utilities to these runtime CSS variables. Existing utilities such as `font-sans`, `font-display`, and `text-brand-*` must resolve through the centralized values rather than creating a second palette or font stack.

## Font Assets and Loading

Copy only the four required WOFF2 files into a storefront font asset directory under `resources` or `public` using the repository's Vite asset pattern.

Define `@font-face` entries for the single family name `Expose` with weights 400, 500, 700, and 900 and `font-display: swap`.

Both body and heading family tokens resolve to `Expose`. Components select hierarchy with semantic weight/size tokens, not different font-family names.

## Central Button System

The existing shared `.btn` foundation remains the only storefront button system.

### Variants

- `.btn-primary`: navy background, white text
- `.btn-secondary`: orange background, white text
- `.btn-outline`: light/off-white background or transparent surface, navy text/border
- `.btn-light`: off-white background, navy text
- `.btn-danger`: semantic error background, white text
- `.btn-link`: transparent, semantic brand link color
- purpose-specific aliases such as `.btn-product` may remain only when behavior/layout differs; they inherit the canonical base and resolve through the new tokens

### CTA typography

- Default all-caps primary CTA: 16px, 700, 100%
- Large all-caps CTA: 18px, 700, 100%
- Normal large: 18px, 400, 100%
- Normal regular: 16px, 400, 100%
- Normal small: 14px, 500, 100%

Existing authored casing remains unchanged unless the component already intends an all-caps CTA. The system must not globally uppercase every button.

### Interaction states

Hover, focus-visible, active, disabled, loading, icon size/gap, and transitions are defined once in the shared button system. Page/component CSS may control width, alignment, and placement only.

## Storefront Typography Application

Semantic mapping:

- primary page hero / dominant page heading → Title/1
- major section heading → Title/2
- card/module heading → Title/3
- small component heading → Title/4
- prominent descriptive copy → Body/1
- standard content/helper copy → Body/2
- category/tag/badge meta text → Tag or a context-specific small semantic token where space requires it
- navigation and controls → CTA/Normal or CTA/All Caps tokens according to existing casing

Existing `font-display`, `font-sans`, `np-section-heading__*`, product card titles, navigation, forms, pagination, wishlist/cart/checkout/auth screens, category screens, and homepage sections must resolve through this system.

## Color Application

- Navy `#061F44` is the primary brand/action color.
- Orange `#CF5D38` is the secondary CTA/accent color.
- Links and focus treatments resolve through semantic brand tokens.
- Existing storefront hard-coded legacy brand colors are replaced with centralized variables when they represent brand identity.
- Semantic state colors (success, warning, error) remain distinct and are not recolored to brand colors.
- Decorative testimonial/avatar palette colors are not automatically replaced unless they are serving as brand UI chrome.

## Storefront Scope

Audit and migrate customer-facing storefront surfaces including:

- shared storefront layout, header, navigation, announcement areas, footer
- homepage sections
- reusable product cards and product grids
- product detail pages
- all-products/category/sports/shop-by-category pages
- search/filter controls
- wishlist and cart
- checkout/customer account/auth storefront pages
- bulk quote storefront pages
- pagination
- reusable content blocks and CTA sections
- error pages that use storefront brand identity

Admin dashboard styling remains outside scope.

## Legacy Compatibility

To avoid unnecessary template churn:

- old Tailwind brand utility names can stay as semantic aliases if they map to new tokens
- old shared button class aliases can remain if they map to the new central variants
- no new parallel theme stylesheet is introduced
- page-specific hard-coded font families, brand colors, button visual identity, and heading typography are removed or replaced with token references

## Responsive Behavior

The migration must preserve current layout behavior. Typography can scale down at smaller breakpoints using centralized responsive tokens or `clamp()` where necessary. No page layout redesign is part of this task.

Buttons remain touch-friendly and maintain shared minimum heights while respecting existing full-width/mobile placement rules.

## Accessibility

- Maintain visible focus states using centralized focus tokens.
- Ensure navy/orange button text combinations remain readable.
- Keep disabled and loading states visually distinct.
- Preserve semantic heading structure in Blade templates; CSS styling must not require changing heading tags only for appearance.

## Files Expected to Change

Primary design-system files:

- `resources/css/storefront-theme.css`
- `resources/css/storefront.css`
- `resources/css/pagination.css`
- `tailwind.config.js` only if alias mapping needs adjustment
- storefront layout metadata where theme color is hard-coded
- font asset files copied from the supplied Expose package

Additional storefront Blade/CSS files change only where they currently bypass central tokens with hard-coded brand/font/button identity.

Admin CSS/layout files must not be modified for this storefront migration.

## Testing and Verification

Before completion:

1. Build frontend assets with `npm ci && npm run build`.
2. Confirm Vite manifest references the newly built storefront assets.
3. Run focused source checks that no active storefront CSS contains `Inter` or `Oswald` font stacks.
4. Search storefront CSS/views for legacy brand hex values where they represent active storefront identity.
5. Verify centralized button variants retain visible text on all backgrounds.
6. Verify product cards, navigation, forms, pagination, cart, wishlist, checkout, category pages, and homepage resolve to Expose and the new tokens.
7. Run Laravel tests if dependencies are available; report any unavailable suite honestly.
8. Perform syntax checks on changed PHP/Blade-adjacent PHP files where applicable.

## Non-Goals

- No backend business-logic changes.
- No database migrations.
- No admin redesign.
- No new page layout or component redesign beyond applying the approved centralized visual system.
- No external font CDN dependency.
