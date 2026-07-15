# Price table save blocker fix

## What changed

- Relaxed product price-table validation so existing/imported old-site price tables do not block saving other product sections.
- Removed strict checks that forced price-table quantity rows to be unique, increasing, continuous, and final-open-ended only.
- Price table rows are still preserved as entered.
- Live pricing is still generated only from rows where a usable quantity and price can be parsed.
- The Qty From browser `required` blocker was removed so old imported rows can be cleaned later.
- Spreadsheet price import no longer fails when imported quantity starts repeat or are not in perfect order.
- Rebuilt Vite assets so the admin JS fix is included in `public/build`.

## Run after upload

```bash
php artisan optimize:clear
php artisan cache:clear
```

If you rebuild assets on the server:

```bash
npm ci
npm run build
```
