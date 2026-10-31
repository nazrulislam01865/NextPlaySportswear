# NextPlay Bulk Quote — Master Data shipping images and icon sizes

## What was wrong

The Bulk Quote `public/js/bulk-quote-selects.js` had four hardcoded SVG paths; they were **not** the images uploaded by administrators under **Master Data → Shipping Methods**.

## Correct data flow

- The Bulk Quote controller now loads **active Shipping Methods with images** in one bounded, ordered lookup, reusing the existing `ShippingMethod::imageUrl()` and `PublicMedia` URL resolution.
- The existing fixed quote preference values remain **standard**, **express**, **rush**, **recommend**. They are not database shipping method identifiers. Images are associated by the master record's normalized `code` or `name`, not by changing form submission values.
- Default master records `standard`, `expedited`, `rush-review` map to form options `standard`, `express`, `rush`. For **Recommend best option**, upload an image to an active master method whose code is `recommend` (or `recommended` / `recommend-best-option` / `recommend-best-shipping-option`) if an image should appear. The default **Bulk Freight / Team Shipment** image is deliberately **not** reused for the recommendation choice because they do not mean the same thing.
- If no matching master method has an uploaded image, display the text-only choice; **never invent a generic SVG**.
- The enhanced dropdown renders the image on both its choice row and its selected-value trigger. The original native select's value, event dispatch, validation, and keyboard behavior are unchanged.
- No new table, column, migration, upload UI, or storage method is added.

## Current sizes and recommendation

| Element | Original | Updated |
| --- | --- | --- |
| Shipping menu icons | 20 × 20 px hardcoded SVG | 28 × 28 px contain-fit uploaded image |
| Bulk Quote heading icon slot | 52 × 52 px, transparent with 1 px padding | Unchanged 52 × 52 px |
| Built-in heading SVG within slot | 20 × 20 px | 36 × 36 px |
| Admin-uploaded Bulk Quote section icon | Contain-fit within 52 × 52 px slot (50 × 50 px available) | Unchanged |

For section artwork, upload a **52 × 52 px transparent-background icon**. For shipping methods, a square **64 × 64 px transparent PNG or WebP** is a useful source asset; the menu displays it at 28 × 28 px and will not crop it. Both align with Expose typography and existing `--nps-*` colors.

## Deployment

Update the changed files together. The storefront layout now loads the revisioned Bulk Quote CSS (`20261031shippingmaster1`) and JavaScript (`20261031shippingmaster1`). Clear compiled views if relevant: `php artisan view:clear`. No database migration or Vite rebuild is needed.

The saved upload files must exist in configured public media storage on production. This update does not migrate administrator-uploaded media. Use **Admin → Master Data → Shipping Methods** to upload or replace the images.

## Verification

- Frontend tests: 45 passed via `node --test tests/frontend/*.test.mjs`.
- PHP syntax checks on controller and new feature test: passed.
- New Laravel feature tests check uploaded master image association, inactive records, defaults and unchanged submitted preference values, but could not be run without installed Composer dependencies.
- CSS and archive integrity checked. Browser rendering and live uploaded media availability remain unverified.
