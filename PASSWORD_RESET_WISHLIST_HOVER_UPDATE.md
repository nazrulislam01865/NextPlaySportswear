# Password Reset and Wishlist Hover Update

## Restored customer password recovery

The storefront login page again includes **Forgot password?** and now uses a complete Laravel password-broker flow instead of a placeholder page.

Implemented routes:

- `GET /forgot-password`
- `POST /forgot-password`
- `GET /reset-password/{token}`
- `POST /reset-password`

Security behavior:

- Only active customer accounts can use this storefront recovery flow.
- Admin accounts remain isolated from the customer reset form.
- Unknown email addresses receive the same generic success message to prevent account enumeration.
- Reset-link requests and reset submissions use dedicated rate limits.
- Reset tokens expire according to `config/auth.php`.

Production mail must be configured in the server `.env`, including `APP_URL` and the appropriate `MAIL_*` values.

## Wishlist hover preview

The wishlist icon in the desktop header now behaves like the cart icon:

- Hovering or keyboard-focusing the wishlist area opens a compact preview.
- The newest four saved products are shown with image, title, and price.
- The total item count and remaining-item count are displayed.
- Signed-in customer wishlists refresh from the server.
- Guest wishlists refresh instantly from browser storage.
- Empty-state, View Wishlist, Keep Shopping, and Explore Products actions are included.
- The preview is hidden on smaller touch layouts where hover is not reliable.

## Deployment

```bash
cd /var/www/nextplay

composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```

No database migration is required because the project already contains the `password_reset_tokens` table migration.
