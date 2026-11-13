# NextPlay shared admin searchable filters

## Scope

This iteration reuses the existing `<x-admin.searchable-select>` component on seven
admin index screens. It does not change the filter controllers or option values.

| Screen | Dropdowns |
| --- | --- |
| Media Gallery | Type, Date, Category |
| Categories | Status, Type |
| Orders | Status, Payment, FlowTrack (only when enabled) |
| Bulk Quote Requests | Status, Priority, Assignee, FlowTrack, Country |
| Contact Requests | Status, Topic |
| Returns & Exchanges | Status, Type |
| Customers | Account, Verification, Orders, Marketing, Sort |

The centralized component keeps the original native `<select>` as the form
value and preserves all its HTML attributes, including Alpine bindings.
It enhances the select with a consistently styled, optionally searchable menu
using the existing public CSS/JS assets. The gallery's `type` select emits a
visual-only sync event on tab changes so its caption reflects the active tab;
this does not trigger an additional gallery API request.

Only Assignee and Country on Bulk Quotes use the in-memory search input.
All others retain a compact options list, keyboard support, selected highlighting,
Escape/outside-click closing and the original no-JavaScript fallback.

## Local verification

```bash
php artisan optimize:clear
php artisan test --filter=SharedSearchableFilterRolloutTest
php artisan test --filter=ProductFilterSearchableSelectTest
php artisan test --filter=AdminMediaGalleryTest
php artisan test --filter=ContactRequestsInboxTest
php artisan test --filter=CustomerDirectoryTest
php artisan view:cache
```

No migration or Vite rebuild is required. Shared assets remain loaded only by
pages using the component. Browser verification should cover desktop, tablet,
mobile, dropdown opening, selected values, search, keyboard navigation, and the
Media tab-to-select synchronization.
