# Product realtime visitor tracking fix

This update replaces the previous manual-only visitor label with real storefront visitor tracking.

## What changed

- Added `product_view_sessions` table to store anonymous per-product active viewer sessions.
- Added `POST /products/visitor-activity` endpoint.
- Product cards now include product IDs and an always-available activity row that JavaScript can reveal when live data exists.
- Storefront JavaScript pings the activity endpoint on page load and every 60 seconds.
- Product detail pages also register a live product view, so a product card can show another active shopper when someone else is on the product page.
- Visitor text remains hidden when there is no genuine activity data.

## Why it was not showing before

The earlier product card only displayed `recent_viewers_count` when that value was manually entered in the admin product form. It did not automatically track two browsers or live storefront visitors.

## Required after deploy

Run:

```bash
php artisan migrate
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
```

If `php artisan migrate` is skipped, the visitor endpoint will not have the table needed to store live viewer data.
