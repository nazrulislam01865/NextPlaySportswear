# Password Recovery Security Update

This update applies four production hardening changes to the NextPlay customer password-recovery flow.

## 1. Existing customer sessions are revoked after a successful password reset

A new `users.auth_session_version` column starts at `0`. Every successful forgot-password reset increments it. `EnforceCustomerSessionVersion` stores the version in each authenticated customer session and compares it on every web request. A mismatch logs out that browser session.

This design is storage-driver independent: it works with Redis, database and file sessions without scanning/deleting provider-specific session keys.

**Deployment note:** customer sessions created before this middleware is deployed do not contain the version key and will be asked to sign in once. Admin guard sessions are not targeted.

Run the migration:

```bash
php artisan migrate --force
```

## 2. Stronger password-reset throttling

Reset-link requests are independently limited by IP and normalized email fingerprint. Defaults:

```env
PASSWORD_RESET_REQUEST_IP_PER_MINUTE=3
PASSWORD_RESET_REQUEST_IP_PER_HOUR=12
PASSWORD_RESET_REQUEST_EMAIL_PER_MINUTE=2
PASSWORD_RESET_REQUEST_EMAIL_PER_HOUR=5

PASSWORD_RESET_SUBMIT_IP_PER_MINUTE=5
PASSWORD_RESET_SUBMIT_IP_PER_HOUR=20
PASSWORD_RESET_SUBMIT_EMAIL_PER_MINUTE=3
PASSWORD_RESET_SUBMIT_EMAIL_PER_HOUR=10
```

These values are configured in `config/security.php`, so they can be tuned without touching the rate-limiter code.

## 3. Account-enumeration protection restored

Unknown, inactive, admin, valid, broker-throttled and provider-failure reset requests all receive the same public status text:

> If an active customer account matches that email, a password reset link will be sent. The link expires in 15 minutes and can only be used once.

The backend still checks for an active customer before creating a token; unknown addresses do not generate email. Errors are logged internally rather than revealing account existence to the browser.

To reduce timing differences between valid and unknown addresses, reset mail is queued by default:

```env
TRANSACTIONAL_EMAIL_PASSWORD_RESET_SYNC=false
```

Keep a queue worker supervised in production.

## 4. Provider switching remains centralized

Gmail SMTP remains usable now:

```env
TRANSACTIONAL_EMAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-google-address@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-google-address@gmail.com
MAIL_FROM_NAME="NextPlay Sportswear"
```

The application does not contain Gmail calls. Later, change only mail configuration and install the selected Laravel transport dependency where required. Business services such as checkout, password reset, shipment and returns do not change.

Examples:

```env
# Postmark
TRANSACTIONAL_EMAIL_MAILER=postmark
POSTMARK_API_KEY=...

# Resend
TRANSACTIONAL_EMAIL_MAILER=resend
RESEND_API_KEY=...

# Amazon SES
TRANSACTIONAL_EMAIL_MAILER=ses
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
```

Provider failover can also be enabled without source changes:

```env
TRANSACTIONAL_EMAIL_MAILER=failover
MAIL_FAILOVER_MAILERS=postmark,smtp
MAIL_FAILOVER_RETRY_AFTER=60
```

The failover configuration deliberately does **not** fall back to Laravel's `log` mailer, because a log fallback could make a security-critical send appear successful without delivering a message.

## Deployment sequence

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
```

Ensure your production queue worker is running under Supervisor/systemd.
