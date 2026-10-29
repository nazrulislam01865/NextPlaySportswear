# NextPlay bulk quote details screen / Word request download

Scope: admin bulk quotation request **show page only**. The app's routes, permissions, quotation publish and message APIs remain unchanged.

The button labelled **Download Request PDF** matches the supplied screen prototype but returns `.docx` as requested. The generated Word file includes the request, the active **Storefront Branding > Storefront Logo** used in the storefront header (not the footer logo), configured contact details, and rendered artwork pages. Changes to the uploaded header logo are reflected in subsequent downloads; no logo path in `.env` is needed. The original upload remains downloadable separately.

The server must have PHP Phar (`PharData`, normally enabled with PHP) and two OS tools for inline previews of uploaded PDFs / Word documents:

```bash
sudo apt-get update
sudo apt-get install -y poppler-utils libreoffice-writer
```

The Word export embeds the active Storefront Branding header logo directly from its stored image. PNG/JPEG logos work natively. WebP/AVIF header logos require PHP GD with support for that format, to embed a transparent PNG in Word. The fallback is the storefront-configured logo (`STOREFRONT_LOGO`, normally `/images/logo.png`) when no logo is uploaded. Customer artwork in PNG/JPEG works without conversion tools. PDFs are rasterized with `pdftoppm` (up to the first 20 pages to bound document size). DOC/DOCX and GIF/WebP images are converted by headless LibreOffice first. An explicit error is shown rather than silently producing a document without artwork if conversion fails or the original file is missing. The original customer file is never altered.

Configure the existing storefront brand, if needed, in `.env`:

```
STOREFRONT_NAME="NextPlay Sportswear"
STOREFRONT_EMAIL="support@your-domain.com"
STOREFRONT_ADDRESS="Your full business address"
STOREFRONT_PHONE="+00 000 000 0000"
```

Logo does not need a new environment variable: it follows **Admin > Storefront Branding > Storefront Logo**. Do not point the document export to the Footer Logo or Favicon. `STOREFRONT_LOGO` is only the pre-existing fallback when no main logo has been uploaded. Set contact variables to actual company values; the placeholders above are examples, not real NPS contact details.

Defaults for `BULK_QUOTE_PDFTOPPM_BIN` and `BULK_QUOTE_SOFFICE_BIN` are `pdftoppm` and `soffice`; set them only if the executables live outside PATH. After configuring, use `php artisan optimize:clear` (and `php artisan config:cache` if the deployment caches configuration). No migration or frontend build is required: the dedicated stylesheet is served directly from `public/css/`.

The Word file is generated in a temporary directory, streamed as a private response, and deleted after delivery. No new tables, APIs, or background jobs were introduced.

## 2026-10-29: Blade parse fix and optional environment settings

Fixed the Bulk Quote Details activity timeline: nested Blade `@if` / `@endif` directives now appear on separate lines so the view does not compile into an unexpected `endif` (HTTP 500). Other inline conditionals on that page were expanded for the same reason; the generated UI, routes, and permissions are unchanged.

**No new `.env` entries are required.** When `STOREFRONT_NAME` is absent or empty, the Word document identifies the business as `NextPlay Sportswear`. Missing `STOREFRONT_EMAIL`, `STOREFRONT_ADDRESS`, and `STOREFRONT_PHONE` simply omit those header contact lines. The existing Storefront Branding header logo is read from the stored branding setting, independently of all these `.env` keys. When it has not been configured, the existing public `images/logo.png` can be used. Conversion command settings are optional: executable names `pdftoppm` and `soffice` are the defaults; the underlying utilities must still be installed if a PDF or Office upload needs to be displayed in the document.

After deploying the patched files, run `php artisan view:clear` to remove any previously compiled invalid Blade view; clear cached configuration only after you change its values. No migrations or frontend rebuilds are needed.
