# Category displayLabel Null Fix

Fixed the admin category create page error:

`App\Models\Category::displayLabel(): Return value must be of type string, null returned`

Cause:
- The create page initializes a new unsaved Category model.
- That model has no `menu_label` and no `name` yet.
- `_create_form.blade.php` calls `$category->iconUrl()`, which calls `defaultIconUrl()`, which calls `displayLabel()`.
- Because both label fields were empty, `displayLabel()` returned `null` even though its return type is `string`.

Fix:
- Updated `Category::displayLabel()` to always return a string.
- Fallback order is now: menu label → name → slug → `New Category`.

Changed file:
- `app/Models/Category.php`
