# NextPlay Typography — Design System v1.1

## Scope

The homepage, shared site header/navigation, footer and opt-in `.nps-type-*` utility classes follow the supplied **NextPlay_Typography_Design_System** template. Canonical `.np-product-card` titles, badges, prices, descriptions and CTA type styling are intentionally unchanged. No application logic, queries, database tables, migrations, or routes are changed.

The two central authoring files are:

- `resources/css/storefront-theme.css`: Expose `@font-face`, brand colors, font fallback, semantic typography scales.
- `resources/css/storefront-typography.css`: reusable components and template mappings for home, header and footer.

The generated `public/css/storefront-typography.css` is linked **after** existing Vite assets in `resources/views/components/layouts/storefront.blade.php`; this means production works even if an older `public/build` is deployed. Never manually edit the generated copy. Rebuild after changing source tokens:

```bash
python3 deploy/build-storefront-typography.py
```

## Mandatory Expose font setup

The project archives did **not** include the original font files even though their CSS already referenced them. The user-provided **Expose_Complete.zip** contains those files. Install them inside the project's public directory (this distribution does not re-distribute the font binaries):

```bash
bash deploy/install-expose-fonts.sh '/absolute/path/to/Expose_Complete.zip'
```

The installer extracts only four originals into `public/fonts/expose` and validates the WOFF2 signatures: `Expose-Regular.woff2`, `Expose-Medium.woff2`, `Expose-Bold.woff2`, `Expose-Black.woff2`. **Include the four installed WOFF2 assets when you deploy to your own server.** Do not commit the archive itself. Assets must respond with status 200 at `/fonts/expose/Expose-Regular.woff2` and `/fonts/expose/Expose-Bold.woff2`. Browser Network/Fonts should show `Expose` and **Segoe UI** only as fallback. No CDN is required.

> The generated delivery archive intentionally does not contain the four font binaries. Install from your provided archive before testing/deploying.

## Type and colors from template

- Primary: Expose. Secondary fallback: Segoe UI, then generic sans-serif.
- H1: 40px / 32px mobile, 700, 1.15; H2: 32px / 26px mobile, 700, 1.25; H3: 24px / 22px mobile, 700, 1.3; card heading/H4: 20px, 700, 1.4.
- Normal body: 16px / 1.6; lead: 18px / 1.6 (mobile 16px); nav/menu: 15px / 500; buttons: 16px / 500 / 1.4; footer links: 14px; captions: 13px; eyebrow: 12px uppercase.
- Navy `#051e44`, orange `#cf5d38`, hover `#ad4023`, text `#26364b`, muted `#52637a`, links `#a43e20`, border `#bac6d5`, soft `#f5f7fa`; semantic success/warning/error/focus use template variables.

### Reuse

Use `nps-type-h1` through `nps-type-h6`, `nps-type-body`, `nps-type-lead`, `nps-type-small`, `nps-type-caption`, `nps-type-eyebrow`, `nps-type-prose`, `nps-type-muted`, `nps-type-inverse`, `nps-type-link`, `nps-type-btn`, `nps-type-btn--outline` or `nps-type-btn--accent`. All values come from `--nps-*` tokens, not individual page styles.

## Visual QA

After installing fonts, load homepage at desktop, 768px and 375px widths. Check topbar, navigation/mobile navigation, slider, hero, category cards, Shop by Sport, process cards, best-selling gear, FAQ, customer stories, newsletter, footer. Confirm product cards retain their previous sizes and CTA geometry. Check color contrast of custom backgrounds and text. Test keyboard focus and tabs. Existing browser/prototype styles for product configurator, checkout and bulk quote remain out of scope.

## Deployment

No new database work is required and no production `npm` rebuild is required for the typography patch. Upload both new CSS files (source + generated), changed theme/layout/font loader, and the four self-hosted WOFF2 files from the supplied font archive. Deploy with existing workflow; clear Laravel view cache if needed: `php artisan view:clear`. Because the static CSS version changes in the layout, browser caches will refresh.
