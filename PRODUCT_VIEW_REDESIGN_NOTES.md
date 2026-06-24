# NextPlay Product View Redesign

## Scope

This update changes only the customer-facing product details presentation. The existing product database, admin product form, cart, pricing tiers, customization builder, artwork upload, production speed, related products, SEO metadata, and order workflows remain unchanged.

## Updated files

- `resources/views/storefront/products/show.blade.php`
- `resources/views/components/storefront/product/gallery.blade.php`
- `resources/views/components/storefront/product/detail-information.blade.php`
- Compiled Vite assets under `public/build/`

## New product hero layout

The top section now follows the supplied reference more closely:

1. Large contained product image with blue/cyan frame accents.
2. Horizontal thumbnail gallery.
3. Normal-case product title instead of the previous oversized uppercase display heading.
4. Short product description directly under the title.
5. Compact two-column Detail / Information table populated from the existing `specifications` data.
6. Product metadata rows for SKU, categories, tags, and brand.
7. A compact starting-price and action block below the product information.

## Existing field mapping

No database changes are required for this design review.

| Frontend content | Existing backend field |
|---|---|
| Title | `products.name` |
| Short description | `products.short_description` |
| Detail table | `products.specifications` |
| SKU | `products.sku` |
| Category | Existing product/category relationships |
| Tags | `products.tags` |
| Brand | `products.brand` |
| Starting price | Existing product price tiers / base price |
| Images | Existing product images |

If specifications are empty, the page safely falls back to product type, brand, and minimum order information.

## Preserved behavior

- Product image lightbox
- Quantity price table
- Full customization builder
- Add-to-cart flow
- Bulk quotation link
- Description, specifications, and FAQ tabs
- Related products
- Structured data and SEO
- Mobile and tablet responsive behavior

## Build

The frontend assets were rebuilt successfully with Vite.
