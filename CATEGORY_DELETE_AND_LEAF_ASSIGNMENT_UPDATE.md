# Category deletion and leaf-only product assignment update

## Implemented behavior

- Every category and subcategory now has a Delete action on the admin category page.
- Deleting a parent category deletes its complete child subtree in one transaction.
- The confirmation dialog shows affected child categories, products, and menu-link counts before submission.
- Affected products are not deleted. All category assignments are removed and the products become categoryless.
- Menu items that point to deleted categories are disabled and detached from the category to prevent broken navigation references.
- Product assignment is allowed only on a last-level category: a category that currently has no children.
- A one-level category can hold products only while it has no child.
- In a two-level tree, only the second level can hold products.
- In a three-level tree, only the third level can hold products.
- Product forms, category-product assignment pages, bulk assignment, legacy synchronization, CSV hierarchy imports, duplication, and hierarchy reordering all enforce the same leaf-only rule.
- The included migration cleans existing parent/intermediate assignments. Products that have no remaining valid leaf assignment become categoryless.

## Deployment

Back up the database first, deploy the included files at their listed paths, then run:

```bash
php artisan down
composer dump-autoload -o
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

If frontend assets are built locally, run `npm ci && npm run build` locally and deploy the resulting `public/build` directory instead of building on the server.

## Verification checklist

1. Delete a leaf category with a product and confirm the product remains but has no category.
2. Delete a parent category and confirm the popup lists child/product impact and the complete subtree disappears.
3. Open a parent category's Products page and confirm direct assignment is disabled.
4. Open a leaf category's Products page and confirm assignment is enabled.
5. Edit a product and confirm only categories without children appear in the category picker.
