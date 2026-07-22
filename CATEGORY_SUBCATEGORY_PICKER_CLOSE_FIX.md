# Category Subcategory Picker Close Fix

Fixed the Add subcategory picker behavior on the filtered category product list.

Changes:
- Clicking outside an open Add subcategory picker now closes it automatically.
- Opening one picker closes any other open picker.
- Pressing Escape closes open pickers.
- Search input receives focus when a picker opens.
- Existing add/remove/bulk assignment behavior is unchanged.

Changed file:
- resources/views/admin/categories/products.blade.php
