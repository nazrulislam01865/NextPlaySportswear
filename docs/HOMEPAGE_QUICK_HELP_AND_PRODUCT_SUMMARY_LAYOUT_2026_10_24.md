# NextPlay storefront layout refinement - 24 October 2026

## Homepage FAQ / Quick Help
- Matched the provided reference at desktop widths: Quick Help is alongside the FAQ in a 436px-wide right column.
- Kept the existing 2x2 card grid, with approximately 211px-wide cards, 14px gaps, and a 196px minimum card height. Cards may grow vertically for admin-supplied longer text.
- Changed the card arrow from the navy primary color to the **centralized orange accent** (`var(--np-color-secondary)`).
- Preserved existing Quick Help admin editing, labels, destinations, uploads, fonts, colors, and responsive stacking.

## Product detail / Sizes & Quantities
- Corrected the overly aggressive 1450px query that forced a 2x2 layout even for ordinary desktop panels.
- Four **equal** columns with fabric image when customizer panel width is greater than 860px; two columns at 520-860px and one column at 520px or less. When no fabric selection is provided, three equally sized columns fill the available space.
- Product summary icons use the same **64x56px footprint** as the desktop fabric preview, or **56x50px** at mobile sizes. Uploaded images remain transparent with no extra frames, tiles, or shadows.
- Preserved every product customization step, quantity control, admin setting, source icon, and business rule.

## Deployment
The full ZIP includes both the edited source CSS and a versioned, prebuilt storefront CSS referenced by `public/build/manifest.json`. There are no database changes. After deployment:

```sh
php artisan optimize:clear
php artisan view:clear
```

If you perform your usual npm/Vite build later, it will recompile the updated source CSS.

## Validation
Focused standalone regression tests pass for Quick Help, product summary layout, material media, and managed product UI controls. CSS was parsed for syntax errors. A full live application browser test was not possible in this environment, and an older broad prototype assertion has known failures unrelated to this change.
