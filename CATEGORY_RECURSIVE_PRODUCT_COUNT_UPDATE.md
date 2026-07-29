# Recursive Category Product Count Update

## Behaviour

- Leaf categories display the number of unique products assigned directly to them.
- Every parent category displays the unique combined product total from all descendants.
- The calculation supports two, three, or deeper hierarchy levels.
- A product assigned to multiple leaf categories inside the same subtree is counted once for the parent.
- Legacy `category_id` and `subcategory_id` assignments remain included for compatibility.
- Soft-deleted products are excluded.
- The calculation follows `categories.parent_id`, so counts stay correct even before `category_closure` is rebuilt after an import or hierarchy change.
- The admin **Empty** filter and empty-category dashboard statistic now use the same recursive totals.
