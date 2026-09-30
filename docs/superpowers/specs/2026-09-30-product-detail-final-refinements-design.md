# Product Detail Final Refinements Design

## Goal

Refine the existing NEXTPLAY product-detail/customizer experience so the storefront matches the approved prototype more closely, while extending existing Production Method and Shipping Method master data with optional reusable image/icon media. The Request a Sample popup/database feature is explicitly out of scope for this iteration.

## Scope

### Storefront product header

- Move the SKU from the far-right product-header position to the visitor-count row, immediately after the recent-viewer count.
- Render the SKU with slightly stronger visual emphasis than the surrounding metadata while keeping it subordinate to the product title.
- Add a small copy control next to the SKU that copies the exact SKU value to the clipboard and gives brief copied feedback.
- Do not change product identity, routing, schema markup, or catalog search behavior.

### Material option cards

- Keep the current material/fabric option data and selection state.
- Increase the visible option image size.
- Remove the outer media/image frame so the image itself is the visual focus.
- Preserve a clear selected state using the existing centralized NEXTPLAY theme tokens.
- Continue using existing master-data/product-option images; do not create a second fabric-media system.

### Sizes & Quantities

- Increase sample/product image size in each size row for better readability.
- Preserve current size groups, quantity controls, pricing calculations, validation, and size-guide behavior.

### Player Names & Numbers

- Increase the product/fabric summary image size where displayed.
- Hide the Preview column for now, including its header and row cells.
- Redistribute table width across row number, size, name, number, and delete controls.
- Preserve all current roster data, validation, add/remove/clear behavior, and cart payload behavior.

### Production & Shipping master-data media

- Extend the existing `production_methods` and `shipping_methods` master tables with optional `image_path` and `image_url` fields.
- Reuse the project's established public-storage/media conventions rather than introducing a generic media subsystem.
- Admin Production Method and Shipping Method forms must support:
  - uploading/replacing an image or icon,
  - optional external/public image URL,
  - previewing the currently resolved image,
  - removing the stored/uploaded media,
  - retaining the existing name, code, description, day ranges, default/active controls, and shipping pricing-source rules.
- Uploaded files use the public disk and remain within dedicated master-data directories.
- On replacement/removal/delete, only files owned by the corresponding master-data directory may be deleted.
- Storefront product payloads expose the resolved master image through existing production/shipping option arrays.
- Product-specific linked methods continue inheriting master name, description, day ranges, and now media; legacy unlinked product methods keep their current fallback icons.

### Review & Add to Cart

- Expand the review section to show all configured order information available from the existing builder state:
  - product image, title, and SKU,
  - selected fabric and other product options,
  - selected sizes and quantities and total pieces,
  - roster/player count and player rows where enabled,
  - artwork selection/file count,
  - production method and production timing,
  - shipping method and shipping timing,
  - product price,
  - shipping estimate,
  - remote-area surcharge when available in the existing pricing state,
  - estimated total.
- Keep the same business calculations and cart submit endpoint; presentation may be reorganized for clarity but calculations must not be duplicated.
- Make Add to Cart the clear primary action and Save for Later the secondary action, using centralized button styles.

### Right-side `Your Custom Order` card

- Rework the component to follow the supplied prototype structure:
  - dark heading,
  - product summary,
  - selected fabric,
  - compact selected size/quantity summary,
  - player section,
  - artwork section,
  - production/shipping section,
  - product price,
  - shipping estimate,
  - remote-area surcharge,
  - total estimate,
  - Add to Cart,
  - Save as Quote / existing quote action.
- Use one reusable component driven by the same Alpine builder state as the active customizer.
- Add to Cart is visible throughout customization but disabled/muted until all required customization steps are complete and the existing builder validation prerequisites are satisfied.
- Once the required steps are complete, the button becomes active and submits through the existing cart form/logic.
- Do not create a second completion or pricing state solely for the sidebar.

## Reuse and Architecture

- Keep the existing Laravel Blade + Alpine product-builder architecture.
- Keep `ProductCatalogService` as the storefront product payload boundary.
- Reuse `ProductionMethod` and `ShippingMethod` master models/controllers/requests/views rather than creating new media tables.
- Add a small focused media service/helper for production/shipping master image persistence only if it reduces duplicated controller file-management code; follow the project's existing Catalog media-service pattern.
- Continue using the reusable product customizer components already present under `resources/views/components/storefront/product/customizer/`.
- No unrelated storefront, checkout, payment, campaign, category, or email changes.

## Data and Validation

- New master-data media fields are nullable.
- Uploads accept safe image formats already used by the admin project (JPG/JPEG, PNG, WebP, AVIF) with a small image-size limit consistent with other admin image controls.
- `image_url` accepts only the same safe public URL forms already supported by project media helpers/validation.
- Upload takes precedence over URL when both are present for preview/resolution; the stored record may retain both only if existing project conventions allow it, otherwise saving an upload clears an obsolete external URL.
- Removal clears the stored path and/or URL as indicated by the form.
- Down migration removes only the four new nullable columns.

## Completion Rules

The right-side Add to Cart activation must use the current builder's actual requirements, including at minimum:

- required option selections are present,
- minimum quantity is met,
- required roster/player details are valid when roster is required,
- required artwork state/files are satisfied when artwork upload is required,
- production method is selected when production methods are enabled and required by current builder logic,
- shipping method is selected when shipping methods are enabled,
- no existing validation error remains that would prevent the normal cart submission.

The existing final submit validation remains authoritative; the sidebar disabled state is a user-friendly reflection of that state, not a replacement for server/client validation.

## Explicit Non-Goals

- No Request a Sample modal, request table, request controller, notification, or database persistence in this iteration.
- No new quote subsystem; reuse the existing bulk-quote/quote action already wired by the project.
- No new generic media library abstraction.
- No change to product pricing formulas, Stripe/payment logic, cart persistence, or checkout workflow.
- No preview column redesign; the player Preview column is simply hidden for now.

## Verification

- Feature tests for Production Method and Shipping Method media upload, URL, replace/remove, and storefront payload resolution.
- Regression/static checks for SKU placement/copy control, material image-card structure, larger size images, hidden player preview column, complete review content, sidebar prototype sections, and disabled/enabled Add to Cart state hooks.
- Blade parse safety checks for changed product-detail templates.
- JavaScript syntax/build checks for any Alpine/helper changes.
- Laravel test suite relevant to admin master data and product detail.
- `npm run build` and PHP/Blade syntax checks before packaging.
