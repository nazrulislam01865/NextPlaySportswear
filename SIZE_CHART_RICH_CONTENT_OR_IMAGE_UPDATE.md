# Size Chart Rich Content or Image Update

## Admin form

`Master Data → Size Options → Create/Edit` now uses only two alternative size-chart methods:

1. **Size Chart Table / Information** — the same reusable rich-text editor used elsewhere in the product admin.
2. **OR: Upload size chart image** — JPG, PNG, WebP, or AVIF up to 5 MB.

The previous Chart title, Chart note, Table columns, Table rows, and visible image-link fields were removed.

Both methods are optional, but they cannot be submitted together. Selecting an uploaded image clears the stored formatted chart. Saving formatted chart content clears an existing stored image.

## Data compatibility

Migration `2026_06_24_000006_add_chart_html_to_size_option_groups.php` adds `chart_html` to:

- `size_option_groups`
- `product_size_groups`

Legacy structured charts without an image are converted to safe HTML tables. Existing image-based charts remain image-based.

## Product and storefront integration

When a master size group is assigned to a product, the formatted chart HTML or image is copied into the product-size snapshot. The storefront size-chart modal renders the sanitized formatted content or the image.

## Security

Formatted HTML is sanitized through `SafeHtmlService`. Scripts, inline styles, event handlers, unsafe URLs, and unsupported tags are removed. Tables remain supported.

## Deployment

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan view:cache
```

Do not run `migrate:fresh`.
