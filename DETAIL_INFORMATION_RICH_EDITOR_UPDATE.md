# Detail / Information Rich Editor Update

## Change
The Product Information section no longer uses repeatable Detail / Information rows. It now uses the same reusable rich-text editor component as Formatted Product Description.

## Storage and security
- Added `products.detail_information_html` as nullable LONGTEXT.
- Input is validated and sanitized through the existing `SafeHtmlService`.
- Existing legacy `specifications` JSON data is preserved.
- On first edit, legacy specification rows are converted into an HTML table inside the rich editor.

## Storefront
The formatted Detail / Information content is rendered:
- beside the product gallery;
- in the Specifications tab.

Legacy products without the new HTML value continue using their existing specification table.

## Deployment
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```
