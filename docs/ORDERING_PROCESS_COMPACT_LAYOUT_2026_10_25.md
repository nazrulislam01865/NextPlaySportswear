# NextPlay — Compact Ordering Process correction (October 25, 2026)

## Scope
- Removed the Ordering Process section's custom 1560px container override. It now uses the exact homepage `.container`/shared gutter rules that apply to Featured Products.
- Matched ordering header-to-card spacing to the reusable homepage section-heading rhythm.
- Reduced card-body padding, step-label scale, title weight/size and description scale. Preserved five cards, image proportions, arrows, centralized fonts/colors, CTA, and admin-managed ordering images.
- Saved ordering steps pointing only to the generic product-placeholder.svg now display the correct bundled ordering-process photograph; real admin-uploaded images are never overridden. The admin preview follows that fallback.
- Production stylesheet is included under a new hashed filename, referenced by the updated Vite manifest. No npm rebuild required for this patch.

## Install
Extract the PATCH ZIP into your current NextPlay project root, preserving folder paths and replacing affected files. Back up your project first. Then run:

```bash
php artisan optimize:clear
```

No database migration is needed for this layout fix. Your original uploaded image selections and card text stay in the admin database.

## Validation
- PHP syntax checks passed for the two edited Blade files (embedded PHP).
- Source/compiled stylesheet and Vite manifest paths verified.
- ZIP files verified using CRC integrity tests.
- Full browser/live Laravel rendering could not be completed in the offline environment.
