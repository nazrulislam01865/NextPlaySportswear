# Category Product Remove Tag Fix

Fixed the optional subcategory × behavior on the filtered category product list.

Problem:
- Clicking × only checked a hidden checkbox and showed a red struck-through chip.
- The tag visually stayed on the page.
- If the page was refreshed before saving, the tag appeared again.

Fix:
- Clicking × now hides the optional subcategory chip immediately.
- The remove checkbox remains checked for the form payload.
- The form is submitted automatically so the removal is saved immediately.
- Existing Apply flow remains available for adding subcategories and bulk assignments.
- Primary category tags remain protected because they do not render a remove control.

Changed files:
- resources/views/admin/categories/products.blade.php
- resources/css/admin.css
- public/build/assets/admin-CategoryProductManagement.css
