# NextPlay product details typography alignment — 2026-10-28

Scope: Storefront product details page only (`.storefront-clean-ui .np-product-page`).

Changes:
- The entire product details page uses the centralized `--np-font-body` (Expose) family for body copy, product labels, inputs, buttons, upload instructions, and review copy.
- Headings use centralized `--np-font-heading` (also Expose).
- Generated personalization field labels, shared-entry labels, and upload labels are normalized to the centralized font and weight tokens.
- Text inputs and generated personalization labels use `--np-body-2-size` while compact upload labels retain their current small sizing.
- No markup, layouts, routes, product models, JS, or database logic changed.
- Includes a prebuilt CSS asset and updated Vite manifest; `npm run build` is not needed immediately.

Apply the patch by extracting its files into the root of your existing NextPlay project, replacing matching files. Run `php artisan optimize:clear`, then hard-refresh your browser.

Font availability: The uploaded source archive contains `@font-face` rules for `/fonts/expose/Expose-Regular.woff2`, `Expose-Medium.woff2`, `Expose-Bold.woff2`, and `Expose-Black.woff2`, but did not include the actual `.woff2` files. Ensure these fonts exist in your deployed `public/fonts/expose` directory; otherwise browsers will display the system-font fallback, even though all components use the same centralized CSS variables. Font binaries were not bundled or altered in this patch.

Verification: CSS parsing and manifest paths checked. Live browser rendering and Laravel integration tests were not run in this environment.
