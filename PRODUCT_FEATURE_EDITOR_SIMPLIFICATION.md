# Product Feature Editor Simplification

## Updated admin workflow

The Add/Edit Product page now uses one compact customizable-feature workflow:

1. Click **Add New Feature**.
2. Enter the feature name in a focused modal.
3. The feature is created with a safe unique internal code.
4. Select only the **Customer input style**.
5. For image, swatch, button, dropdown, and checkbox styles, add customer-visible values using the existing value editor.

## Removed visible controls

The following controls were removed from each feature card:

- Feature name input after creation
- Where it appears
- Placeholder
- Customer description
- Fixed value and fixed text
- File restriction fields
- Checkbox minimum and maximum selections
- Customer must choose
- Show in live order summary
- Preset feature buttons

The value editor itself remains unchanged, including labels, additional charges, charge basis, default choice, color controls, image URL/uploads, descriptions, and removal.

## Behavior

- Every feature created by this editor is a customer-selectable product feature.
- Active submitted features appear in **Choose Product Features** on the storefront.
- Removing a feature from the admin form removes it from the product configuration.
- Existing hidden compatibility values are preserved where appropriate.
- The server enforces `section=product`, `display_mode=customer`, and `is_active=true` so modified hidden values cannot change the simplified behavior.
- Duplicate feature names are rejected in the creation modal.
- Internal feature codes are generated automatically and made unique.
- No database migration is required.

## Changed files

- `resources/views/admin/products/_form.blade.php`
- `resources/js/app.js`
- `app/Http/Requests/Admin/ProductFormRequest.php`
- Rebuilt files under `public/build/`
