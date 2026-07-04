# Admin Pusher Product Notifications

This update adds the NextPlay admin notification bell and Pusher Channels integration for product activity.

## What it sends

Admin users receive a notification when a product is:

- added
- edited/updated
- duplicated
- deleted

Each notification stores the actor name/email, product name, SKU, action, and URL where the product can be opened when available.

## Required `.env` values

Add your Pusher Channels credentials to the server `.env`:

```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap2
PUSHER_HOST=
```

Use your actual Pusher cluster instead of `ap2` if Pusher shows a different one.

## Deploy commands

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

No queue worker is required for this first version because notifications are saved and pushed immediately after the product action completes.
