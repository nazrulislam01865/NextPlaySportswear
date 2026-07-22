# Homepage Section Unified Item Manager Update

## What changed

The repeatable item area used by the admin **Homepage Sections** editor was redesigned so large sections no longer display every item as a full form card.

Each item-based homepage section now has:

- One shared add/edit form.
- A compact desktop table of added items.
- A responsive mobile list.
- Edit and remove actions for each item.
- Up/down ordering controls.
- A 30-item counter that matches backend validation.
- A warning that prevents submitting while an item is still being edited but has not been added back to the list.
- Hidden form fields generated from the compact list, preserving the existing `homepage_sections.items` JSON structure and storefront behavior.

The change automatically applies to all item-enabled homepage sections, including Hero checklist lines, Buyer Cards, Design Steps, Quote Checklist, Ordering Process, Customization Options, Reasons, Use Cases, Testimonials, FAQ Questions, and Final CTA contact lines.

## Files changed

- `resources/views/admin/homepage-sections/edit.blade.php`
- `package.json`
- `package-lock.json`
- `public/build/manifest.json`
- Generated files under `public/build/assets/`

## Build correction

The existing frontend build imported `saxen` through `read-excel-file`, but it was not installed as a declared dependency. `saxen` was added to `package.json`, allowing the Vite production build to complete successfully.

## Database impact

No migration or database schema change is required. Existing section items remain compatible because the backend request and JSON format are unchanged.

## Verification completed

- Blade source PHP syntax check passed.
- The Blade template compiled successfully through Laravel's Blade compiler.
- Vite production build completed successfully.
- Generated admin CSS includes the newly used responsive and item-state classes.

PHPUnit could not be executed in the supplied container because the CLI PHP installation lacks `dom`, `mbstring`, `xml`, and `xmlwriter`. This is an environment limitation rather than an application test failure.
