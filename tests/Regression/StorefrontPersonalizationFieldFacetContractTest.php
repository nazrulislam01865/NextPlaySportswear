<?php

$root = dirname(__DIR__, 2);
$source = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$fieldSource = file_get_contents($root.'/app/Services/Storefront/PersonalizationFieldFacets.php');
$request = file_get_contents($root.'/app/Http/Requests/Storefront/ProductFilterRequest.php');
$shared = file_get_contents($root.'/resources/views/components/storefront/catalog/shared-filter-sections.blade.php');
$category = file_get_contents($root.'/app/Http/Controllers/Storefront/CategoryController.php');
$products = file_get_contents($root.'/app/Http/Controllers/Storefront/ProductController.php');

$checks = [
    'No Availability section is rendered in the shared filter component.' => ! str_contains($shared, "options['availability']"),
    'No Availability request is accepted as a catalog filter.' => ! str_contains($request, "'availability'"),
    'No Availability is counted as an active catalog filter.' => ! str_contains($category, "'availability'") && ! str_contains($products, "'availability'] as \$key"),
    'No legacy roster facet is generated.' => ! str_contains($source, "'player-details'") && ! str_contains($source, 'Roster fields available'),
    'Actual personalization field labels generate facet options.' => str_contains($source, '$this->personalizationFieldFacets->options($products)'),
    'Selections filter by assigned templates.' => str_contains($source, "whereHas('personalizationTemplates'") && str_contains($source, 'templateIdsForValue($option)'),
    'Only active/published templates supply field choices.' => str_contains($fieldSource, "->where('is_active', true)") && str_contains($fieldSource, "->where('is_published', true)"),
    'Disabled fields are excluded.' => str_contains($fieldSource, '($field[\'enabled\'] ?? true)'),
    'Duplicate labels are counted only once per product.' => str_contains($fieldSource, 'isset($seen[$value])'),
    'Template names are never added to filters.' => ! str_contains($fieldSource, '$template->name') && ! str_contains($fieldSource, '$template->section_title'),
    'Field hashes are validated and capped.' => str_contains($request, "'max:46'") && str_contains($request, 'field-[a-f0-9]{40}'),
];
foreach ($checks as $description => $passed) {
    if (! $passed) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
}

echo "Storefront personalization field facet contract passed.\n";
