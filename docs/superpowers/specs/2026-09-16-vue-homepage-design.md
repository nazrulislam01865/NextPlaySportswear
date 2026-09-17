# NextPlay Vue 3 Homepage Design Spec

## Goal
Replace only the public `/` storefront homepage with a Vue 3 implementation inside the existing Laravel project. Preserve every other legacy storefront Blade route and the Blade admin unchanged.

## Visual source
- Primary current visual reference: user-provided homepage screenshot matching Figma prototype node `1:9`.
- Figma design context could not be retrieved because the connected account does not have editor access. Therefore exact Figma-only token values and original exported assets must remain centrally swappable.
- No intentional redesign is allowed.

## Architecture
- Laravel remains backend/source of truth.
- New homepage mounts Vue from a thin Blade host.
- Vue calls only existing `/api/v1/home` and `/api/v1/storefront/bootstrap` for homepage/global data plus existing cart/wishlist APIs for homepage actions.
- Existing category/product/cart/auth/checkout routes remain Blade destinations.
- No Vue Router, no Inertia, no separate frontend project.

## Frontend boundaries
`resources/js/storefront/` is the new Vue storefront root. Components never call Axios directly. Feature API modules call the centralized API client. Homepage API data stays page-local through `useHome`; shared customer/cart/wishlist/navigation state goes in Pinia.

## Reuse and design system
- Centralize colors, fonts, spacing, layout widths, radii, shadows and motion in CSS tokens.
- Centralize buttons, containers, section headings, product cards, carousel controls, image tiles, loading and error states.
- Reuse one ProductCard in New Arrivals, Best Choices and Make It Yours.
- Reuse one ProductCarousel and one CarouselControls implementation.

## Homepage section order
1. Utility bar
2. Main header/navigation
3. Hero slider
4. Men/Women/Kids audience tiles
5. Shop by Sport
6. New Arrivals
7. Shop by Category
8. Best Choices for You
9. Season Sale banner
10. Make It Yours
11. How to Design a T-Shirt Using NextPlay
12. Footer

## Data sources
- `/api/v1/storefront/bootstrap`: site, customer, cart summary, wishlist summary, global navigation.
- `/api/v1/home`: SEO, slides, sections, categories, featured/latest/best-selling products, sports, process steps and menu data.
- `/api/v1/cart/items`: direct add-to-cart for simple non-customizable items.
- `/api/v1/wishlist/items`: wishlist actions.

## Migration safety
- `/` switches to the Vue host.
- Existing `resources/views/storefront/home.blade.php` and `Storefront\\HomeController` remain in the project but are no longer linked from `/`.
- `/homepage/latest-products` and every other legacy storefront/admin route remain unchanged.
