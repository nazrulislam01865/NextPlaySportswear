# NextPlay customer forgot-password flow

## Flow

1. Guest opens `/forgot-password`.
2. Email is normalized and validated.
3. Only an active `customer` account can receive a customer reset token.
4. Laravel stores a hashed token in `password_reset_tokens`.
5. `User::sendPasswordResetNotification()` routes delivery through `TransactionalEmailManager`.
6. The centralized email service sends the reset link through the configured mail provider.
7. The reset URL contains the token and customer email.
8. GET `/reset-password/{token}` verifies that the token is still valid before showing the form.
9. POST `/reset-password` validates the token again, validates the new password, changes the password, rotates the remember token, consumes the reset token, and queues a password-changed security email.
10. The user is redirected to `/login` and can sign in with the new password.

## Gmail configuration

Do not commit the Google App Password. Put the real values only in the server/local `.env`.

```env
APP_URL=http://127.0.0.1:8000

TRANSACTIONAL_EMAIL_ENABLED=true
TRANSACTIONAL_EMAIL_MAILER=smtp
TRANSACTIONAL_EMAIL_PASSWORD_RESET_SYNC=true
TRANSACTIONAL_EMAIL_QUEUE=true
TRANSACTIONAL_EMAIL_QUEUE_CONNECTION=database
TRANSACTIONAL_EMAIL_QUEUE_NAME=
TRANSACTIONAL_EMAIL_QUEUE_TRIES=3
TRANSACTIONAL_EMAIL_QUEUE_TIMEOUT=30
TRANSACTIONAL_EMAIL_QUEUE_BACKOFF=60,300,900

MAIL_MAILER=smtp
MAIL_SCHEME=
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=saruarmunna17@gmail.com
MAIL_PASSWORD=YOUR_16_CHARACTER_GOOGLE_APP_PASSWORD
MAIL_TIMEOUT=10
MAIL_FROM_ADDRESS=saruarmunna17@gmail.com
MAIL_FROM_NAME="NextPlay Sportswear"

EMAIL_SUPPORT_ADDRESS=saruarmunna17@gmail.com
EMAIL_SALES_ADDRESS=saruarmunna17@gmail.com
EMAIL_ORDERS_ADDRESS=saruarmunna17@gmail.com
EMAIL_RETURNS_ADDRESS=saruarmunna17@gmail.com
```

For production, `APP_URL` must be the public URL that a customer can actually open. If the application is served from a public IP, use that public HTTP/HTTPS base URL. If it later moves to a domain, change `APP_URL` to the domain and clear cached configuration.

`TRANSACTIONAL_EMAIL_PASSWORD_RESET_SYNC=true` is intentional. Password-reset delivery is security-critical and user-blocking, so the forgot-password request reports success only after the configured provider accepts the reset message. Other transactional emails continue to use the queue.

## Required database tables

The existing project migration already creates:

- `password_reset_tokens`
- `jobs`
- `failed_jobs`

No new migration is required for this update.

## Deployment commands

After editing `.env`:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```

Keep the worker running for queued emails such as the post-reset security notice:

```bash
php artisan queue:work --tries=3 --timeout=90
```

## Test the flow

1. Create or use an active customer account (`role=customer`, `is_active=1`).
2. Open `/forgot-password`.
3. Enter the customer email.
4. Confirm the Gmail reset message arrives.
5. Click the reset button in the email.
6. Confirm the reset page opens and the email address is read-only.
7. Enter a new password containing at least 8 characters, letters, and numbers.
8. Submit the reset.
9. Confirm redirect to `/login` with the success message.
10. Sign in with the new password.
11. Confirm the same reset URL cannot be used again.

## Troubleshooting

- Reset link points to the wrong host: correct `APP_URL`, then run `php artisan optimize:clear && php artisan config:cache`.
- Gmail authentication error: use a Google App Password, not the normal Gmail account password.
- Reset request says mail could not be sent: inspect `storage/logs/laravel.log` and verify Gmail SMTP settings.
- Password-changed security email does not arrive: confirm the Laravel queue worker is running and inspect `php artisan queue:failed`.
- Existing customer is not eligible: confirm `users.role = customer` and `users.is_active = 1`.
