# Homepage Category Multi-Select and Duplicate Message Update

## Applied to every category-driven homepage section

- Featured Categories / What Are You Looking For?
- Popular Categories
- Shop by Sport
- Best-Selling Gear
- Any future homepage section whose item fields include `category_id`

## Changes

- Several categories, subcategories, or sub-subcategories can be selected and added at once.
- Search and level filters continue to work with multi-selection.
- “Select visible” and “Clear selection” actions were added.
- Categories already in the section are marked as **Already added**.
- Duplicate selections are blocked before submission.
- Existing duplicate rows are identified by their full category path and item positions.
- Backend validation now returns readable messages such as:

  `“Bags › Drawstring Bags › Soccer Drawstring Bags” is listed more than once (items 1 and 2). Keep it only once or choose a different category.`

- Technical messages such as `items.0.category_id field has a duplicate value` are no longer shown.

## Updated files

- `app/Http/Requests/Admin/HomepageSectionRequest.php`
- `resources/views/admin/homepage-sections/edit.blade.php`
- `resources/views/components/layouts/admin.blade.php`

No database migration is required.
