# Bulk Quote improvements (October 31, 2026)

## Change scope
- The existing optional product picker now includes image URLs for product option values and Color attribute values that already have an uploaded image. There are no new database queries, joins, migrations, or fields. Text-only values stay selectable. Image choices submit exactly the same option group/value IDs as the original select. Product picker search remains lazy.
- Shipping preference still submits the original `preferred_shipping_method` native select with the same `standard`, `express`, `rush` and `recommend` values. Its existing enhanced dropdown now shows a compact relevant icon for each choice; form processing is unchanged.
- Attachment upload remains first. The Additional Notes field is immediately below, full-width. The existing CAPTCHA remains after those controls.
- Removed the icon preceding the privacy statement, the “Your Request” sidebar card and its now-unused summary script. “What happens next?” and “Need a hand?” are preserved.
- A dedicated **WhatsApp contact number** editor has been added to **Admin → Bulk Quote Icons**. Uses the existing `storefront_branding.manage` permission. The number is stored in `storage/app/private/nextplay/bulk-quote-contact.json` on a standard Laravel 13 local disk. No migrations, schema changes or database queries. When empty, fallback to existing `STOREFRONT_WHATSAPP_URL` or `STOREFRONT_WHATSAPP` works as before. Other storefront WhatsApp links are intentionally untouched.

## Deployment
Deploy full project or copy changed files preserving paths. The new `public/css/bulk-quote-enhancements.css` is loaded after existing quote design styles; the product/select JS URLs include fresh versions. No Vite build or migrations required. Run `php artisan view:clear && php artisan route:clear` after deployment. Ensure that the PHP process can write under the local disk (Laravel 13's `storage/app/private` by default). If a public image uses `storage/app/public`, the existing `public/storage` symlink must continue to work.

## Verification
Run `node --test tests/frontend/*.test.mjs`, then (on a Laravel install with vendor dependencies) `php artisan test --filter=BulkQuoteVisualSettingsTest` and `php artisan test --filter=BulkQuoteProductInformationTest`. Verify login with admin permissions, update the WhatsApp number, open the bulk quote page and confirm the generated `https://wa.me/` link. Exercise an image option, a text-only option, each shipping method, mobile viewport, and file upload. The uploaded ZIP does not contain vendor dependencies, so Laravel integration tests are not runnable in the supplied sandbox.
