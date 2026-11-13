# NextPlay shared admin searchable dropdown

## Scope

The Product List now uses one reusable dropdown component for Status, Category,
Production Methods, and Sort. The category picker searches the existing flat
category tree in memory and uses the same light menu / selected-option styling
as the Bulk Quote country picker. No changes were made to routes, filter query
parameters, controllers, database records, or the Bulk Quote UI.

## Reuse on another admin Blade page

```blade
<x-admin.searchable-select
    id="example-select"
    name="example_field"
    label="Example field"
    :searchable="true"
    search-placeholder="Search options..."
>
    <option value="">All options</option>
    @foreach($options as $option)
        <option value="{{ $option->id }}" @selected((string)$selected === (string)$option->id)>{{ $option->name }}</option>
    @endforeach
</x-admin.searchable-select>
```

- The **native select** is the sole form input, retaining its original `name`,
  selected `value`, validation, `input`/`change` events, and submission behavior.
- JavaScript progressively enhances it; without JavaScript, a normal select
  remains usable.
- The component adds its versioned CSS and JS assets **only on pages using it**.
- Set `:searchable="true"` for larger lists. Search filters the options already
  rendered on the page; it does not call an API or query the database.
- Category hierarchies can provide `data-depth` and `data-label` on each
  `<option>`. The native label may still contain indentation for fallback.
- Menus are attached to `document.body` at runtime to avoid clipping inside
  scrollable/filter containers. Menus reposition on scroll or resize and flip
  above the trigger near the viewport bottom.

## Verification

```bash
php artisan optimize:clear
php artisan test --filter=ProductFilterSearchableSelectTest
php artisan test --filter=AdminProductBadgeListTest
php artisan view:cache
```

No migrations or Vite build are required: the reusable UI files live in `public/`
and are loaded through the Blade component. If the site uses a CDN, purge the
admin page and the two versioned static assets after deployment.
