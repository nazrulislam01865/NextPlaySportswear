# Shipping Method Minimal UI Update

Updated the admin product add/edit shipping method area to match the cleaner/minimal style used elsewhere in the admin UI.

## Product add/edit page

- Reduced bold text weights in the Shipping methods section.
- Removed heavy card shadows and large visual emphasis.
- Changed shipping cards to smaller, cleaner, equal-height cards.
- Reduced checkbox/radio size and made default selector more subtle.
- Kept selected shipping methods visible with a light neutral highlight.
- Kept all existing functionality:
  - show/hide shipping methods on product page
  - select multiple shipping methods
  - choose one default shipping method
  - show estimate, delivery basis, and status

## Master Data shipping methods

- Softened status/option boxes on the create/edit form.
- Reduced bold styling on the shipping method list page.
- Did not change database structure or shipping calculation behavior.

## Deployment

Run:

```bash
php artisan optimize:clear
```

No migration is required for this UI-only update.
