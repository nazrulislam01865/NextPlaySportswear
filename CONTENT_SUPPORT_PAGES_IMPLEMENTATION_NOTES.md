# NextPlay Content, Support, Policy, and Error Pages

## Added storefront pages

- `/about-us`
- `/contact-us`
- `/help-center`
- `/how-to-order`
- `/size-guide`
- `/artwork-guidelines`
- `/customization-guide`
- `/bulk-team-ordering`
- `/shipping-delivery`
- `/returns-refunds-exchanges`
- `/payment-information`
- `/privacy-policy`
- `/terms-conditions`
- `/cookie-policy`
- `/accessibility`
- Custom HTTP 404, 500, and 503 views

Legacy short URLs such as `/about`, `/contact`, `/faq`, `/shipping`, `/returns`, `/privacy`, and `/terms` permanently redirect to their canonical URLs.

## Reusable structure

The pages use the existing `x-layouts.storefront` shell and these reusable components:

- `x-storefront.content.hero`
- `x-storefront.content.icon-card`
- `x-storefront.content.callout`
- `x-storefront.content.cta`
- `x-storefront.content.policy-shell`

The shared header and footer now link to the new named routes.

## SEO implementation

- Unique title and meta description per page
- Canonical URLs
- Open Graph and Twitter metadata
- Organization, WebSite, and WebPage JSON-LD
- FAQPage structured data on the Help Center
- Permanent redirects for duplicate legacy paths
- `noindex, nofollow` on error and maintenance pages

## Security implementation

- CSRF protection on the contact form
- Dedicated Form Request validation and input normalization
- Topic allowlist and strict field length limits
- Honeypot bot field
- Named rate limiter: per-minute and per-hour IP limits plus per-email limit
- Encrypted contact message body at rest
- HMAC fingerprints instead of storing raw IP and user-agent values
- Global response headers for anti-framing, MIME sniffing, referrer control, and browser permissions
- Production Content Security Policy and HTTPS HSTS
- Blade escaped output by default
- Contact POST response marked `no-store`

## Deployment

Run:

```bash
php artisan migrate --force
php artisan optimize:clear
npm ci
npm run build
```

Recommended production environment values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Use a shared Redis or database cache store in multi-server production so contact throttling is consistent across application instances. Keep `APP_KEY` backed up securely because encrypted contact message bodies depend on it.

The legal/policy copy is storefront-ready starter content and should be reviewed by qualified US legal and operational advisers before launch.
