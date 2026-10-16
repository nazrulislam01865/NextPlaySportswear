# NextPlay — E2A mail provider

E2A has been added **as a Laravel mail transport** to the existing centralized email architecture. This does not change registration, password reset, order emails, templates, jobs, routes, database schema, or security rules. Existing SMTP and other Laravel mailers remain available.

## 1. Add the E2A credentials to your private `.env`

Do not commit the API key to Git and do not post it in chat.

```dotenv
E2A_API_KEY=your_private_agent_scoped_api_key
E2A_FROM_EMAIL=nextplayspoartswear@agents.e2a.dev
E2A_API_URL=https://api.e2a.dev/v1
E2A_TIMEOUT=20

MAIL_FROM_ADDRESS=nextplayspoartswear@agents.e2a.dev
MAIL_FROM_NAME="NextPlay Sportswear"

MAIL_MAILER=e2a
TRANSACTIONAL_EMAIL_MAILER=e2a
```

IMPORTANT: the sender above is the exact E2A agent address you created (`nextplayspoartswear`, including the `spoa` spelling). The `MAIL_FROM_ADDRESS` must match the agent address on the E2A transport. Set the display name for this agent in the E2A account as well; the API sender is controlled by the agent, not an arbitrary Laravel From header.

No new Composer package is required; this transport uses the Laravel HTTP client and the Symfony mailer already shipped with Laravel 13. Existing `phpoffice/phpspreadsheet` and other project dependencies are untouched.

## 2. Test before enabling production

On your existing Laravel installation:

```bash
php artisan optimize:clear
php artisan email:diagnose
php artisan email:diagnose --send=your-verified-address@example.com
```

Verify receipt and the E2A dashboard's message status. The service may refuse customer recipients on the shared `agents.e2a.dev` domain until your account is approved for external sending. Test with your verified E2A login email first. A successful HTTP `accepted` response means E2A queued the message, not that the destination inbox received it.

## 3. Switching email providers without editing PHP

Both `MAIL_MAILER` and `TRANSACTIONAL_EMAIL_MAILER` are supported. For NextPlay's centralized transactional notifications, `TRANSACTIONAL_EMAIL_MAILER` takes precedence. Use the same value for both to make *all* application mail use the same provider.

- E2A: `MAIL_MAILER=e2a`, `TRANSACTIONAL_EMAIL_MAILER=e2a`; configure `E2A_*`, and set `MAIL_FROM_ADDRESS` to the E2A agent address.
- SMTP/Brevo SMTP/other SMTP host: `MAIL_MAILER=smtp`, `TRANSACTIONAL_EMAIL_MAILER=smtp`; set `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, and a verified `MAIL_FROM_ADDRESS` for that provider.
- Another Laravel mailer: use the configured mailer name in both settings, ensure its dependencies/credentials are installed, and set its verified From address.

After **every** provider/From address change, run:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
php artisan email:diagnose
```

Queued messages that are already waiting will use the mailer selected **when the job executes**. Test a newly sent email after the switch. Avoid configuring `log`/`array` as production delivery transports. The existing failover mailer can optionally be configured using `MAIL_FAILOVER_MAILERS=e2a,smtp` **only** when the fallback provider accepts the same authenticated sender identity, otherwise the fallback will be rejected by the provider. Do not enable an unverified failover.

## 4. Retried or held messages

E2A is registered through `app/Providers/EmailServiceProvider.php` and `app/Services/Email/E2aTransport.php`. New centralized mail jobs carry a stable `X-NextPlay-Delivery-ID` UUID. The E2A transport maps this to the `Idempotency-Key` HTTP header; retrying the **same logical email** will not create a duplicate during E2A's retention period, provided the payload is unchanged.

`accepted`, `sent`, and `scheduled` indicate provider acceptance, but not necessarily recipient delivery. `pending_review`, 401/403, rate limits, network failures, or an HTTP `failed` response are surfaced as errors rather than falsely reporting a delivered email. Sender mismatch fails before the API request. No automatic switch is performed on send failures; change mailers explicitly if needed.

## 5. What did not change

Existing account confirmation, password-reset, order-status, quotation, shipment, and admin/customer notifications continue to use `App\Contracts\EmailService` and `CentralEmailService`. No table, migration, route, template, controller, CSS, JavaScript, or queue architecture was modified. No `.env` or private API key is included in the release archive.
