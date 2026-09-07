# Customer Email Verification Setup

## What is implemented

Customer registration now uses Laravel's built-in email-verification contract and signed URL validation while keeping all outbound mail inside the project's centralized transactional email service.

Flow:

1. Customer submits `/register`.
2. Registration data is normalized and stored by `CustomerRegistrationService`.
3. The new customer starts with `email_verified_at = null`.
4. Laravel's `Registered` event triggers the customer's verification notification.
5. `User::sendEmailVerificationNotification()` delegates to `TransactionalEmailManager`.
6. A temporary signed verification URL is queued through `EmailService`.
7. The customer is authenticated but redirected to the verification notice.
8. Protected account and checkout routes use Laravel's `verified` middleware.
9. Clicking the valid signed link marks `email_verified_at` and dispatches Laravel's `Verified` event.
10. The first successful verification queues the customer welcome email and records `welcome_email_sent_at` to prevent duplicates.
11. The customer is shown the dedicated email-verification success page before continuing.
12. Changing the account email clears verification and sends a new verification link, but does not send another welcome email after re-verification.

## Required production environment values

Use your existing transactional email settings and add the verification controls below:

```env
TRANSACTIONAL_EMAIL_ENABLED=true
TRANSACTIONAL_EMAIL_MAILER=smtp
TRANSACTIONAL_EMAIL_QUEUE=true
TRANSACTIONAL_EMAIL_QUEUE_CONNECTION=database

# Must be the exact public origin used by customers. HTTPS is strongly recommended.
APP_URL=https://yourdomain.com

EMAIL_VERIFICATION_LINK_EXPIRE_MINUTES=60
EMAIL_VERIFICATION_RESEND_IP_PER_MINUTE=6
EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_MINUTE=3
EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_HOUR=10
```

`APP_URL` matters because the verification signature is generated for the public verification URL. Set it to the exact customer-facing HTTPS origin before sending production email.

Your actual provider credentials still belong only in `.env`, never in PHP, Blade, Git, or the downloadable project archive.

Example SMTP values:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_USERNAME=your-provider-username
MAIL_PASSWORD=your-provider-password
MAIL_FROM_ADDRESS=orders@yourdomain.com
MAIL_FROM_NAME="NextPlay Sportswear"
```

## Queue worker is required when queueing is enabled

Production should keep a worker running:

```bash
php artisan queue:work --tries=3 --timeout=90
```

If you later configure a dedicated queue name, include that queue in the worker command.

## Deployment commands

After copying the updated project:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
npm ci
npm run build
```

This update includes `2026_09_07_000002_add_welcome_email_sent_at_to_users_table.php`. It adds a nullable `users.welcome_email_sent_at` timestamp used only to guarantee that the welcome message is sent once. Existing customer accounts are backfilled as already welcomed so deployment does not generate duplicate welcome emails.

## Routes added

- `GET /email/verify` → verification notice
- `GET /email/verify/{id}/{hash}` → signed verification handler
- `POST /email/verification-notification` → rate-limited resend
- `GET /email/verified` → verified-success confirmation page

## Security behavior

- Verification URLs are temporary signed URLs and cannot be modified without invalidating the signature.
- The hash is tied to the user's current email, so a link for an old email stops working after an address change.
- Verification links expire (60 minutes by default).
- Resend is limited independently by source IP and authenticated account.
- Account and checkout routes reject authenticated-but-unverified customers through Laravel's `verified` middleware.
- Registration keeps the existing honeypot, CSRF protection, unique database constraint, normalized email, and strong password validation.
- Email delivery remains provider-agnostic and queueable through the centralized email service.
- No SMTP credentials are committed to source code.

## Test locally without sending real email

For local development you can use Laravel's log mailer:

```env
TRANSACTIONAL_EMAIL_ENABLED=true
TRANSACTIONAL_EMAIL_MAILER=log
TRANSACTIONAL_EMAIL_QUEUE=false
MAIL_MAILER=log
```

Register a customer, then inspect `storage/logs/laravel.log` for the generated verification email and signed URL.

For a real provider, switch the mailer/credentials in `.env`, clear cached config, and keep the queue worker running.
