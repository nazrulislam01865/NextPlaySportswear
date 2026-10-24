# NextPlay: Simple Ordering Process — prototype match

## Scope

The homepage Ordering Process card row now mirrors the supplied five-card reference:
full-height top images, STEP 01–05 labels, navy headings, gray copy, slender inter-card
arrows, white cards and thin borders. Images are cropped from the supplied prototype
and converted to small WebP files; no image generation or unrelated styling change.

A reusable Blade card component was added under
`resources/views/components/storefront/home/order-process-card.blade.php`. The
parent component still uses the same dynamic homepage section registry, and all
colors/fonts use the current NextPlay storefront theme tokens.

## Admin controls

**Admin > Homepage Sections > Ordering Process**

- Edit title/description and reorder the steps in the existing section editor.
- Upload/replace an image for each step under **Ordering process card images**.
- Set optional image alt text.
- Use **Use prototype image** to restore that card's bundled default image.
- JPG, PNG, WebP, AVIF up to 10 MB, displayed at ratio 282:262.
- Uploaded image is stored through the existing centralized public image service,
  with the existing homepage sections cache invalidation on save.

## Deployment

After applying the full ZIP (or overlaying the patch onto the same source revision):

```bash
php artisan migrate --force
php artisan optimize:clear
```

The migration updates only the five **unchanged legacy default** ordering-step
texts and inserts bundled default image URLs where no custom image was set.
Custom-edited steps, admin-uploaded images and unrelated homepage sections are
not overwritten. This is a content migration, not a schema change.

The patch contains the updated CSS asset and `public/build/manifest.json`. An npm
build is not necessary for this patch. `npm ci --offline` was unavailable here
because a package was not cached; the stylesheet additions were instead included
in a versioned compiled CSS file and the Vite manifest was updated.

## Checks

- PHP syntax checked for migration, registry, request and media service.
- Five bundled images (WebP) verified.
- Existing storefront Vite JavaScript was not changed.
- An end-to-end browser/database session was not available in this environment.
