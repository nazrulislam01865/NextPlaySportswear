# NextPlay checkout-only typography alignment (31 October 2026)

## Scope

Source: `Archive(20261031-073208).zip`. References: `NextPlay_Typography_Design_System (1) (1)(6).html` and `NextPlay_Form_Design_System(4).html`.

**Only checkout routes (`checkout.*`) are affected.** Homepage, product, product cards, catalog filters, account pages, and global theme/layout style rules were not changed. `resources/views/components/layouts/storefront.blade.php` was edited only to change the cache version for its existing conditional checkout CSS link.

Checkout routes covered by the shared stylesheet:

- Contact/information, shipping address, billing address, review, payment method, and the legacy shipping-method screen
- Referral checkout (a separate legacy layout)
- Checkout success / order confirmation

## Typography and color rules

| Role | Shared token / value |
| --- | --- |
| Primary font | `--nps-font` -> Expose, Segoe UI fallback |
| Page heading | `--nps-h1` (40px; mobile `--nps-h1-mobile` 32px), 700 |
| Form panel heading | `--nps-h4` (20px), 700, per Form Design System |
| Form description | `--nps-body` (16px), 400, 1.6 line-height |
| Standard label / supporting copy | `--nps-small` (14px), varying 400–500 |
| Metadata | `--nps-caption` (13px) |
| Text buttons | `--nps-button-size` (16px), `--nps-button-weight` (500); minimum 48px |
| Headings | `--nps-navy` / `#051E44` |
| Body | `--nps-text` / `#26364B` |
| Secondary | `--nps-muted` / `#52637A` |
| Links | `--nps-link` / `#A43E20` |
| Success / errors | `--nps-success` / `#187044`, `--nps-error` / `#B42318` |

Exceptions preserve white text on dark backgrounds (buttons, order summary heading) and maintain status cues. The referral checkout's final action uses the Form Design System's navy primary button. Existing Stripe-hosted payment behavior is untouched.

## Explicitly protected

- **No HTML input, select, textarea or field-control attribute changes.** Each field was compared with the uploaded archive.
- No input height, width, color, border, padding, placeholder, focus, disabled, checkbox/radio, or validation-state rule was added.
- No checkout controller, order service, route, JavaScript, migration, cart or pricing code was changed.
- No product-card or catalog-filter component or stylesheet was changed.

Typography edits to the referral checkout turn uppercase *headings and the CTA label* into sentence case without changing button attributes/behaviors; the confirmation page receives a checkout-only CSS class and sentence-case title.

## Source and activation

The main edits are in `resources/css/nextplay-checkout.css`; the same bytes are published at `public/css/nextplay-checkout.css`. Both need to be deployed, along with the small Blade changes and the updated layout link cache query. CSS is conditionally loaded after the general storefront typography stylesheet **only on checkout routes**.

Run after deployment:

```bash
php artisan view:clear
```

No new Vite build or database migration is needed to deploy the included generated checkout CSS. Hard-refresh checkout pages to pick up the new `v=20261031templatev11` asset URL. Keep the existing self-hosted Expose fonts installed.

## Tests and limitations

Passed in the source archive:

```bash
php tests/Support/checkout_typography_template_static_check.php
php tests/Support/storefront_design_system_static_check.php all
```

Also passed: CSS stylesheet/declaration syntax parsing (`tinycss2`), exact comparison of checkout form-control markup and form/button attributes against the uploaded archive, and CSS source/public parity.

**Not verified:** authenticated browser rendering and Stripe's real payment flow. Chromium headless could not complete the local smoke check. Review `/checkout/information`, `/checkout/shipping-address`, `/checkout/billing-address`, `/checkout/review`, `/checkout/referral`, and `/checkout/success` at desktop and mobile widths on a running environment before production rollout.
