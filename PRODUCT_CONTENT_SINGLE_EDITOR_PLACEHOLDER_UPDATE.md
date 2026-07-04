# Product Content Single Editor Placeholder Update

Implemented a single WordPress-style Product Content editor for the product add/edit page.

## Behavior

The admin now sees one rich text editor only. Inside that editor, three visible non-editable section markers are shown:

- [Description]
- [Customization & Artwork]
- [Fulfillment]

The admin writes content under each marker. On input and form submission, the editor parses the content between markers and fills the existing hidden fields:

- `description_html`
- `customization_artwork_html`
- `fulfillment_html`

## Safety

No database migration is required. Existing frontend product tabs continue using the same backend fields.

A **Restore section markers** button is available if section placeholders are accidentally disturbed.
