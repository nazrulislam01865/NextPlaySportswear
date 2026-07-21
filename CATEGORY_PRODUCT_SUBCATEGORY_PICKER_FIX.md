# Category Product Subcategory Picker Fix

Issue:
- On a filtered product page for a leaf category, the inline "+ Add subcategory" picker used only the selected category's descendants.
- Leaf categories do not have descendants, so the picker displayed "No more subcategories available" even though sibling subcategories existed under the same parent category.

Fix:
- Parent category pages still show descendant categories in the picker.
- Leaf category pages now fall back to sibling categories from the same parent branch.
- Existing assigned categories are still filtered out per row.
- Primary category remains protected from accidental removal.

Changed file:
- app/Http/Controllers/Admin/CategoryProductController.php
