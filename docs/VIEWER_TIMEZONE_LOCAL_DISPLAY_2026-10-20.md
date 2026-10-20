# NextPlay Sportswear - viewer-local timezone display

Date: October 20, 2026

## Purpose

Show each visitor their own device's timezone (including customer, guest, admin, manager, and other admin roles) rather than the server timezone. This is automatic, with **no location permission, database schema change, extra request, or timezone lookup query**.

### Implementation

- `config/app.php` **remains UTC**. Persisted timestamps, business deadlines, payment processing, promotion rules and queue/cron jobs must not be shifted globally.
- `app/Support/ViewerTime.php` is a single reusable view formatter producing an ISO-8601 UTC `<time>` element. It provides a readable UTC fallback for browsers without JavaScript and escapes its contents.
- `public/js/viewer-time.js` uses `Intl.DateTimeFormat` and the viewer's browser IANA timezone for local presentation and daylight-saving handling. The script is loaded by both the admin and storefront layouts and handles content inserted later into the page.
- 97 timestamp renderings in 53 Blade views or shared components now use `ViewerTime::render`. Areas include admin activity, orders, customers, quotations, account history, artwork, returns, guest tracking, media, coupons and notifications.
- Normal admin `datetime-local` controls for orders, bulk quotes, homepage slides, categories and coupons show local times; when submitted, the native `formdata` hook supplies UTC values to the **existing** Laravel controllers. Invalid DST-gap times trigger a field-level validity message. On a DST fallback repeated hour, an unchanged field round-trips to its **original exact UTC instant**.
- Existing promotion and sale banner editors have their **own explicitly selected schedule timezone** and intentionally retain that behavior.
- Calendar-only fields (quotation validity date, event day, requested delivery date, holiday calendar, and order delivery date ranges) are intentionally **not converted**: they are calendar days, not moments in time, and shifting them could change the order's contractual date.
- Relative timestamps such as "Just now" and "3 hours ago" are already timezone-independent and have been preserved.

### Example

UTC `2026-10-20 05:34:26` displays as:

- Asia/Dhaka: `Oct 20, 2026 - 11:34 AM`
- America/New_York: `Oct 20, 2026 - 1:34 AM`
- America/Los_Angeles: `Oct 19, 2026 - 10:34 PM`

The HTML has an ISO `datetime` attribute and a hover title identifying the timezone and daylight saving abbreviation when available.

### Setup / deployment

No migration or `.env` timezone change required. Keep `APP_TIMEZONE` and `config/app.php` on UTC; do not change the server OS or MySQL connection timezone to implement local display.

Deploy the complete project (preserve `.env`, DB, uploaded files, `vendor`), and run:

```sh
php artisan optimize:clear
php artisan view:clear
php artisan test --filter=ViewerTimeTest
```

Browser test scripts require Node 18+:

```sh
node tests/js/viewer-time.test.mjs
TZ=Asia/Dhaka node tests/js/viewer-time-input.test.mjs
TZ=America/New_York node tests/js/viewer-time-input.test.mjs
ORIGINAL_UTC=2026-11-01T06:30 EXPECTED_LOCAL=2026-11-01T01:30 TZ=America/New_York node tests/js/viewer-time-input.test.mjs
```

### Important boundaries

- This delivers viewer-local **web UI** timestamps. It does not rewrite emails, PDF documents, export files, scheduled jobs, or audit logs. Those have no browser context; such outputs should be updated separately when recipient timezone settings are available.
- Browser timezone detection reflects the device configuration. Incorrect device timezone configuration will give an incorrect display; users can correct the timezone in their device/browser settings. This is not derived from IP address.
- If JavaScript is disabled, the server-rendered UTC fallback remains, clearly marked in the element title.
- The project's historical timestamps must already represent UTC instants. If legacy rows were stored in local server time without timezone conversion, they need a separate, carefully audited data migration.
- Full Laravel/Blade compilation and browser-side integration testing must be completed in a full development environment (`vendor/` is not in the supplied ZIP). The project in this ZIP is **not yet production-certified**.
