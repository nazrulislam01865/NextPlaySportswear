# Uniform Fabric Selected Import Update

Implemented selective fabric import for the master data fabric option pages.

## What changed

- The fabric import panel no longer imports every option from the selected source list.
- Admin users now choose a fabric source first, then tick only the fabric options they want to import.
- "Select All" selects only importable items from the chosen source.
- Duplicate slugs already present in the target fabric list are shown as "Already exists" and are disabled in the chooser.
- Backend validation now requires at least one selected fabric option and confirms every selected option belongs to the selected source list.
- The import service now accepts selected source option IDs and copies only those selected options, including fabric details and images.

## Updated files

- `app/Http/Controllers/Admin/JerseyCustomizationOptionController.php`
- `app/Services/Catalog/JerseyCustomizationOptionService.php`
- `resources/views/admin/jersey-customization-options/type-index.blade.php`
