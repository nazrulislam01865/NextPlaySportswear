# HEX Color and Fabric Image Option Update

## What changed

The product customization value editor now supports both customer-facing HEX colors and fabric/option images.

### HEX color controls

For every option value, the admin can:

- Type a HEX code with or without `#`.
- Use three-digit or six-digit HEX input.
- Use the visual color picker.
- See an immediate color preview in the admin form.

Before validation, values are normalized to uppercase six-digit form, for example:

- `15345d` becomes `#15345D`
- `abc` becomes `#AABBCC`

The storefront receives only a validated HEX value. Swatches render the exact color, display its HEX code, and automatically use a readable dark or light checkmark.

### Fabric and option images

For every image-based option value, including fabrics, the admin can now either:

- Upload a JPG, PNG, WebP, or AVIF image up to 5 MB, or
- Enter a remote image URL.

A local upload takes priority over a URL when both are submitted. Uploaded files are saved to:

```text
storage/app/public/products/{product_id}/options
```

The admin form shows an immediate preview. Existing uploaded images remain attached when the product is edited without choosing a replacement.

On the storefront, an option group with display type `image` shows the uploaded or linked fabric image as a selectable card.

## Admin usage

1. Open **Admin → Products**.
2. Create or edit a product.
3. Open **Customization**.
4. Add a product option group.
5. For a fabric selector, set **Display type** to **Image**.
6. Add a value for each fabric.
7. Upload a fabric image or provide its image URL.
8. For a color selector, set **Display type** to **Swatch** and enter each value's HEX color.
9. Save the product and open the storefront preview.

## Deployment

No new database migration is required because the existing `product_option_values` table already contains `color_hex`, `image_path`, and `image_url` columns.

After replacing the updated files, run:

```bash
php artisan optimize:clear
php artisan storage:link
npm install
npm run build
```

If `public/storage` already exists, Laravel may report that the link already exists; that is safe.

## Validation and security

- HEX values are normalized and validated server-side.
- Image uploads are validated by file type, image content, extension, and 5 MB limit.
- Existing option image IDs are checked against the product being edited before their stored path is reused.
- Storefront color output is normalized again before being written into inline CSS.
- Product pricing and selections still require server-side validation during cart and checkout processing.
