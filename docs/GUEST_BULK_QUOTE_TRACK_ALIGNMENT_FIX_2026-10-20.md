# Guest Bulk Quote - Track Form Alignment Fix

## Cause
The three-column track form used `align-items: end`. Inline validation adds a third row inside each input label, making the label columns taller. The submit button was bottom-aligned to the bottom of the validation message rather than the input control.

## Fix
Only the Track Bulk Quote search form is changed:
- The form grid uses `align-items: start` so variable field error heights do not move the action.
- The button's top offset matches the existing field title line height and gap, aligning the button to the two inputs.
- On narrow/mobile layouts the button remains in its own row without a top offset.
- Existing Blade input labels, inline error messages, data submission, server validation, CSRF, route names, and account/order features are unchanged.

## Changed files
- `resources/css/storefront.css` (scoped alignment rules)
- `public/build/manifest.json` (points to a fresh CSS asset to avoid stale browser CSS)
- `public/build/assets/storefront-guest-alignment-*.css` (new compiled CSS artifact)
- `docs/GUEST_BULK_QUOTE_TRACK_ALIGNMENT_FIX_2026-10-20.md` (this note)

There are no database or backend changes.

## Local application
Keep your existing `.env`, `vendor` dependencies, database, and uploaded files. Replace the project code, then:

```bash
php artisan optimize:clear
php artisan view:clear
```

Hard refresh `/bulk-quote/track`. Verify the button aligns with the inputs both before and after submitting empty fields. Check desktop and mobile screen widths.
