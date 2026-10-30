# NextPlay: E2A HTTP 422 and after-response forwarding repair

## Root causes (2026-10-30)

1. E2A returned `422 invalid_request` for `body.to[0]`. The project's previous transport sent objects (`[{"email":"..."}]`) instead of strings (`["..."]`). The canonical `SendEmailRequest` OpenAPI schema defines `to`, `cc`, and `bcc` as arrays of strings. This affects **both customer emails and automatic confirmation forwarding** because both use the same E2A transport.
2. The `after_response` code used `SendTransactionalEmail::dispatchAfterResponse()`; if a provider exception escaped from the terminating callback, it caused a secondary `Cannot modify header information` fatal error and could interrupt later terminating callbacks. The revised email service catches/logs these errors during termination, so customer email failures cannot prevent subsequent forwarding callbacks.

The existing forwarding defaults and trigger points for new orders, bulk quotes and quote-converted orders are retained unchanged.

## Install

Extract the patch ZIP into your *existing* project root, replacing only matching files. Do not replace your `.env`, database, user uploads, or private storage.

Clear config:

```sh
php artisan config:clear
php artisan view:clear
```

Check that E2A mail delivery works (use a permitted/approved E2A recipient):

```sh
php artisan email:diagnose --send=YOUR_TEST_EMAIL
```

After the standalone test succeeds, submit **one new test inquiry** with your customer email and check the configured forwarding recipient too. The forwarding feature remains automatic only when at least one address is saved in Email Customization > Forwarding Settings.

Inspect failures without triggering bulk replays:

```sh
grep -E 'Transactional email delivery failed|Automatic confirmation forwarding failed|after response' storage/logs/laravel.log | tail -n 20
```

Existing queued jobs / failed jobs are untouched. Do not run a bulk retry until the provider issue is confirmed fixed, to avoid unwanted duplicate messages. No migrations or database modifications are included.

## Testing scope

PHP lint and ZIP integrity checks performed. The project archive excludes Composer `vendor/`, environment credentials and database runtime, so a real Laravel test run and live E2A delivery cannot be performed here.

Schema reference: https://raw.githubusercontent.com/tokencanopy/e2a/main/api/openapi.yaml (components.schemas.SendEmailRequest).
