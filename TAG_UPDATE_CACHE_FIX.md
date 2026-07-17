# Product tag update/cache fix

This update fixes product tags reverting to old values after edit by:

- accepting both the current `tags_text` field and legacy-style `tags`/`tag` inputs;
- normalizing comma, semicolon, newline, array, and JSON-array tag formats;
- preserving existing product tags when an update request does not include any tag field;
- adding a product catalog cache version so storefront product lists/search/homepage collections refresh immediately after product changes;
- flushing both category/catalog caches after product create/update/delete/duplicate/bulk actions.

Run after deployment:

```bash
php artisan optimize:clear
```
