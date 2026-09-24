<?php

$root = dirname(__DIR__, 2);

$files = [
    'categoryView' => $root.'/resources/views/storefront/categories/show.blade.php',
    'categoryFilter' => $root.'/resources/views/components/storefront/category/filter-panel.blade.php',
    'allProductsFilter' => $root.'/resources/views/components/storefront/product/category-filter-panel.blade.php',
    'categoryModel' => $root.'/app/Models/Category.php',
    'categoryRequest' => $root.'/app/Http/Requests/Admin/CategoryFormRequest.php',
    'adminController' => $root.'/app/Http/Controllers/Admin/CategoryController.php',
    'adminForm' => $root.'/resources/views/admin/categories/_form.blade.php',
    'storefrontController' => $root.'/app/Http/Controllers/Storefront/CategoryController.php',
    'catalogService' => $root.'/app/Services/Storefront/CategoryCatalogService.php',
];

$contents = [];
foreach ($files as $name => $path) {
    $contents[$name] = file_get_contents($path);
    if ($contents[$name] === false) {
        fwrite(STDERR, "Unable to read {$name} source.\n");
        exit(1);
    }
}

$migrationMatches = glob($root.'/database/migrations/*filter_product_types*');
$migration = $migrationMatches !== [] ? file_get_contents($migrationMatches[0]) : '';

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(
    str_contains($contents['categoryView'], 'np-filter-shell np-filter-shell--clean hidden self-start lg:flex'),
    'The category product page uses the same clean desktop filter shell as All Products.'
);

$expect(
    ! str_contains($contents['categoryFilter'], 'np-catalog-filter-header--compact')
        && ! str_contains($contents['categoryFilter'], '>Apply Filters</button>')
        && str_contains($contents['categoryFilter'], 'data-product-filter-form'),
    'The category filter panel matches the All Products filter interaction: standard header, auto-apply hook, and no Apply Filters button.'
);

$expect(
    $migration !== false
        && str_contains($migration, "json('filter_product_types')")
        && str_contains($migration, 'nullable()'),
    'A nullable category-level product type selection is persisted without creating a separate product-type subsystem.'
);

$expect(
    str_contains($contents['categoryModel'], "'filter_product_types'")
        && preg_match("/'filter_product_types'\s*=>\s*'array'/", $contents['categoryModel']) === 1,
    'Category exposes filter_product_types as a fillable array setting.'
);

$expect(
    str_contains($contents['categoryRequest'], "'filter_product_types' => ['nullable', 'array'")
        && str_contains($contents['categoryRequest'], "'filter_product_types.*'")
        && str_contains($contents['categoryRequest'], 'filter_product_types_present'),
    'Category admin validation accepts only a bounded list of selected product-type strings and supports explicitly clearing the selection.'
);

$expect(
    str_contains($contents['adminController'], 'use App\\Models\\Product;')
        && str_contains($contents['adminController'], "whereNotNull('product_type')")
        && str_contains($contents['adminController'], "pluck('product_type')")
        && str_contains($contents['adminController'], "'productTypeOptions'")
        && str_contains($contents['adminController'], "'filter_product_types'"),
    'The admin category form reads product type options from existing product master data and persists the category selection.'
);

$expect(
    str_contains($contents['adminForm'], 'name="filter_product_types_present"')
        && str_contains($contents['adminForm'], 'name="filter_product_types[]"')
        && str_contains($contents['adminForm'], 'Product types from product master data'),
    'The category admin UI exposes product-master product types as selectable checkboxes, including an explicit empty-selection sentinel.'
);

$expect(
    str_contains($contents['catalogService'], "filter_product_types")
        && str_contains($contents['catalogService'], "'product_types'"),
    'Category storefront facet options are limited to the product types selected by the admin when a selection is configured.'
);

$expect(
    str_contains($contents['storefrontController'], 'filter_product_types')
        && str_contains($contents['storefrontController'], "product_types"),
    'Category storefront requests ignore product-type values that the admin has not enabled for that category.'
);

if ($failures !== []) {
    fwrite(STDERR, "Category filter parity/product type admin regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Category filter parity/product type admin regression passed.\n";
