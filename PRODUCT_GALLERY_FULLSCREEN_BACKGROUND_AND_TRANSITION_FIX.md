# Product gallery fullscreen/background and transition fix

## Updated behavior

- Fullscreen product images are rendered directly on the dark overlay with no modal card, padding, border, radius, shadow, or white presentation background.
- Uniform outer transparent, white, or near-white margins are trimmed in the browser before the fullscreen image is displayed.
- The original image URL is used automatically when trimming is unavailable, including third-party images without canvas CORS permission.
- Gallery images are preloaded before activation and cross-fade inside a locked media stage.
- Changing thumbnails no longer collapses the gallery height or shifts the rest of the product page.
- Reduced-motion browser preferences are respected.

## Updated files

- `resources/views/components/storefront/product/gallery.blade.php`
- `resources/views/storefront/products/show.blade.php`
- `resources/js/storefront.js`
- `public/js/product-image-viewer.js`

No database migration is required. The standalone public JavaScript file is included, so this fix works without rebuilding Vite assets.
