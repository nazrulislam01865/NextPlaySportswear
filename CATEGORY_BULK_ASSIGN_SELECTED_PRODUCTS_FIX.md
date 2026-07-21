# Category Bulk Assign Selected Products Fix

Fixed a bulk category assignment issue where assigning a category to multiple selected products could update only one product.

Cause:
- The bulk assignment depended only on the submitted `selected_product_ids[]` checkbox values.
- In the current dynamic table/picker UI, bulk submit could submit an incomplete selected list in some interactions.

Fix:
- Added a hidden synced bulk selection payload: `bulk_selected_product_ids[]`.
- Before form submit, JavaScript now copies every currently checked product row into that hidden payload whenever bulk categories are selected.
- Backend now merges `selected_product_ids[]` with `bulk_selected_product_ids[]`, filters to visible products, de-duplicates them, and applies the selected bulk categories to all of them.
- Existing row-level Add subcategory, remove tag, and normal Apply behavior remain unchanged.

Changed files:
- resources/views/admin/categories/products.blade.php
- app/Http/Controllers/Admin/CategoryProductController.php
