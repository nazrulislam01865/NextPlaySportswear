# Vue 3 Homepage Setup

The new storefront homepage lives inside the existing Laravel application. Only `/` has been switched to Vue. All other storefront pages remain on the existing Blade implementation.

## Install frontend dependencies

Because this archive was produced in an environment without npm registry access, `node_modules` is not included and the stale pre-Vue `package-lock.json` has been intentionally removed. Your first install will generate a fresh lockfile from the Vue-enabled `package.json`. From the project root run:

```bash
npm install
npm run build
```

During development:

```bash
npm run dev
```

Optional checks after install:

```bash
npm run typecheck
npm run test:frontend
node --test tests/frontend/homepage-architecture.test.mjs
```

## Backend API connections

The Vue homepage uses the existing backend contracts:

- `GET /api/v1/storefront/bootstrap`
- `GET /api/v1/home`
- `POST /api/v1/cart/items`
- `POST /api/v1/wishlist/items`

All API traffic goes through `resources/js/storefront/api/client.ts`.

## Migration safety

- `/` -> Vue homepage
- old `resources/views/storefront/home.blade.php` remains untouched but is not linked to any public homepage route
- old `App\\Http\\Controllers\\Storefront\\HomeController` remains for the legacy latest-products endpoint and rollback
- category/product/cart/auth/checkout/account/admin routes remain unchanged

## Figma fidelity note

The supplied Figma node could not be read by the connected Figma account because the file does not grant editor access. The current implementation therefore follows the supplied screenshot as the visual reference and centralizes all design tokens/assets so exact Figma values can replace the working values without rewriting components once editor access is granted.
