# Artwork UX compact typography and admin sidebar update — October 19, 2026

## Scope

- Updated **only the artwork-specific CSS** in `resources/css/storefront.css`, adjusting headings, subheadings, summary cards, lists, proof viewers, revision forms, approval confirmations, messages and mobile breakpoints to the existing compact My Account proportions.
- Reused NextPlay theme colors and the shared Expose font family rather than adding separate artwork fonts, icons, or buttons.
- Matched the available font-face weights (400/500/700/900). Subtitles/labels are medium; prominent page titles stay bold, not black.
- Removed the **Admin > Commerce > Artwork Approvals** navigation entry. Existing customer Artwork Approvals navigation remains.
- Removed the obsolete Artwork Review Queue shortcut from admin order details. Artwork management (proof viewing, review, feedback, upload, replies, history) stays within **Admin > Orders > View Order**.
- Minor compact padding adjustments to the admin artwork order panel and its proof-review and upload partials.
- Kept every existing route, controller, authorization rule, notification, database table, file upload/download and pricing/payment workflow unchanged. The standalone admin queue route still exists for backwards compatibility but has **no sidebar link**.

## Shared font, important deployment note

`resources/css/storefront-theme.css` remains the **central source of truth** for fonts; `--np-font-ui` is used by `--np-font-body` and `--np-font-heading`, consumed by Tailwind's sans/display utilities and by the storefront and admin layout styles. The declared typeface is **Expose** with system fallback. Local `@font-face` files must be hosted at:

- `public/fonts/expose/Expose-Regular.woff2`
- `public/fonts/expose/Expose-Medium.woff2`
- `public/fonts/expose/Expose-Bold.woff2`
- `public/fonts/expose/Expose-Black.woff2`

These `.woff2` binaries **are not present in the uploaded archive**. The CSS was changed to `/fonts/expose/...` absolute public paths to prevent build-relative font URLs. Unless you already have these licensed files on your local machine/deployment, the browser will use the consistent built-in system font fallback. No font files are distributed with this patch. Do not claim Expose renders until its Network requests return 200.

## Apply

1. Back up the project or commit current work; apply the changed-files archive at the Laravel project root.
2. If the four Expose font files are available to you and correctly licensed, place them in `public/fonts/expose/` (only on your own machines/servers).
3. Run `npm run build`, `php artisan view:clear`, and `php artisan optimize:clear`.
4. Test all four customer artwork views (list, review, changes, approved) at desktop/tablet/mobile. Confirm the admin sidebar no longer contains the separate Artwork Approvals entry, and navigate to Orders > View Order to manage proofs.
5. In browser DevTools > Network > Font verify all intended Expose files load successfully (HTTP 200) if they are deployed.

No database migration is required. Source static/syntax checks were run; full end-to-end tests need the project's installed dependencies and test database.
