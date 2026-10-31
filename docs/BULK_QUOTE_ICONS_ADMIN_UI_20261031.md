# Bulk Quote Icons — Admin UI correction (2026-10-31)

## Scope
The Bulk Quote Icons settings page now uses the existing Product Detail Controls layout: intro panel, grouped section headers, reusable control cards, custom upload button, instant preview, restore-default checkbox, and sticky save bar. It is responsive and uses the shared admin theme and Expose typography.

The five existing slots and field names are unchanged:
- `product`: Product & Customisation
- `contact`: Contact Information
- `delivery`: Delivery Requirements
- `attachments`: Attachments & Notes
- `help`: Need a hand?

## Icon display
On both the storefront and admin preview, each icon sits in a 52 × 52px box with 1px padding, zero margin, transparent background, no border or shadow, and `object-fit: contain` (no cropping). Built-in SVGs are retained if no custom icon is uploaded. No database changes.

Allowed uploads remain PNG, JPG/JPEG, WebP or AVIF, max 2 MB per file. Settings and storage behavior are unchanged.

## Deployment
Preserve the existing `.env`, `vendor`, database, and uploaded files when updating. Then run:

```
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

The admin page loads `public/css/admin-bulk-quote-icons.css` directly, and the Bulk Quote page loads the updated versioned `public/css/bulk-quote-form-system.css`, so the icon corrections do not require a fresh Vite build. For source-asset consistency, run `npm ci && npm run build` during normal deployment.

## Verification

```
node --test tests/frontend/*.test.mjs
```

38 frontend tests passed in the packaging environment. CSS parsing passed for the affected stylesheets. Laravel browser/runtime checks should be performed in a local installation with Composer dependencies.

## Font families
The project's centralized font is `Expose`, for both headings and body text. No separate branded secondary font exists. The configured alternate/fallback stack is `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`.
