# Centralized Transactional Email Service

## Goal

All transactional email now goes through one application-level transport contract:

- `App\Contracts\EmailService`
- implementation: `App\Services\Email\CentralEmailService`
- business email composition: `App\Services\Email\TransactionalEmailManager`

Business controllers and services no longer need to know whether the provider is SMTP, Resend, Postmark, SES, or another Laravel mailer. Provider selection belongs in environment/configuration only.

## Provider switching

The application reads this value:

```env
TRANSACTIONAL_EMAIL_MAILER=smtp
```

That value must match a mailer defined in `config/mail.php`.

For the easiest provider portability, keep `TRANSACTIONAL_EMAIL_MAILER=smtp` and change only SMTP credentials when moving between providers:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.provider.example
MAIL_PORT=587
MAIL_TIMEOUT=10
MAIL_USERNAME=provider-username
MAIL_PASSWORD=provider-password
MAIL_FROM_ADDRESS=orders@yourdomain.com
MAIL_FROM_NAME="NextPlay Sportswear"
```

This lets you move from one SMTP-capable provider to another without changing PHP application code.

Laravel also already contains mailer entries for `ses`, `postmark`, and `resend` in `config/mail.php`. If you choose a native API transport instead of SMTP, install the transport/package required by the Laravel version deployed on the server, set its credentials, and change only `TRANSACTIONAL_EMAIL_MAILER`.

## Central service settings

Add these to the production `.env`:

```env
TRANSACTIONAL_EMAIL_ENABLED=true
TRANSACTIONAL_EMAIL_MAILER=smtp

# Queue email so web requests do not wait on the email provider.
TRANSACTIONAL_EMAIL_QUEUE=true
TRANSACTIONAL_EMAIL_QUEUE_CONNECTION=database

# Leave blank to use the existing default queue. This works with the current
# project development command, which already starts a queue listener.
TRANSACTIONAL_EMAIL_QUEUE_NAME=

TRANSACTIONAL_EMAIL_QUEUE_TRIES=3
TRANSACTIONAL_EMAIL_QUEUE_TIMEOUT=30
TRANSACTIONAL_EMAIL_QUEUE_BACKOFF=60,300,900

EMAIL_VERIFICATION_LINK_EXPIRE_MINUTES=60
EMAIL_VERIFICATION_RESEND_IP_PER_MINUTE=6
EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_MINUTE=3
EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_HOUR=10

EMAIL_SUPPORT_ADDRESS=support@yourdomain.com
EMAIL_SALES_ADDRESS=sales@yourdomain.com
EMAIL_ORDERS_ADDRESS=orders@yourdomain.com
EMAIL_RETURNS_ADDRESS=returns@yourdomain.com
```

If the company wants a single mailbox at first, all four recipient variables can use the same address.

## Queue worker

The project already has the database jobs migration. In production, a queue worker must remain running.

With the default queue:

```bash
php artisan queue:work --tries=3 --timeout=90
```

If you later set:

```env
TRANSACTIONAL_EMAIL_QUEUE_NAME=emails
```

run a worker that watches the email queue:

```bash
php artisan queue:work --queue=emails,default --tries=3 --timeout=90
```

Use Supervisor/systemd or the hosting platform's process manager so the worker restarts automatically.

## Deployment refresh

After changing email environment values:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```

## Email flows currently connected

The centralized service is used for:

1. Customer email verification after registration or email-address change.
2. Customer password reset link.
3. Password-changed security alert after reset or account password update.
4. Email-address-changed security alert to the previous address; the new address receives its verification email.
5. New customer welcome email, queued only after the customer successfully verifies their email address.
6. Contact-form customer confirmation and support-team alert.
7. Bulk quote customer confirmation and sales-team alert.
8. New order customer confirmation and order-team alert.
9. Admin order status/payment/fulfillment updates.
10. Shipment status or tracking updates.
11. Customer cancellation/change request confirmation and resolution updates.
12. Return/exchange submission confirmation and return-team alert.
13. Return/exchange/refund status updates.

Newsletter marketing email is intentionally not mixed into the transactional email layer. It should use a consent-aware marketing provider/list integration when that feature is implemented.

## Failure behavior

- Email verification dispatch is user-blocking. Registration still creates the account safely, reports a delivery problem to the verification screen, and lets the customer retry through the rate-limited resend action.
- Password reset dispatch is treated as critical. If the application cannot enqueue the email, the existing reset endpoint catches the error and tells the customer to try again.
- Order/contact/quote/status emails are non-critical. `safelyQueue()` logs a dispatch problem without rolling back or failing the customer's successful business action.
- Queued delivery failures are retried according to the configured backoff and ultimately recorded by Laravel's failed-jobs system.
- Logs record the email key and recipient count, not recipient addresses or message bodies.

## Adding a new transactional email later

Do not call `Mail::...` directly from a controller.

Add a purpose-specific method to:

```text
app/Services/Email/TransactionalEmailManager.php
```

Build an `App\Data\EmailMessage` there and send it through:

```php
$this->emails->safelyQueue($message);
```

Use `$this->emails->queue($message)` only when enqueue failure must be returned to the user.

This keeps provider details and delivery behavior out of the rest of the codebase.
