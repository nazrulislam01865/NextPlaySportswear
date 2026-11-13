# NextPlay Commerce Admin - Shared Forms / Filter Alignment

## Scope
Only six admin screens were opted in to the shared form guideline: Orders, Bulk Quote Requests, Contact Requests, Returns & Exchanges, Customers, and Rewards Programme. The existing Product filter dropdown was *reused*, not replaced.

## Changes
- A single route-scoped `public/css/nextplay-admin-commerce-forms.css` applies the existing Expose font token and the 48px height / 16px text / 8px radius guideline to search controls, searchable dropdown triggers, buttons and Rewards inputs.
- One reusable `resources/views/components/admin/filter-search.blade.php` renders the same single-border search-with-icon control. Orders, Bulk Quotes, Contact Requests and Customers use this component while preserving GET `q` values.
- Returns now uses the existing `admin.filter-bar` component. Its Filter button stays action-sized instead of stretching across all remaining space. Selected type/status filters still submit normally.
- The Customers filter has explicit responsive grid regions for query, four filters, sort and actions. All elements remain inside the card with no negatively positioned buttons. Sort is not expanded to full row on desktop.
- Rewards uses scoped classes so its numeric inputs, labels and Save button conform to the same design without changing disabled/checkbox behavior.
- Searchable dropdown options, names, values and submit events remain native and unchanged. Menus continue to be positioned by the existing shared JS under the document body.

## Not changed
Controllers, routes, permissions, migrations, SQL queries, order statuses, emails, reward calculations, pagination and table data.

## Validation commands
```bash
php artisan optimize:clear
php artisan test --filter=CommerceFormGuidelineTest
php artisan test --filter=SharedSearchableFilterRolloutTest
php artisan test --filter=CustomerDirectoryTest
php artisan test --filter=ContactRequestsInboxTest
php artisan test --filter=BulkQuoteRequestAdminTest
php artisan view:cache
```

The archive contains no Composer `vendor` directory, so run these Laravel feature tests in the local NextPlay project after overlaying changed files. No migration or Vite build is required: the new CSS loads directly from `public/css`.

## Visual acceptance
Verify the six admin pages at 390px, 768px, 1024px, 1440px and wider: all form controls should be 48px high; labels readable; dropdown captions 16px regular (not bold); search icon does not overlap text; form actions remain within their cards; Customers sort and Filter customers never overlap the directory below; and the Returns Filter button does not stretch full width on desktop. Test selection, clearing and form submission without changing data.
