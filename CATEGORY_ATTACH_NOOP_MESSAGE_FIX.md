# Category product add/no-op message fix

Fixed the category product assignment flow so clicking **Add selected products** in a newly-created category no longer falls through to the generic no-op status message.

## Changes
- Added an explicit `assignment_action=attach_products` marker to the add-existing-products form.
- Added action-aware validation in `CategoryProductController`.
- Empty add attempts now return a proper validation message instead of a green success-style status.
- Successful product attachments now return `Added X product(s) to Category Name`.
- Bulk assignment and row tag updates still work as before.
- Assignment changes are now counted only when category rows are actually inserted or removed.
