# Storefront Product Card Consistency Update

Updated the storefront so product cards use the same reusable component across the system.

## What changed

- Home page Featured Products now uses `x-storefront.product-card`, matching product listing, category pages, cart suggestions, and related products.
- Home page category-style cards now use `x-storefront.category-card` instead of the old custom `product-card` markup.
- Added consistent product card styling for cleaner typography, lighter shadows, softer borders, less bold title weight, and better spacing.
- Added the same CSS to the current compiled storefront asset so the update can show immediately before a fresh local Vite build.

## Changed files

- `resources/views/components/storefront/product-card.blade.php`
- `resources/views/components/storefront/home/featured-products.blade.php`
- `resources/views/components/storefront/home/category-section.blade.php`
- `resources/views/components/storefront/home/popular-categories.blade.php`
- `resources/css/storefront.css`
- `public/build/assets/storefront-BebQBZFd.css`
