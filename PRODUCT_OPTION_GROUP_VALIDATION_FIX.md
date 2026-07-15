# Product Option Group Validation Fix

## Root cause

Jersey customization types are stored internally with underscore identifiers such as `neck_and_collar` and `sleeves_and_cuffs`. During product validation, those internal enum values were copied into the hidden product option-group `code` field. The product code rule accepts URL-safe slugs only, so the request failed with:

`The option_groups.0.code field format is invalid.`

The invalid field was hidden, so the product form could not show the administrator where the problem occurred.

## Changes

- Added `JerseyCustomizationType::productCode()` to generate URL-safe codes such as:
  - `neck-and-collar`
  - `sleeves-and-cuffs`
  - `jersey-style`
- Product validation now always regenerates customization-group codes on the server instead of trusting a hidden submitted value.
- Existing underscore-based or malformed hidden codes are corrected automatically when the product is saved.
- Added feature-level validation highlighting to the Add/Edit Product form.
- The first customization feature containing an error is automatically scrolled into view.
- Added human-readable validation messages for invalid or duplicate customization features.
- Added a regression test covering every Jersey Customization Type code.

## Database

No migration is required.
