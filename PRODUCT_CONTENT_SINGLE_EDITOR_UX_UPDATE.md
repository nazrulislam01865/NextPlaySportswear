# Product Content Single Editor UX Update

Updated the admin product content editor so it remains one single WordPress-style editor while being easier to use.

## What changed

- Kept one visible rich text editor for all product tab content.
- Kept internal section markers so the system can still split content into:
  - `description_html`
  - `customization_artwork_html`
  - `fulfillment_html`
- Replaced large blue marker blocks with compact section headings.
- Added quick section buttons above the editor to jump to Description, Customization & Artwork, and Fulfillment.
- Added subtle filled indicators for sections that already contain content.
- Renamed Restore section markers to Reset headings.
- No database migration is required.
- No npm build is required because the editor style/script is contained in the Blade component.

## Updated file

- `resources/views/components/admin/tabbed-rich-editor.blade.php`
